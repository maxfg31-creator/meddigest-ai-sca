<?php
/**
 * Case admin filters.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

use MedDigest\AiSca\Cases\CasePostTypeIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseFilters
{
    public function register()
    {
        add_action('restrict_manage_posts', [$this, 'render_filters']);
        add_action('pre_get_posts', [$this, 'apply_filters']);
    }

    /**
     * Render admin filters for case list screens.
     *
     * @param string $post_type Post type.
     */
    public function render_filters($post_type)
    {
        if (!CasePostTypeIntegration::is_case_post_type($post_type)) {
            return;
        }

        $ai_enabled = isset($_GET['mdsca_ai_enabled']) ? sanitize_key(wp_unslash($_GET['mdsca_ai_enabled'])) : '';
        $mode       = isset($_GET['mdsca_consultation_mode']) ? sanitize_key(wp_unslash($_GET['mdsca_consultation_mode'])) : '';
        $mock_pool  = isset($_GET['mdsca_mock_pool_enabled']) ? sanitize_key(wp_unslash($_GET['mdsca_mock_pool_enabled'])) : '';
        $ready      = isset($_GET['mdsca_mock_ready_status']) ? sanitize_key(wp_unslash($_GET['mdsca_mock_ready_status'])) : '';

        $this->select('mdsca_ai_enabled', $ai_enabled, __('AI Enabled', 'meddigest-ai-sca'), ['1' => __('Yes', 'meddigest-ai-sca'), '0' => __('No', 'meddigest-ai-sca')]);
        $this->select('mdsca_consultation_mode', $mode, __('Mode', 'meddigest-ai-sca'), ['video' => __('Video', 'meddigest-ai-sca'), 'telephone' => __('Telephone', 'meddigest-ai-sca')]);
        $this->select('mdsca_mock_pool_enabled', $mock_pool, __('Mock Pool', 'meddigest-ai-sca'), ['1' => __('Yes', 'meddigest-ai-sca'), '0' => __('No', 'meddigest-ai-sca')]);
        $this->select('mdsca_mock_ready_status', $ready, __('Mock Ready', 'meddigest-ai-sca'), ['draft' => __('Draft', 'meddigest-ai-sca'), 'review' => __('Needs Review', 'meddigest-ai-sca'), 'approved' => __('Approved', 'meddigest-ai-sca')]);
    }

    /**
     * Apply meta filters.
     *
     * @param \WP_Query $query Query.
     */
    public function apply_filters($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');

        if (!CasePostTypeIntegration::is_case_post_type($post_type)) {
            return;
        }

        $meta_query = (array) $query->get('meta_query');

        $filters = [
            'mdsca_ai_enabled'         => '=',
            'mdsca_consultation_mode'  => '=',
            'mdsca_mock_pool_enabled'  => '=',
            'mdsca_mock_ready_status'  => '=',
        ];

        foreach ($filters as $field => $compare) {
            if (!isset($_GET[$field]) || '' === $_GET[$field]) {
                continue;
            }

            $meta_query[] = [
                'key'     => $field,
                'value'   => sanitize_key(wp_unslash($_GET[$field])),
                'compare' => $compare,
            ];
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Render a select filter.
     *
     * @param string $name        Field name.
     * @param string $selected    Selected value.
     * @param string $placeholder Placeholder.
     * @param array  $options     Options.
     */
    private function select($name, $selected, $placeholder, array $options)
    {
        printf('<select name="%1$s"><option value="">%2$s</option>', esc_attr($name), esc_html($placeholder));

        foreach ($options as $value => $label) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($value),
                selected($selected, $value, false),
                esc_html($label)
            );
        }

        echo '</select>';
    }
}
