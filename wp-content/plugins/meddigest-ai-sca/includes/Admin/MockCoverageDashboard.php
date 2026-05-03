<?php
/**
 * Mock coverage dashboard.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

use MedDigest\AiSca\Mock\MockCoverageService;

if (!defined('ABSPATH')) {
    exit;
}

final class MockCoverageDashboard
{
    public function register()
    {
    }

    /**
     * Render coverage panel.
     */
    public function render()
    {
        $coverage = (new MockCoverageService())->coverage_report();

        echo '<h2>' . esc_html__('Full Mock Coverage', 'meddigest-ai-sca') . '</h2>';
        echo '<p>' . esc_html__('Full Mock SCA needs exactly 12 configured Clinical Experience Group term IDs, each with at least one published, AI-enabled, mock-pool-approved case.', 'meddigest-ai-sca') . '</p>';

        if (12 !== absint($coverage['configured_count'])) {
            printf(
                '<div class="notice notice-warning inline"><p>%s</p></div>',
                esc_html__('Configure exactly 12 Clinical Experience Group term IDs before launching Full Mock SCA.', 'meddigest-ai-sca')
            );
        } elseif (empty($coverage['ready'])) {
            printf(
                '<div class="notice notice-warning inline"><p>%s</p></div>',
                esc_html__('Coverage is incomplete. Full Mock launch will be blocked until every configured group has coverage.', 'meddigest-ai-sca')
            );
        } else {
            printf(
                '<div class="notice notice-success inline"><p>%s</p></div>',
                esc_html__('Coverage is ready for Full Mock launch.', 'meddigest-ai-sca')
            );
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Group', 'meddigest-ai-sca') . '</th>';
        echo '<th>' . esc_html__('Term ID', 'meddigest-ai-sca') . '</th>';
        echo '<th>' . esc_html__('Eligible Cases', 'meddigest-ai-sca') . '</th>';
        echo '<th>' . esc_html__('Status', 'meddigest-ai-sca') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($coverage['groups'])) {
            echo '<tr><td colspan="4">' . esc_html__('No groups configured yet.', 'meddigest-ai-sca') . '</td></tr>';
        }

        foreach ($coverage['groups'] as $group) {
            printf(
                '<tr><td>%1$s</td><td>%2$d</td><td>%3$d</td><td>%4$s</td></tr>',
                esc_html($group['label']),
                absint($group['group_id']),
                absint($group['eligible_count']),
                !empty($group['has_coverage']) ? esc_html__('Ready', 'meddigest-ai-sca') : esc_html__('Missing', 'meddigest-ai-sca')
            );
        }

        echo '</tbody></table>';
    }
}
