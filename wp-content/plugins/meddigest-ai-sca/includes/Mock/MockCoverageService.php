<?php
namespace MedDigest\AiSca\Mock;

use MedDigest\AiSca\Database\Schema;

if (!defined('ABSPATH')) {
    exit;
}

final class MockCoverageService
{
    public const OPTION_GROUP_IDS = 'meddigest_ai_sca_mock_group_term_ids';

    /**
     * Sanitize configured group IDs.
     *
     * @param mixed $value Raw option.
     */
    public static function sanitize_group_ids($value)
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value);
        }

        if (!is_array($value)) {
            $value = [];
        }

        $ids = [];

        foreach ($value as $id) {
            $id = absint($id);

            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return array_slice($ids, 0, 12);
    }

    /**
     * Configured required group IDs.
     */
    public function required_group_ids()
    {
        $ids = self::sanitize_group_ids(get_option(self::OPTION_GROUP_IDS, []));

        return self::sanitize_group_ids(apply_filters('meddigest_ai_sca_mock_required_group_term_ids', $ids));
    }

    /**
     * Full coverage report.
     */
    public function coverage_report()
    {
        $required = array_values(array_map('absint', $this->required_group_ids()));
        $groups   = [];

        foreach ($required as $group_id) {
            if ($group_id <= 0) {
                continue;
            }

            $eligible = $this->eligible_case_ids_for_group($group_id);
            $groups[] = [
                'group_id'          => $group_id,
                'label'             => $this->group_label($group_id),
                'eligible_count'    => count($eligible),
                'eligible_case_ids' => $eligible,
                'has_coverage'      => !empty($eligible),
            ];
        }

        $missing = array_values(
            array_filter(
                $groups,
                static function ($group) {
                    return empty($group['has_coverage']);
                }
            )
        );

        return [
            'configured_count' => count($required),
            'required_count'   => 12,
            'groups'           => $groups,
            'missing'          => $missing,
            'ready'            => 12 === count($required) && empty($missing),
        ];
    }

    /**
     * Eligible case IDs for a configured group.
     *
     * @param int $group_id Group term ID.
     */
    public function eligible_case_ids_for_group($group_id)
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return [];
        }

        $tables = Schema::tables();

        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT c.case_post_id
                FROM {$tables['case_config']} c
                INNER JOIN {$wpdb->posts} p ON p.ID = c.case_post_id
                WHERE c.enabled = 1
                    AND c.mock_pool_enabled = 1
                    AND c.mock_ready_status = %s
                    AND c.mock_primary_group_term_id = %d
                    AND p.post_status = %s
                ORDER BY c.updated_at DESC",
                'approved',
                $group_id,
                'publish'
            )
        );

        return array_values(array_map('absint', $rows ?: []));
    }

    /**
     * Human-readable group label.
     *
     * @param int $group_id Group term ID.
     */
    private function group_label($group_id)
    {
        $term = function_exists('get_term') ? get_term($group_id) : null;

        if ($term && !is_wp_error($term) && !empty($term->name)) {
            return $term->name;
        }

        return sprintf(__('Clinical Experience Group %d', 'meddigest-ai-sca'), $group_id);
    }
}
