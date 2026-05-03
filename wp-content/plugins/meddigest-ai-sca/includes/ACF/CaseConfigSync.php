<?php
/**
 * ACF to case config sync.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\ACF;

use MedDigest\AiSca\Cases\CaseConfigRepository;
use MedDigest\AiSca\Cases\CaseFactRepository;
use MedDigest\AiSca\Cases\CaseMarkingRepository;
use MedDigest\AiSca\Cases\CasePostTypeIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseConfigSync
{
    public function register()
    {
        add_action('acf/save_post', [$this, 'sync_after_acf_save'], 20);
        add_action('save_post', [$this, 'sync_after_post_save'], 20, 2);
    }

    /**
     * Sync after ACF save.
     *
     * @param mixed $post_id Post ID.
     */
    public function sync_after_acf_save($post_id)
    {
        if (!is_numeric($post_id)) {
            return;
        }

        $this->sync(absint($post_id));
    }

    /**
     * Sync after post save fallback.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post.
     */
    public function sync_after_post_save($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!$post || !CasePostTypeIntegration::is_case_post_type($post->post_type)) {
            return;
        }

        $this->sync(absint($post_id));
    }

    /**
     * Sync ACF/post meta to normalized case config table.
     *
     * @param int $post_id Post ID.
     */
    private function sync($post_id)
    {
        $post_type = get_post_type($post_id);

        if (!$post_type || !CasePostTypeIntegration::is_case_post_type($post_type)) {
            return;
        }

        $repository = new CaseConfigRepository();
        $config     = [];

        foreach ($repository->field_names() as $field_name) {
            $value = function_exists('get_field') ? get_field($field_name, $post_id) : null;

            if (null === $value || false === $value || '' === $value) {
                $value = get_post_meta($post_id, $field_name, true);
            }

            $config[$field_name] = $value;
        }

        $case_config_id = $repository->upsert(
            $post_id,
            [
                'enabled'                         => !empty($config['mdsca_ai_enabled']),
                'mode'                            => $config['mdsca_consultation_mode'] ?? 'video',
                'first_speaker'                   => $config['mdsca_first_speaker'] ?? 'patient',
                'voice_id'                        => $config['mdsca_default_voice'] ?? '',
                'ai_version'                      => $config['mdsca_ai_version'] ?? '',
                'reviewed_by'                     => $config['mdsca_reviewed_by'] ?? 0,
                'reviewed_at'                     => $config['mdsca_reviewed_date'] ?? '',
                'doctor_brief_override'           => $config['mdsca_doctor_brief_override'] ?? '',
                'pre_start_instructions_override' => $config['mdsca_pre_start_instructions_override'] ?? '',
                'patient_profile_json'            => $config['mdsca_patient_profile_json'] ?? '',
                'internal_notes_json'             => $config['mdsca_internal_notes_json'] ?? '',
                'mock_pool_enabled'               => !empty($config['mdsca_mock_pool_enabled']),
                'mock_ready_status'               => $config['mdsca_mock_ready_status'] ?? 'draft',
                'mock_primary_group_term_id'      => $config['mdsca_mock_primary_group_term_id'] ?? 0,
            ]
        );

        if ($case_config_id) {
            (new CaseFactRepository())->replace_from_json($case_config_id, $config['mdsca_hidden_facts_json'] ?? '');
            (new CaseMarkingRepository())->replace_from_json($case_config_id, $config['mdsca_marking_items_json'] ?? '');
        }
    }
}
