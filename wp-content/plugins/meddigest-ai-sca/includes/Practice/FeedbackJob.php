<?php
namespace MedDigest\AiSca\Practice;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\OpenAI\ResponsesFeedbackClient;
use MedDigest\AiSca\Support\Clock;
use MedDigest\AiSca\Support\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class FeedbackJob
{
    public const HOOK = 'meddigest_ai_sca_generate_feedback';
    private const MAX_RETRIES = 3;

    /**
     * Register job runner.
     */
    public function register()
    {
        add_action(self::HOOK, [$this, 'run'], 10, 1);
    }

    /**
     * Queue feedback processing.
     *
     * @param string $attempt_uuid Attempt UUID.
     */
    public function queue($attempt_uuid)
    {
        $this->ensure_feedback_row($attempt_uuid, 'pending');

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [$attempt_uuid], 'meddigest-ai-sca');
            return;
        }

        wp_schedule_single_event(time() + 5, self::HOOK, [$attempt_uuid]);
    }

    /**
     * Generate feedback or retry transient failures.
     *
     * @param string $attempt_uuid Attempt UUID.
     */
    public function run($attempt_uuid)
    {
        $this->ensure_feedback_row($attempt_uuid, 'processing');
        $this->update_feedback($attempt_uuid, ['processing_status' => 'processing']);

        $attempt = (new StationAttemptService())->get_attempt($attempt_uuid);

        if (!$attempt) {
            $this->update_feedback(
                $attempt_uuid,
                [
                    'processing_status' => 'failed',
                    'practice_verdict'  => __('Station attempt was not found for feedback generation.', 'meddigest-ai-sca'),
                ]
            );

            return;
        }

        $result = (new ResponsesFeedbackClient())->generate_feedback($attempt);

        if (is_wp_error($result)) {
            $this->handle_generation_error($attempt_uuid, $result);
            return;
        }

        $feedback = isset($result['feedback']) && is_array($result['feedback']) ? $result['feedback'] : [];
        $raw      = isset($result['raw']) && is_array($result['raw']) ? $result['raw'] : [];

        $this->update_feedback(
            $attempt_uuid,
            [
                'processing_status'      => 'completed',
                'practice_verdict'       => sanitize_textarea_field($feedback['practice_verdict'] ?? ''),
                'dgd_grade'              => sanitize_key($feedback['dgd_grade'] ?? ''),
                'cmc_grade'              => sanitize_key($feedback['cmc_grade'] ?? ''),
                'rto_grade'              => sanitize_key($feedback['rto_grade'] ?? ''),
                'global_json'            => wp_json_encode($feedback['global'] ?? []),
                'full_feedback_json'     => wp_json_encode($feedback),
                'rendered_snapshot_json' => wp_json_encode(['provider' => $raw]),
            ]
        );

        (new StationAttemptService())->update_attempt(
            $attempt_uuid,
            [
                'status' => StationAttemptService::STATUS_COMPLETED,
            ]
        );
    }

    /**
     * Get feedback by attempt UUID.
     *
     * @param string $attempt_uuid Attempt UUID.
     */
    public function get_feedback($attempt_uuid)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['feedback']} WHERE attempt_uuid = %s LIMIT 1",
                $attempt_uuid
            ),
            ARRAY_A
        );
    }

    /**
     * Ensure feedback row exists.
     *
     * @param string $attempt_uuid Attempt UUID.
     * @param string $status       Status.
     */
    private function ensure_feedback_row($attempt_uuid, $status)
    {
        global $wpdb;

        if ($this->get_feedback($attempt_uuid)) {
            return;
        }

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();

        $wpdb->insert(
            $tables['feedback'],
            [
                'attempt_uuid'      => $attempt_uuid,
                'processing_status' => sanitize_key($status),
                'created_at'        => $now,
                'updated_at'        => $now,
            ]
        );
    }

    /**
     * Handle feedback generation error.
     *
     * @param string    $attempt_uuid Attempt UUID.
     * @param \WP_Error $error        Error.
     */
    private function handle_generation_error($attempt_uuid, \WP_Error $error)
    {
        $feedback      = $this->get_feedback($attempt_uuid);
        $retry_count   = $feedback ? absint($feedback['retry_count']) : 0;
        $error_data    = $error->get_error_data();
        $non_retryable = is_array($error_data) && !empty($error_data['non_retryable']);

        Logger::error('Feedback generation failed: ' . $error->get_error_code());

        if ($non_retryable) {
            $this->update_feedback(
                $attempt_uuid,
                [
                    'processing_status' => 'requires_openai_configuration',
                    'practice_verdict'  => __('Feedback generation is queued. Configure the OpenAI feedback service before running live grading.', 'meddigest-ai-sca'),
                    'retry_count'       => $retry_count,
                ]
            );

            return;
        }

        $retry_count++;

        if ($retry_count <= self::MAX_RETRIES) {
            $this->update_feedback(
                $attempt_uuid,
                [
                    'processing_status' => 'retrying',
                    'retry_count'       => $retry_count,
                    'practice_verdict'  => __('Feedback generation is retrying after a temporary error.', 'meddigest-ai-sca'),
                ]
            );

            $this->schedule_retry($attempt_uuid, $retry_count);
            return;
        }

        $this->update_feedback(
            $attempt_uuid,
            [
                'processing_status' => 'failed',
                'retry_count'       => $retry_count,
                'practice_verdict'  => __('Feedback generation failed after retrying. Please contact support.', 'meddigest-ai-sca'),
            ]
        );
    }

    /**
     * Schedule retry.
     *
     * @param string $attempt_uuid Attempt UUID.
     * @param int    $retry_count  Retry count.
     */
    private function schedule_retry($attempt_uuid, $retry_count)
    {
        $delay = min(300, 30 * max(1, absint($retry_count)));

        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + $delay, self::HOOK, [$attempt_uuid], 'meddigest-ai-sca');
            return;
        }

        wp_schedule_single_event(time() + $delay, self::HOOK, [$attempt_uuid]);
    }

    /**
     * Update feedback row.
     *
     * @param string $attempt_uuid Attempt UUID.
     * @param array  $data         Data.
     */
    private function update_feedback($attempt_uuid, array $data)
    {
        global $wpdb;

        $tables             = Schema::tables();
        $data['updated_at'] = Clock::mysql_utc();

        $wpdb->update($tables['feedback'], $data, ['attempt_uuid' => $attempt_uuid]);
    }
}
