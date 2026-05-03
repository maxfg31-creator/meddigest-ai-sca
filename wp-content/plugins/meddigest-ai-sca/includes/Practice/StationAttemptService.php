<?php
namespace MedDigest\AiSca\Practice;

use MedDigest\AiSca\Cases\CaseConfigRepository;
use MedDigest\AiSca\Cases\CaseSnapshotService;
use MedDigest\AiSca\Credits\CreditService;
use MedDigest\AiSca\Credits\CreditHoldService;
use MedDigest\AiSca\Credits\Idempotency;
use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\MemberPress\EligibilityService;
use MedDigest\AiSca\Support\Clock;
use MedDigest\AiSca\Support\Uuid;

if (!defined('ABSPATH')) {
    exit;
}

final class StationAttemptService
{
    public const STATUS_SETUP            = 'setup';
    public const STATUS_LIVE             = 'live';
    public const STATUS_FEEDBACK_PENDING = 'feedback_pending';
    public const STATUS_COMPLETED        = 'completed';
    public const STATUS_CANCELLED        = 'cancelled';

    /**
     * Create a single-station attempt and one-credit hold.
     *
     * @param int $user_id      User ID.
     * @param int $case_post_id Case post ID.
     */
    public function create_attempt($user_id, $case_post_id)
    {
        global $wpdb;

        $user_id      = absint($user_id);
        $case_post_id = absint($case_post_id);

        if (!$user_id || !$case_post_id) {
            return new \WP_Error('meddigest_ai_sca_invalid_attempt', __('Invalid station start request.', 'meddigest-ai-sca'), ['status' => 400]);
        }

        if (!(new EligibilityService())->user_has_sca_cases_premium($user_id)) {
            return new \WP_Error('meddigest_ai_sca_premium_required', __('Active SCA Cases Premium is required to launch AI practice.', 'meddigest-ai-sca'), ['status' => 403]);
        }

        if (!(new CaseConfigRepository())->is_ai_enabled($case_post_id)) {
            return new \WP_Error('meddigest_ai_sca_case_not_enabled', __('AI practice is not enabled for this case.', 'meddigest-ai-sca'), ['status' => 403]);
        }

        $active = $this->get_active_attempt_for_user($user_id);
        if ($active) {
            return new \WP_Error('meddigest_ai_sca_active_session_exists', __('You already have an active AI practice session.', 'meddigest-ai-sca'), ['status' => 409, 'attempt_uuid' => $active['attempt_uuid']]);
        }

        $attempt_uuid = Uuid::v4();
        $hold_key     = Idempotency::key('station', $attempt_uuid, 'hold');
        $hold         = (new CreditHoldService())->hold_credits(
            $user_id,
            1,
            'station_attempt',
            $attempt_uuid,
            $hold_key,
            ['case_post_id' => $case_post_id]
        );

        if (empty($hold['ledger_uuid'])) {
            return new \WP_Error('meddigest_ai_sca_credit_hold_failed', __('At least 1 available AI credit is required to start this station.', 'meddigest-ai-sca'), ['status' => 402]);
        }

        $snapshot = (new CaseSnapshotService())->build_snapshot($case_post_id);
        $tables   = Schema::tables();
        $now      = Clock::mysql_utc();
        $inserted = $wpdb->insert(
            $tables['attempts'],
            [
                'attempt_uuid'            => $attempt_uuid,
                'user_id'                 => $user_id,
                'case_post_id'            => $case_post_id,
                'status'                  => self::STATUS_SETUP,
                'membership_snapshot_json' => wp_json_encode(['sca_cases_premium_active' => true, 'snapshotted_at' => $now]),
                'hold_ledger_uuid'        => $hold['ledger_uuid'],
                'snapshot_json'           => wp_json_encode($snapshot),
                'started_at'              => $now,
                'created_at'              => $now,
                'updated_at'              => $now,
            ]
        );

        if (false === $inserted) {
            (new CreditHoldService())->release_hold(
                $user_id,
                1,
                'station_attempt',
                $attempt_uuid,
                Idempotency::key('station', $attempt_uuid, 'release', 'insert_failed'),
                ['reason' => 'attempt_insert_failed']
            );

            return new \WP_Error('meddigest_ai_sca_attempt_insert_failed', __('The station attempt could not be created.', 'meddigest-ai-sca'), ['status' => 500]);
        }

        return $this->get_attempt($attempt_uuid);
    }

    /**
     * Begin live station and commit the held credit.
     *
     * @param int    $user_id      User ID.
     * @param string $attempt_uuid Attempt UUID.
     */
    public function begin_live($user_id, $attempt_uuid)
    {
        $attempt = $this->get_owned_attempt($user_id, $attempt_uuid);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        if (self::STATUS_LIVE === $attempt['status']) {
            return $attempt;
        }

        if (self::STATUS_SETUP !== $attempt['status']) {
            return new \WP_Error('meddigest_ai_sca_invalid_attempt_state', __('This station cannot be started from its current state.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $commit = (new CreditHoldService())->commit_hold(
            $attempt['user_id'],
            1,
            'station_attempt',
            $attempt_uuid,
            Idempotency::key('station', $attempt_uuid, 'commit'),
            ['case_post_id' => $attempt['case_post_id']]
        );

        if (empty($commit['ledger_uuid'])) {
            return new \WP_Error('meddigest_ai_sca_credit_commit_failed', __('The held AI credit could not be committed.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $hard_stop = gmdate('Y-m-d H:i:s', time() + 12 * MINUTE_IN_SECONDS);

        $this->update_attempt(
            $attempt_uuid,
            [
                'status'             => self::STATUS_LIVE,
                'commit_ledger_uuid' => $commit['ledger_uuid'],
                'live_started_at'    => Clock::mysql_utc(),
                'hard_stop_at'       => $hard_stop,
            ]
        );

        return $this->get_attempt($attempt_uuid);
    }

    /**
     * End a station and queue feedback.
     *
     * @param int    $user_id      User ID.
     * @param string $attempt_uuid Attempt UUID.
     */
    public function end_attempt($user_id, $attempt_uuid)
    {
        $attempt = $this->get_owned_attempt($user_id, $attempt_uuid);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        if (in_array($attempt['status'], [self::STATUS_FEEDBACK_PENDING, self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return $attempt;
        }

        if (self::STATUS_SETUP === $attempt['status']) {
            (new CreditHoldService())->release_hold(
                $attempt['user_id'],
                1,
                'station_attempt',
                $attempt_uuid,
                Idempotency::key('station', $attempt_uuid, 'release', 'cancel_before_live'),
                ['reason' => 'cancel_before_live']
            );

            $this->update_attempt(
                $attempt_uuid,
                [
                    'status'   => self::STATUS_CANCELLED,
                    'ended_at' => Clock::mysql_utc(),
                ]
            );

            return $this->get_attempt($attempt_uuid);
        }

        if ((new QuickCancelPolicy())->is_quick_cancel($attempt)) {
            (new CreditService())->issue_credits(
                $attempt['user_id'],
                1,
                'station_attempt',
                $attempt_uuid,
                Idempotency::key('station', $attempt_uuid, 'quick_cancel_refund'),
                ['reason' => 'quick_cancel']
            );

            $this->update_attempt(
                $attempt_uuid,
                [
                    'status'   => self::STATUS_CANCELLED,
                    'ended_at' => Clock::mysql_utc(),
                ]
            );

            return $this->get_attempt($attempt_uuid);
        }

        $this->update_attempt(
            $attempt_uuid,
            [
                'status'   => self::STATUS_FEEDBACK_PENDING,
                'ended_at' => Clock::mysql_utc(),
            ]
        );

        (new FeedbackJob())->queue($attempt_uuid);

        return $this->get_attempt($attempt_uuid);
    }

    /**
     * Save transcript turns captured from the Realtime session without exposing them on screen.
     *
     * @param int    $user_id      User ID.
     * @param string $attempt_uuid Attempt UUID.
     * @param array  $turns        Transcript turns.
     */
    public function save_transcript($user_id, $attempt_uuid, array $turns)
    {
        $attempt = $this->get_owned_attempt($user_id, $attempt_uuid);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        if (!in_array($attempt['status'], [self::STATUS_LIVE, self::STATUS_FEEDBACK_PENDING], true)) {
            return new \WP_Error('meddigest_ai_sca_invalid_attempt_state', __('Transcript capture is not available for this station state.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $sanitized = [];

        foreach ($turns as $turn) {
            if (!is_array($turn)) {
                continue;
            }

            $speaker = isset($turn['speaker']) ? sanitize_key((string) $turn['speaker']) : 'unknown';
            $text    = isset($turn['text']) ? trim(wp_strip_all_tags((string) $turn['text'])) : '';

            if ('' === $text) {
                continue;
            }

            $sanitized[] = [
                'speaker'    => in_array($speaker, ['candidate', 'patient', 'unknown'], true) ? $speaker : 'unknown',
                'text'       => $text,
                'created_at' => isset($turn['created_at']) ? sanitize_text_field((string) $turn['created_at']) : Clock::mysql_utc(),
            ];
        }

        $this->update_attempt(
            $attempt_uuid,
            [
                'transcript_json' => wp_json_encode(
                    [
                        'source'     => 'browser_realtime_events',
                        'updated_at' => Clock::mysql_utc(),
                        'turns'      => $sanitized,
                    ]
                ),
            ]
        );

        return $this->get_attempt($attempt_uuid);
    }

    /**
     * Get attempt by UUID.
     *
     * @param string $attempt_uuid Attempt UUID.
     */
    public function get_attempt($attempt_uuid)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['attempts']} WHERE attempt_uuid = %s LIMIT 1",
                $attempt_uuid
            ),
            ARRAY_A
        );
    }

    /**
     * Get owner-checked attempt.
     *
     * @param int    $user_id      User ID.
     * @param string $attempt_uuid Attempt UUID.
     */
    public function get_owned_attempt($user_id, $attempt_uuid)
    {
        $attempt = $this->get_attempt($attempt_uuid);

        if (!$attempt) {
            return new \WP_Error('meddigest_ai_sca_attempt_not_found', __('Station attempt not found.', 'meddigest-ai-sca'), ['status' => 404]);
        }

        if (absint($attempt['user_id']) !== absint($user_id)) {
            return new \WP_Error('meddigest_ai_sca_attempt_forbidden', __('You do not have access to this station attempt.', 'meddigest-ai-sca'), ['status' => 403]);
        }

        return $attempt;
    }

    /**
     * Get active attempt for user.
     *
     * @param int $user_id User ID.
     */
    public function get_active_attempt_for_user($user_id)
    {
        global $wpdb;

        $tables = Schema::tables();
        $active = [self::STATUS_SETUP, self::STATUS_LIVE];

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['attempts']}
                WHERE user_id = %d AND status IN ('" . implode("','", array_map('esc_sql', $active)) . "')
                ORDER BY created_at DESC LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Update an attempt.
     *
     * @param string $attempt_uuid Attempt UUID.
     * @param array  $data         Data.
     */
    public function update_attempt($attempt_uuid, array $data)
    {
        global $wpdb;

        $tables             = Schema::tables();
        $data['updated_at'] = Clock::mysql_utc();

        return false !== $wpdb->update(
            $tables['attempts'],
            $data,
            ['attempt_uuid' => $attempt_uuid]
        );
    }
}
