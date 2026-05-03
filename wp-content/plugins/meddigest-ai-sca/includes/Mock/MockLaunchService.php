<?php
namespace MedDigest\AiSca\Mock;

use MedDigest\AiSca\Credits\CreditHoldService;
use MedDigest\AiSca\Credits\Idempotency;
use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\MemberPress\EligibilityService;
use MedDigest\AiSca\Practice\StationAttemptService;
use MedDigest\AiSca\Support\Clock;
use MedDigest\AiSca\Support\Logger;
use MedDigest\AiSca\Support\Uuid;

if (!defined('ABSPATH')) {
    exit;
}

final class MockLaunchService
{
    public const STATUS_RUNNING    = 'running';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';

    /**
     * Create and start a Full Mock SCA run.
     *
     * @param int $user_id User ID.
     */
    public function create_mock($user_id)
    {
        global $wpdb;

        $user_id = absint($user_id);

        if (!$user_id) {
            return new \WP_Error('meddigest_ai_sca_invalid_mock_user', __('Invalid Full Mock launch request.', 'meddigest-ai-sca'), ['status' => 400]);
        }

        if (!(new EligibilityService())->user_has_sca_cases_premium($user_id)) {
            return new \WP_Error('meddigest_ai_sca_premium_required', __('Active SCA Cases Premium is required to launch Full Mock SCA.', 'meddigest-ai-sca'), ['status' => 403]);
        }

        $active_mock = $this->get_active_mock_for_user($user_id);
        if ($active_mock) {
            return $active_mock;
        }

        $active_station = (new StationAttemptService())->get_active_attempt_for_user($user_id);
        if ($active_station) {
            return new \WP_Error('meddigest_ai_sca_active_session_exists', __('You already have an active AI practice session.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $allocation = (new MockAllocationService())->allocate();
        if (is_wp_error($allocation)) {
            return $allocation;
        }

        $mock_uuid = Uuid::v4();
        $hold_key  = Idempotency::key('mock', $mock_uuid, 'hold');
        $hold      = (new CreditHoldService())->hold_credits(
            $user_id,
            12,
            'mock_run',
            $mock_uuid,
            $hold_key,
            ['station_count' => 12]
        );

        if (empty($hold['ledger_uuid'])) {
            return new \WP_Error('meddigest_ai_sca_credit_hold_failed', __('At least 12 available AI credits are required to launch Full Mock SCA.', 'meddigest-ai-sca'), ['status' => 402]);
        }

        $start_ts  = time();
        $now       = Clock::mysql_utc();
        $schedule  = (new MockSchedule())->build($start_ts);
        $phase     = (new MockSchedule())->current_phase($schedule, $start_ts);
        $snapshots = [];

        foreach ($allocation as $station) {
            $snapshots[] = [
                'station_number'             => absint($station['station_number']),
                'case_post_id'               => absint($station['case_post_id']),
                'mock_primary_group_term_id' => absint($station['mock_primary_group_term_id']),
                'group_label'                => sanitize_text_field($station['group_label'] ?? ''),
                'snapshot'                   => $station['snapshot'],
            ];
        }

        $tables = Schema::tables();

        try {
            $wpdb->query('START TRANSACTION');

            $inserted = $wpdb->insert(
                $tables['mock_runs'],
                [
                    'mock_uuid'                => $mock_uuid,
                    'user_id'                  => $user_id,
                    'status'                   => self::STATUS_RUNNING,
                    'membership_snapshot_json' => wp_json_encode(['sca_cases_premium_active' => true, 'snapshotted_at' => $now]),
                    'hold_ledger_uuid'         => $hold['ledger_uuid'],
                    'schedule_json'            => wp_json_encode($schedule),
                    'station_snapshot_json'    => wp_json_encode($snapshots),
                    'started_at'               => $schedule['started_at'],
                    'current_phase'            => $phase['phase'],
                    'current_station'          => $phase['station_number'],
                    'phase_ends_at'            => $phase['phase_ends_at'],
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ]
            );

            if (false === $inserted) {
                $wpdb->query('ROLLBACK');
                $this->release_failed_launch($user_id, $mock_uuid, 'mock_insert_failed');

                return new \WP_Error('meddigest_ai_sca_mock_insert_failed', __('Full Mock SCA could not be created.', 'meddigest-ai-sca'), ['status' => 500]);
            }

            foreach ($allocation as $station) {
                $schedule_station = $schedule['stations'][absint($station['station_number']) - 1];

                $station_inserted = $wpdb->insert(
                    $tables['mock_stations'],
                    [
                        'mock_uuid'                  => $mock_uuid,
                        'station_number'             => absint($station['station_number']),
                        'attempt_uuid'               => Uuid::v4(),
                        'case_post_id'               => absint($station['case_post_id']),
                        'mock_primary_group_term_id' => absint($station['mock_primary_group_term_id']),
                        'reading_start_at'           => $schedule_station['reading_start_at'],
                        'live_start_at'              => $schedule_station['live_start_at'],
                        'hard_stop_at'               => $schedule_station['live_end_at'],
                        'grade_status'               => 'pending',
                        'station_snapshot_json'      => wp_json_encode($station['snapshot']),
                        'created_at'                 => $now,
                        'updated_at'                 => $now,
                    ]
                );

                if (false === $station_inserted) {
                    $wpdb->query('ROLLBACK');
                    $this->release_failed_launch($user_id, $mock_uuid, 'mock_station_insert_failed');

                    return new \WP_Error('meddigest_ai_sca_mock_station_insert_failed', __('Full Mock SCA stations could not be created.', 'meddigest-ai-sca'), ['status' => 500]);
                }
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $throwable) {
            $wpdb->query('ROLLBACK');
            $this->release_failed_launch($user_id, $mock_uuid, 'mock_exception');
            Logger::error($throwable->getMessage());

            return new \WP_Error('meddigest_ai_sca_mock_create_exception', __('Full Mock SCA could not be created.', 'meddigest-ai-sca'), ['status' => 500]);
        }

        $commit = (new CreditHoldService())->commit_hold(
            $user_id,
            12,
            'mock_run',
            $mock_uuid,
            Idempotency::key('mock', $mock_uuid, 'commit'),
            ['station_count' => 12]
        );

        if (empty($commit['ledger_uuid'])) {
            $this->update_mock($mock_uuid, ['status' => self::STATUS_CANCELLED]);
            $this->release_failed_launch($user_id, $mock_uuid, 'mock_commit_failed');

            return new \WP_Error('meddigest_ai_sca_mock_commit_failed', __('Full Mock SCA credit commit failed.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $this->update_mock($mock_uuid, ['commit_ledger_uuid' => $commit['ledger_uuid']]);

        return $this->get_mock($mock_uuid);
    }

    /**
     * Get a mock run by UUID.
     *
     * @param string $mock_uuid Mock UUID.
     */
    public function get_mock($mock_uuid)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['mock_runs']} WHERE mock_uuid = %s LIMIT 1",
                $mock_uuid
            ),
            ARRAY_A
        );
    }

    /**
     * Owner-checked mock run.
     *
     * @param int    $user_id   User ID.
     * @param string $mock_uuid Mock UUID.
     */
    public function get_owned_mock($user_id, $mock_uuid)
    {
        $mock = $this->get_mock($mock_uuid);

        if (!$mock) {
            return new \WP_Error('meddigest_ai_sca_mock_not_found', __('Full Mock SCA run not found.', 'meddigest-ai-sca'), ['status' => 404]);
        }

        if (absint($mock['user_id']) !== absint($user_id)) {
            return new \WP_Error('meddigest_ai_sca_mock_forbidden', __('You do not have access to this Full Mock SCA run.', 'meddigest-ai-sca'), ['status' => 403]);
        }

        return $mock;
    }

    /**
     * Active mock for a user.
     *
     * @param int $user_id User ID.
     */
    public function get_active_mock_for_user($user_id)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['mock_runs']}
                WHERE user_id = %d AND status IN ('running', 'processing')
                ORDER BY created_at DESC LIMIT 1",
                absint($user_id)
            ),
            ARRAY_A
        );
    }

    /**
     * Update a mock run.
     *
     * @param string $mock_uuid Mock UUID.
     * @param array  $data      Data.
     */
    public function update_mock($mock_uuid, array $data)
    {
        global $wpdb;

        $tables             = Schema::tables();
        $data['updated_at'] = Clock::mysql_utc();

        return false !== $wpdb->update($tables['mock_runs'], $data, ['mock_uuid' => $mock_uuid]);
    }

    /**
     * Release a failed launch before station 1 reading can continue.
     *
     * @param int    $user_id   User ID.
     * @param string $mock_uuid Mock UUID.
     * @param string $reason    Reason.
     */
    private function release_failed_launch($user_id, $mock_uuid, $reason)
    {
        (new CreditHoldService())->release_hold(
            $user_id,
            12,
            'mock_run',
            $mock_uuid,
            Idempotency::key('mock', $mock_uuid, 'release', $reason),
            ['reason' => $reason]
        );
    }
}
