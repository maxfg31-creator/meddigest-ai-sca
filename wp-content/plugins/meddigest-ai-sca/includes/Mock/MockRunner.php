<?php
namespace MedDigest\AiSca\Mock;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\OpenAI\RealtimeTokenService;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class MockRunner
{
    /**
     * Owner-safe mock status.
     *
     * @param int    $user_id   User ID.
     * @param string $mock_uuid Mock UUID.
     */
    public function status($user_id, $mock_uuid)
    {
        $mock = (new MockLaunchService())->get_owned_mock($user_id, $mock_uuid);

        if (is_wp_error($mock)) {
            return $mock;
        }

        $mock = $this->refresh_phase($mock);

        return $this->safe_status($mock);
    }

    /**
     * Create Realtime token for the current live station.
     *
     * @param int    $user_id   User ID.
     * @param string $mock_uuid Mock UUID.
     */
    public function realtime_token($user_id, $mock_uuid)
    {
        $mock = (new MockLaunchService())->get_owned_mock($user_id, $mock_uuid);

        if (is_wp_error($mock)) {
            return $mock;
        }

        $mock = $this->refresh_phase($mock);

        if (MockLaunchService::STATUS_RUNNING !== $mock['status'] || 'live' !== $mock['current_phase']) {
            return new \WP_Error('meddigest_ai_sca_mock_not_live', __('Full Mock SCA is not in a live station phase.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $station = $this->get_station($mock_uuid, absint($mock['current_station']));

        if (!$station) {
            return new \WP_Error('meddigest_ai_sca_mock_station_not_found', __('Full Mock SCA station not found.', 'meddigest-ai-sca'), ['status' => 404]);
        }

        $attempt = [
            'attempt_uuid' => $station['attempt_uuid'],
            'user_id'      => $mock['user_id'],
            'case_post_id' => $station['case_post_id'],
            'snapshot_json' => $station['station_snapshot_json'],
        ];

        $token = (new RealtimeTokenService())->create_client_secret($attempt);

        if (is_wp_error($token)) {
            return $token;
        }

        return [
            'attempt_uuid' => $station['attempt_uuid'],
            'station'      => absint($station['station_number']),
            'token'        => $token,
        ];
    }

    /**
     * Save transcript turns for a mock station.
     *
     * @param int    $user_id        User ID.
     * @param string $mock_uuid      Mock UUID.
     * @param int    $station_number Station number.
     * @param array  $turns          Turns.
     */
    public function save_transcript($user_id, $mock_uuid, $station_number, array $turns)
    {
        global $wpdb;

        $mock = (new MockLaunchService())->get_owned_mock($user_id, $mock_uuid);

        if (is_wp_error($mock)) {
            return $mock;
        }

        $station_number = absint($station_number);
        $station        = $this->get_station($mock_uuid, $station_number);

        if (!$station) {
            return new \WP_Error('meddigest_ai_sca_mock_station_not_found', __('Full Mock SCA station not found.', 'meddigest-ai-sca'), ['status' => 404]);
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

        $tables = Schema::tables();

        $wpdb->update(
            $tables['mock_stations'],
            [
                'transcript_json' => wp_json_encode(
                    [
                        'source'     => 'browser_realtime_events',
                        'updated_at' => Clock::mysql_utc(),
                        'turns'      => $sanitized,
                    ]
                ),
                'updated_at'      => Clock::mysql_utc(),
            ],
            [
                'mock_uuid'      => $mock_uuid,
                'station_number' => $station_number,
            ]
        );

        return ['saved' => true];
    }

    /**
     * Get mock station row.
     *
     * @param string $mock_uuid      Mock UUID.
     * @param int    $station_number Station number.
     */
    public function get_station($mock_uuid, $station_number)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['mock_stations']} WHERE mock_uuid = %s AND station_number = %d LIMIT 1",
                $mock_uuid,
                absint($station_number)
            ),
            ARRAY_A
        );
    }

    /**
     * Get all stations for a mock.
     *
     * @param string $mock_uuid Mock UUID.
     */
    public function get_stations($mock_uuid)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$tables['mock_stations']} WHERE mock_uuid = %s ORDER BY station_number ASC",
                $mock_uuid
            ),
            ARRAY_A
        );
    }

    /**
     * Refresh server-time phase and queue aggregation when finished.
     *
     * @param array $mock Mock run.
     */
    private function refresh_phase(array $mock)
    {
        if (!in_array($mock['status'], [MockLaunchService::STATUS_RUNNING, MockLaunchService::STATUS_PROCESSING], true)) {
            return $mock;
        }

        $schedule = !empty($mock['schedule_json']) ? json_decode($mock['schedule_json'], true) : [];
        $schedule = is_array($schedule) ? $schedule : [];
        $phase    = (new MockSchedule())->current_phase($schedule, time());
        $status   = $mock['status'];

        if ('processing' === $phase['phase']) {
            $status = MockLaunchService::STATUS_PROCESSING;
            $results = !empty($mock['results_json']) ? json_decode($mock['results_json'], true) : [];
            $results = is_array($results) ? $results : [];

            if (!in_array(($results['status'] ?? ''), ['failed', 'requires_openai_configuration'], true)) {
                (new MockResultsAggregator())->queue($mock['mock_uuid']);
            }
        }

        (new MockLaunchService())->update_mock(
            $mock['mock_uuid'],
            [
                'status'          => $status,
                'current_phase'   => $phase['phase'],
                'current_station' => $phase['station_number'],
                'phase_ends_at'   => $phase['phase_ends_at'],
            ]
        );

        return (new MockLaunchService())->get_mock($mock['mock_uuid']);
    }

    /**
     * Safe status response.
     *
     * @param array $mock Mock run.
     */
    private function safe_status(array $mock)
    {
        $station_number = absint($mock['current_station']);
        $station        = $station_number ? $this->get_station($mock['mock_uuid'], $station_number) : null;
        $setup          = [];

        if ($station) {
            $snapshot = !empty($station['station_snapshot_json']) ? json_decode($station['station_snapshot_json'], true) : [];
            $snapshot = is_array($snapshot) ? $snapshot : [];
            $setup    = [
                'station_number'       => $station_number,
                'case_post_id'         => absint($station['case_post_id']),
                'title'                => $snapshot['title'] ?? '',
                'mode'                 => $snapshot['mode'] ?? 'video',
                'doctor_brief'         => $snapshot['doctor_brief_override'] ?? '',
                'group_id'             => absint($station['mock_primary_group_term_id']),
            ];
        }

        $phase_end = !empty($mock['phase_ends_at']) ? strtotime($mock['phase_ends_at']) : 0;

        return [
            'mock_uuid'          => $mock['mock_uuid'],
            'status'             => $mock['status'],
            'phase'              => $mock['current_phase'],
            'station_number'     => $station_number,
            'total_stations'     => MockSchedule::STATIONS,
            'phase_ends_at'      => $mock['phase_ends_at'],
            'seconds_remaining'  => $phase_end ? max(0, $phase_end - time()) : 0,
            'current_station'    => $setup,
            'run_url'            => home_url('/sca-ai/mock/' . $mock['mock_uuid'] . '/run/'),
            'results_url'        => home_url('/sca-ai/mock/' . $mock['mock_uuid'] . '/results/'),
        ];
    }
}
