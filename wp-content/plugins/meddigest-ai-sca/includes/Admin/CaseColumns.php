<?php
/**
 * Case admin columns.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

use MedDigest\AiSca\Cases\CaseConfigRepository;
use MedDigest\AiSca\Cases\CasePostTypeIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseColumns
{
    public function register()
    {
        foreach (CasePostTypeIntegration::case_post_types() as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'add_columns']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'render_column'], 10, 2);
        }
    }

    /**
     * Add AI columns.
     *
     * @param array $columns Columns.
     */
    public function add_columns($columns)
    {
        $columns['mdsca_ai_enabled']       = __('AI Enabled', 'meddigest-ai-sca');
        $columns['mdsca_ai_mode']          = __('AI Mode', 'meddigest-ai-sca');
        $columns['mdsca_mock_pool']        = __('Mock Pool', 'meddigest-ai-sca');
        $columns['mdsca_mock_ready']       = __('Mock Ready', 'meddigest-ai-sca');
        $columns['mdsca_ai_reviewed_date'] = __('AI Reviewed', 'meddigest-ai-sca');

        return $columns;
    }

    /**
     * Render AI columns.
     *
     * @param string $column  Column.
     * @param int    $post_id Post ID.
     */
    public function render_column($column, $post_id)
    {
        if (0 !== strpos($column, 'mdsca_')) {
            return;
        }

        $config = (new CaseConfigRepository())->get_by_case_post_id($post_id);

        if (!$config) {
            echo '&mdash;';
            return;
        }

        if ('mdsca_ai_enabled' === $column) {
            echo !empty($config['enabled']) ? esc_html__('Yes', 'meddigest-ai-sca') : esc_html__('No', 'meddigest-ai-sca');
        } elseif ('mdsca_ai_mode' === $column) {
            echo esc_html($config['mode'] ?? '');
        } elseif ('mdsca_mock_pool' === $column) {
            echo !empty($config['mock_pool_enabled']) ? esc_html__('Yes', 'meddigest-ai-sca') : esc_html__('No', 'meddigest-ai-sca');
        } elseif ('mdsca_mock_ready' === $column) {
            echo esc_html($config['mock_ready_status'] ?? '');
        } elseif ('mdsca_ai_reviewed_date' === $column) {
            echo !empty($config['reviewed_at']) ? esc_html($config['reviewed_at']) : '&mdash;';
        }
    }
}
