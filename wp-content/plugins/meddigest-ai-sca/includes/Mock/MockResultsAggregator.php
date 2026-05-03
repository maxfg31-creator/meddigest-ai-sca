<?php
namespace MedDigest\AiSca\Mock;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\OpenAI\ResponsesFeedbackClient;
use MedDigest\AiSca\Support\Clock;
use MedDigest\AiSca\Support\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class MockResultsAggregator
{
    public const HOOK = 'meddigest_ai_sca_generate_mock_results';

    /**
     * Register background job.
     */
    public function register()
    {
        add_action(self::HOOK, [$this, 'run'], 10, 1);
    }

    /**
     * Queue result aggregation.
     *
     * @param string $mock_uuid Mock UUID.
     */
    public function queue($mock_uuid)
    {
        $mock = (new MockLaunchService())->get_mock($mock_uuid);

        if (!$mock || MockLaunchService::STATUS_COMPLETED === $mock['status']) {
            return;
        }

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [$mock_uuid], 'meddigest-ai-sca');
            return;
        }

        wp_schedule_single_event(time() + 5, self::HOOK, [$mock_uuid]);
    }

    /**
     * Generate station feedback and aggregate final result.
     *
     * @param string $mock_uuid Mock UUID.
     */
    public function run($mock_uuid)
    {
        $mock = (new MockLaunchService())->get_mock($mock_uuid);

        if (!$mock || MockLaunchService::STATUS_COMPLETED === $mock['status']) {
            return;
        }

        $stations = (new MockRunner())->get_stations($mock_uuid);

        if (empty($stations)) {
            $this->mark_processing_issue($mock_uuid, __('Full Mock SCA stations were not found.', 'meddigest-ai-sca'), 'failed');
            return;
        }

        foreach ($stations as $station) {
            if ('completed' === $station['grade_status'] && !empty($station['feedback_json'])) {
                continue;
            }

            $attempt = [
                'attempt_uuid'  => $station['attempt_uuid'],
                'user_id'       => $mock['user_id'],
                'case_post_id'  => $station['case_post_id'],
                'snapshot_json' => $station['station_snapshot_json'],
                'transcript_json' => $station['transcript_json'],
            ];

            $result = (new ResponsesFeedbackClient())->generate_feedback($attempt);

            if (is_wp_error($result)) {
                $data = $result->get_error_data();
                Logger::error('Mock feedback failed: ' . $result->get_error_code());

                if (is_array($data) && !empty($data['non_retryable'])) {
                    $this->mark_processing_issue(
                        $mock_uuid,
                        __('Full Mock SCA results are queued. Configure the OpenAI feedback service before running mock grading.', 'meddigest-ai-sca'),
                        'requires_openai_configuration'
                    );
                    return;
                }

                $retry_count = $this->retry_count($mock) + 1;

                if ($retry_count > 3) {
                    $this->mark_processing_issue(
                        $mock_uuid,
                        __('Full Mock SCA results failed after retrying. Please contact support.', 'meddigest-ai-sca'),
                        'failed'
                    );
                    return;
                }

                $this->update_station(
                    $mock_uuid,
                    absint($station['station_number']),
                    [
                        'grade_status' => 'retrying',
                    ]
                );

                (new MockLaunchService())->update_mock(
                    $mock_uuid,
                    [
                        'results_json' => wp_json_encode(
                            [
                                'status'      => 'retrying',
                                'retry_count' => $retry_count,
                                'message'     => __('Full Mock SCA grading is retrying after a temporary error.', 'meddigest-ai-sca'),
                            ]
                        ),
                    ]
                );

                $this->schedule_retry($mock_uuid);
                return;
            }

            $feedback = isset($result['feedback']) && is_array($result['feedback']) ? $result['feedback'] : [];

            $this->update_station(
                $mock_uuid,
                absint($station['station_number']),
                [
                    'grade_status'  => 'completed',
                    'feedback_json' => wp_json_encode($feedback),
                    'ended_at'      => $station['hard_stop_at'],
                ]
            );
        }

        $this->complete($mock_uuid);
    }

    /**
     * Owner-safe results response. Does not trigger OpenAI calls.
     *
     * @param int    $user_id   User ID.
     * @param string $mock_uuid Mock UUID.
     */
    public function results($user_id, $mock_uuid)
    {
        $mock = (new MockLaunchService())->get_owned_mock($user_id, $mock_uuid);

        if (is_wp_error($mock)) {
            return $mock;
        }

        $results = !empty($mock['results_json']) ? json_decode($mock['results_json'], true) : [];
        $results = is_array($results) ? $results : [];

        return [
            'mock_uuid'   => $mock['mock_uuid'],
            'status'      => $mock['status'],
            'phase'       => $mock['current_phase'],
            'results'     => $results,
            'run_url'     => home_url('/sca-ai/mock/' . $mock['mock_uuid'] . '/run/'),
            'results_url' => home_url('/sca-ai/mock/' . $mock['mock_uuid'] . '/results/'),
        ];
    }

    /**
     * Complete aggregate result.
     *
     * @param string $mock_uuid Mock UUID.
     */
    private function complete($mock_uuid)
    {
        $stations = (new MockRunner())->get_stations($mock_uuid);
        $summary  = [
            'status'          => 'completed',
            'completed_at'    => Clock::mysql_utc(),
            'station_count'   => count($stations),
            'domain_grades'   => [
                'dgd' => [],
                'cmc' => [],
                'rto' => [],
            ],
            'stations'        => [],
        ];

        foreach ($stations as $station) {
            $feedback = !empty($station['feedback_json']) ? json_decode($station['feedback_json'], true) : [];
            $feedback = is_array($feedback) ? $feedback : [];
            $snapshot = !empty($station['station_snapshot_json']) ? json_decode($station['station_snapshot_json'], true) : [];
            $snapshot = is_array($snapshot) ? $snapshot : [];

            foreach (['dgd', 'cmc', 'rto'] as $domain) {
                $key   = $domain . '_grade';
                $grade = sanitize_key($feedback[$key] ?? 'not_assessable');

                if (!isset($summary['domain_grades'][$domain][$grade])) {
                    $summary['domain_grades'][$domain][$grade] = 0;
                }

                $summary['domain_grades'][$domain][$grade]++;
            }

            $summary['stations'][] = [
                'station_number'   => absint($station['station_number']),
                'case_title'       => $snapshot['title'] ?? '',
                'practice_verdict' => $feedback['practice_verdict'] ?? '',
                'dgd_grade'        => $feedback['dgd_grade'] ?? '',
                'cmc_grade'        => $feedback['cmc_grade'] ?? '',
                'rto_grade'        => $feedback['rto_grade'] ?? '',
            ];
        }

        (new MockLaunchService())->update_mock(
            $mock_uuid,
            [
                'status'        => MockLaunchService::STATUS_COMPLETED,
                'current_phase' => 'results',
                'results_json'  => wp_json_encode($summary),
            ]
        );
    }

    /**
     * Mark processing issue.
     *
     * @param string $mock_uuid Mock UUID.
     * @param string $message   Message.
     * @param string $status    Status.
     */
    private function mark_processing_issue($mock_uuid, $message, $status)
    {
        (new MockLaunchService())->update_mock(
            $mock_uuid,
            [
                'status'        => MockLaunchService::STATUS_PROCESSING,
                'current_phase' => 'processing',
                'results_json'  => wp_json_encode(
                    [
                        'status'  => sanitize_key($status),
                        'message' => wp_strip_all_tags($message),
                    ]
                ),
            ]
        );
    }

    /**
     * Current retry count from mock results JSON.
     *
     * @param array $mock Mock run.
     */
    private function retry_count(array $mock)
    {
        $results = !empty($mock['results_json']) ? json_decode($mock['results_json'], true) : [];
        $results = is_array($results) ? $results : [];

        return absint($results['retry_count'] ?? 0);
    }

    /**
     * Update one station.
     *
     * @param string $mock_uuid      Mock UUID.
     * @param int    $station_number Station number.
     * @param array  $data           Data.
     */
    private function update_station($mock_uuid, $station_number, array $data)
    {
        global $wpdb;

        $tables             = Schema::tables();
        $data['updated_at'] = Clock::mysql_utc();

        $wpdb->update(
            $tables['mock_stations'],
            $data,
            [
                'mock_uuid'      => $mock_uuid,
                'station_number' => absint($station_number),
            ]
        );
    }

    /**
     * Schedule retry.
     *
     * @param string $mock_uuid Mock UUID.
     */
    private function schedule_retry($mock_uuid)
    {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + 60, self::HOOK, [$mock_uuid], 'meddigest-ai-sca');
            return;
        }

        wp_schedule_single_event(time() + 60, self::HOOK, [$mock_uuid]);
    }
}
