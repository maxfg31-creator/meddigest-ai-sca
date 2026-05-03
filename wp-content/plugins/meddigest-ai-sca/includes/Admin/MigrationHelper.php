<?php
/**
 * Case AI field migration helper.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

use MedDigest\AiSca\Cases\CasePostTypeIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class MigrationHelper
{
    public function register()
    {
        add_action('admin_post_mdsca_migrate_case_ai_fields', [$this, 'handle']);
    }

    /**
     * Handle migration helper action.
     */
    public function handle()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to run this migration.', 'meddigest-ai-sca'));
        }

        check_admin_referer('mdsca_migrate_case_ai_fields');

        $updated = 0;

        foreach (CasePostTypeIntegration::case_post_types() as $post_type) {
            $posts = get_posts(
                [
                    'post_type'      => $post_type,
                    'post_status'    => ['publish', 'draft', 'private'],
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                ]
            );

            foreach ($posts as $post_id) {
                if ($this->prefill_post($post_id)) {
                    $updated++;
                }
            }
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'                 => 'meddigest-ai-sca',
                    'mdsca_migrated_cases' => $updated,
                ],
                admin_url('options-general.php')
            )
        );
        exit;
    }

    /**
     * Prefill one post from known custom fields.
     *
     * @param int $post_id Post ID.
     */
    private function prefill_post($post_id)
    {
        $updated = false;

        if (!get_post_meta($post_id, 'mdsca_doctor_brief_override', true)) {
            $doctor_notes = $this->first_meta($post_id, ['doctor_notes', 'sca_doctor_notes', 'meddigest_doctor_notes']);
            if ($doctor_notes) {
                update_post_meta($post_id, 'mdsca_doctor_brief_override', $this->strip_hidden_fragments($doctor_notes));
                $updated = true;
            }
        }

        if (!get_post_meta($post_id, 'mdsca_internal_notes_json', true)) {
            $internal = [
                'patient_notes'        => $this->strip_hidden_fragments($this->first_meta($post_id, ['patient_notes', 'sca_patient_notes', 'meddigest_patient_notes'])),
                'marking_scheme'       => $this->strip_hidden_fragments($this->first_meta($post_id, ['marking_scheme', 'sca_marking_scheme', 'meddigest_marking_scheme'])),
                'example_consultation' => $this->strip_hidden_fragments($this->first_meta($post_id, ['example_consultation', 'sca_example_consultation', 'meddigest_example_consultation'])),
            ];

            if (array_filter($internal)) {
                update_post_meta($post_id, 'mdsca_internal_notes_json', wp_json_encode($internal));
                update_post_meta($post_id, 'mdsca_mock_ready_status', 'review');
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Get first non-empty meta value.
     *
     * @param int   $post_id Post ID.
     * @param array $keys    Keys.
     */
    private function first_meta($post_id, array $keys)
    {
        foreach ($keys as $key) {
            $value = get_post_meta($post_id, $key, true);

            if ($value) {
                return (string) $value;
            }
        }

        return '';
    }

    /**
     * Strip shortcodes and hidden fragments from migrated text.
     *
     * @param string $value Value.
     */
    private function strip_hidden_fragments($value)
    {
        $value = strip_shortcodes((string) $value);
        $value = preg_replace('/\[\/?mepr[^\]]*\]/i', '', $value);

        return sanitize_textarea_field($value);
    }
}
