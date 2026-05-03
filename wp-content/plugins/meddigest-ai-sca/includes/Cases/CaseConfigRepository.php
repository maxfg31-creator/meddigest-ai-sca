<?php
/**
 * Case config repository.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Cases;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseConfigRepository
{
    /**
     * Get case config by post ID.
     *
     * @param int $case_post_id Case post ID.
     */
    public function get_by_case_post_id($case_post_id)
    {
        global $wpdb;

        $case_post_id = absint($case_post_id);

        if ($case_post_id <= 0) {
            return null;
        }

        $tables = Schema::tables();
        $row    = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['case_config']} WHERE case_post_id = %d LIMIT 1",
                $case_post_id
            ),
            ARRAY_A
        );

        if ($row) {
            return $this->normalize_row($row);
        }

        return $this->get_from_post_meta($case_post_id);
    }

    /**
     * Whether a case is AI-enabled.
     *
     * @param int $case_post_id Case post ID.
     */
    public function is_ai_enabled($case_post_id)
    {
        $config = $this->get_by_case_post_id($case_post_id);

        return !empty($config['enabled']);
    }

    /**
     * Upsert normalized config.
     *
     * @param int   $case_post_id Case post ID.
     * @param array $config       Config.
     */
    public function upsert($case_post_id, array $config)
    {
        global $wpdb;

        $case_post_id = absint($case_post_id);

        if ($case_post_id <= 0) {
            return false;
        }

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();
        $data   = [
            'case_post_id'                    => $case_post_id,
            'enabled'                         => !empty($config['enabled']) ? 1 : 0,
            'mode'                            => sanitize_key($config['mode'] ?? 'video'),
            'first_speaker'                   => sanitize_key($config['first_speaker'] ?? 'patient'),
            'voice_id'                        => sanitize_text_field($config['voice_id'] ?? ''),
            'ai_version'                      => sanitize_text_field($config['ai_version'] ?? ''),
            'reviewed_by'                     => absint($config['reviewed_by'] ?? 0),
            'reviewed_at'                     => $this->sanitize_datetime($config['reviewed_at'] ?? ''),
            'doctor_brief_override'           => wp_kses_post($config['doctor_brief_override'] ?? ''),
            'pre_start_instructions_override' => wp_kses_post($config['pre_start_instructions_override'] ?? ''),
            'patient_profile_json'            => $this->sanitize_json_textarea($config['patient_profile_json'] ?? ''),
            'internal_notes_json'             => $this->sanitize_json_textarea($config['internal_notes_json'] ?? ''),
            'mock_pool_enabled'               => !empty($config['mock_pool_enabled']) ? 1 : 0,
            'mock_ready_status'               => sanitize_key($config['mock_ready_status'] ?? 'draft'),
            'mock_primary_group_term_id'      => absint($config['mock_primary_group_term_id'] ?? 0),
            'updated_at'                      => $now,
        ];

        $existing = $this->get_raw_by_case_post_id($case_post_id);

        if ($existing) {
            $updated = false !== $wpdb->update(
                $tables['case_config'],
                $data,
                ['case_post_id' => $case_post_id]
            );

            return $updated ? absint($existing['id']) : false;
        }

        $data['created_at'] = $now;

        if (false === $wpdb->insert($tables['case_config'], $data)) {
            return false;
        }

        return absint($wpdb->insert_id);
    }

    /**
     * Get raw row.
     *
     * @param int $case_post_id Case post ID.
     */
    private function get_raw_by_case_post_id($case_post_id)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['case_config']} WHERE case_post_id = %d LIMIT 1",
                $case_post_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get fallback config from post meta/ACF values.
     *
     * @param int $case_post_id Case post ID.
     */
    private function get_from_post_meta($case_post_id)
    {
        $config = [];

        foreach ($this->field_names() as $field_name) {
            $value = function_exists('get_field') ? get_field($field_name, $case_post_id) : null;

            if (null === $value || false === $value || '' === $value) {
                $value = get_post_meta($case_post_id, $field_name, true);
            }

            $config[$field_name] = $value;
        }

        return [
            'case_post_id'                    => $case_post_id,
            'enabled'                         => !empty($config['mdsca_ai_enabled']),
            'mode'                            => sanitize_key($config['mdsca_consultation_mode'] ?: 'video'),
            'first_speaker'                   => sanitize_key($config['mdsca_first_speaker'] ?: 'patient'),
            'voice_id'                        => sanitize_text_field($config['mdsca_default_voice'] ?: ''),
            'ai_version'                      => sanitize_text_field($config['mdsca_ai_version'] ?: ''),
            'reviewed_by'                     => absint($config['mdsca_reviewed_by'] ?: 0),
            'reviewed_at'                     => $this->sanitize_datetime($config['mdsca_reviewed_date'] ?: ''),
            'doctor_brief_override'           => wp_kses_post($config['mdsca_doctor_brief_override'] ?: ''),
            'pre_start_instructions_override' => wp_kses_post($config['mdsca_pre_start_instructions_override'] ?: ''),
            'patient_profile_json'            => $this->sanitize_json_textarea($config['mdsca_patient_profile_json'] ?: ''),
            'internal_notes_json'             => $this->sanitize_json_textarea($config['mdsca_internal_notes_json'] ?: ''),
            'mock_pool_enabled'               => !empty($config['mdsca_mock_pool_enabled']),
            'mock_ready_status'               => sanitize_key($config['mdsca_mock_ready_status'] ?: 'draft'),
            'mock_primary_group_term_id'      => absint($config['mdsca_mock_primary_group_term_id'] ?: 0),
        ];
    }

    /**
     * Normalize DB row.
     *
     * @param array $row Row.
     */
    private function normalize_row(array $row)
    {
        $row['case_post_id']               = absint($row['case_post_id']);
        $row['enabled']                    = !empty($row['enabled']);
        $row['mock_pool_enabled']          = !empty($row['mock_pool_enabled']);
        $row['mock_primary_group_term_id'] = absint($row['mock_primary_group_term_id']);
        $row['reviewed_by']                = absint($row['reviewed_by']);

        return $row;
    }

    /**
     * ACF/post-meta field names.
     */
    public function field_names()
    {
        return [
            'mdsca_ai_enabled',
            'mdsca_consultation_mode',
            'mdsca_first_speaker',
            'mdsca_ai_version',
            'mdsca_reviewed_by',
            'mdsca_reviewed_date',
            'mdsca_default_voice',
            'mdsca_doctor_brief_override',
            'mdsca_pre_start_instructions_override',
            'mdsca_patient_profile_json',
            'mdsca_hidden_facts_json',
            'mdsca_marking_items_json',
            'mdsca_internal_notes_json',
            'mdsca_mock_pool_enabled',
            'mdsca_mock_ready_status',
            'mdsca_mock_primary_group_term_id',
        ];
    }

    /**
     * Sanitize JSON textarea without exposing/decoding on frontend.
     *
     * @param mixed $value Value.
     */
    private function sanitize_json_textarea($value)
    {
        if (is_array($value) || is_object($value)) {
            return wp_json_encode($value);
        }

        return sanitize_textarea_field((string) $value);
    }

    /**
     * Sanitize nullable datetime.
     *
     * @param string $value Value.
     */
    private function sanitize_datetime($value)
    {
        $value = sanitize_text_field((string) $value);

        if ('' === $value) {
            return null;
        }

        $timestamp = strtotime($value);

        if (!$timestamp) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
