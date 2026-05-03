<?php
namespace MedDigest\AiSca\Mock;

use MedDigest\AiSca\Cases\CaseSnapshotService;

if (!defined('ABSPATH')) {
    exit;
}

final class MockAllocationService
{
    /**
     * Allocate exactly 12 unique mock cases, one per configured group.
     */
    public function allocate()
    {
        $coverage = (new MockCoverageService())->coverage_report();

        if (12 !== absint($coverage['configured_count'])) {
            return new \WP_Error(
                'meddigest_ai_sca_mock_groups_not_configured',
                __('The 12 Clinical Experience Group term IDs must be configured before Full Mock SCA can launch.', 'meddigest-ai-sca'),
                ['status' => 409, 'coverage' => $coverage]
            );
        }

        if (empty($coverage['ready'])) {
            return new \WP_Error(
                'meddigest_ai_sca_mock_coverage_missing',
                __('Full Mock SCA is not available because at least one Clinical Experience Group has no approved mock-ready case.', 'meddigest-ai-sca'),
                ['status' => 409, 'coverage' => $coverage]
            );
        }

        $used        = [];
        $allocations = [];
        $station     = 1;

        foreach ($coverage['groups'] as $group) {
            $eligible = array_values(array_diff(array_map('absint', $group['eligible_case_ids']), $used));

            if (empty($eligible)) {
                return new \WP_Error(
                    'meddigest_ai_sca_mock_unique_case_missing',
                    __('Full Mock SCA requires 12 unique cases. At least one configured group only has cases already selected for another station.', 'meddigest-ai-sca'),
                    ['status' => 409, 'coverage' => $coverage]
                );
            }

            shuffle($eligible);

            $case_id = absint($eligible[0]);
            $used[]  = $case_id;

            $snapshot      = (new CaseSnapshotService())->build_snapshot($case_id);
            $allocations[] = [
                'station_number'             => $station,
                'case_post_id'               => $case_id,
                'mock_primary_group_term_id' => absint($group['group_id']),
                'group_label'                => $group['label'],
                'snapshot'                   => $snapshot,
            ];

            $station++;
        }

        if (12 !== count($allocations)) {
            return new \WP_Error(
                'meddigest_ai_sca_mock_allocation_incomplete',
                __('Full Mock SCA could not allocate exactly 12 cases.', 'meddigest-ai-sca'),
                ['status' => 409, 'coverage' => $coverage]
            );
        }

        return $allocations;
    }
}
