<?php
/**
 * ACF case field groups.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\ACF;

use MedDigest\AiSca\Cases\CasePostTypeIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseFieldGroups
{
    /**
     * Register ACF hooks.
     */
    public function register()
    {
        add_action('acf/init', [$this, 'register_field_groups']);
    }

    /**
     * Register local ACF field groups.
     */
    public function register_field_groups()
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        $locations = $this->locations();

        if (empty($locations)) {
            return;
        }

        acf_add_local_field_group(
            [
                'key'                   => 'group_mdsca_ai_case_settings',
                'title'                 => __('MedDigest AI SCA Settings', 'meddigest-ai-sca'),
                'fields'                => $this->fields(),
                'location'              => $locations,
                'menu_order'            => 30,
                'position'              => 'normal',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'active'                => true,
            ]
        );
    }

    /**
     * Build post type locations.
     */
    private function locations()
    {
        $locations = [];

        foreach (CasePostTypeIntegration::case_post_types() as $post_type) {
            $locations[] = [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => $post_type,
                ],
            ];
        }

        return $locations;
    }

    /**
     * Field definitions.
     */
    private function fields()
    {
        return [
            [
                'key'           => 'field_mdsca_ai_enabled',
                'label'         => __('Enable AI', 'meddigest-ai-sca'),
                'name'          => 'mdsca_ai_enabled',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
            ],
            [
                'key'           => 'field_mdsca_consultation_mode',
                'label'         => __('Consultation Mode', 'meddigest-ai-sca'),
                'name'          => 'mdsca_consultation_mode',
                'type'          => 'select',
                'choices'       => [
                    'video'     => __('Video', 'meddigest-ai-sca'),
                    'telephone' => __('Telephone', 'meddigest-ai-sca'),
                ],
                'default_value' => 'video',
                'ui'            => 1,
            ],
            [
                'key'           => 'field_mdsca_first_speaker',
                'label'         => __('First Speaker', 'meddigest-ai-sca'),
                'name'          => 'mdsca_first_speaker',
                'type'          => 'select',
                'choices'       => [
                    'patient' => __('Patient', 'meddigest-ai-sca'),
                    'doctor'  => __('Doctor', 'meddigest-ai-sca'),
                ],
                'default_value' => 'patient',
                'ui'            => 1,
            ],
            [
                'key'   => 'field_mdsca_ai_version',
                'label' => __('AI Version', 'meddigest-ai-sca'),
                'name'  => 'mdsca_ai_version',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_mdsca_reviewed_by',
                'label' => __('Reviewed By User ID', 'meddigest-ai-sca'),
                'name'  => 'mdsca_reviewed_by',
                'type'  => 'number',
                'min'   => 0,
                'step'  => 1,
            ],
            [
                'key'          => 'field_mdsca_reviewed_date',
                'label'        => __('Reviewed Date', 'meddigest-ai-sca'),
                'name'         => 'mdsca_reviewed_date',
                'type'         => 'date_picker',
                'display_format' => 'Y-m-d',
                'return_format'  => 'Y-m-d',
            ],
            [
                'key'   => 'field_mdsca_default_voice',
                'label' => __('Default Voice', 'meddigest-ai-sca'),
                'name'  => 'mdsca_default_voice',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_mdsca_doctor_brief_override',
                'label' => __('Doctor Brief Override', 'meddigest-ai-sca'),
                'name'  => 'mdsca_doctor_brief_override',
                'type'  => 'textarea',
                'rows'  => 5,
            ],
            [
                'key'   => 'field_mdsca_pre_start_instructions_override',
                'label' => __('Pre-start Instructions Override', 'meddigest-ai-sca'),
                'name'  => 'mdsca_pre_start_instructions_override',
                'type'  => 'textarea',
                'rows'  => 4,
            ],
            [
                'key'          => 'field_mdsca_patient_profile_json',
                'label'        => __('Patient Profile JSON', 'meddigest-ai-sca'),
                'name'         => 'mdsca_patient_profile_json',
                'type'         => 'textarea',
                'rows'         => 8,
                'instructions' => __('Server-only profile data for the patient agent. Do not expose this to frontend templates.', 'meddigest-ai-sca'),
            ],
            [
                'key'          => 'field_mdsca_hidden_facts_json',
                'label'        => __('Hidden Facts JSON', 'meddigest-ai-sca'),
                'name'         => 'mdsca_hidden_facts_json',
                'type'         => 'textarea',
                'rows'         => 8,
                'instructions' => __('Server-only hidden facts and reveal rules. Stored for later migration into normalized fact rows.', 'meddigest-ai-sca'),
            ],
            [
                'key'          => 'field_mdsca_marking_items_json',
                'label'        => __('Marking Items JSON', 'meddigest-ai-sca'),
                'name'         => 'mdsca_marking_items_json',
                'type'         => 'textarea',
                'rows'         => 8,
                'instructions' => __('Server-only marking criteria. Never render this in frontend responses.', 'meddigest-ai-sca'),
            ],
            [
                'key'          => 'field_mdsca_internal_notes_json',
                'label'        => __('Internal Clinical Notes JSON', 'meddigest-ai-sca'),
                'name'         => 'mdsca_internal_notes_json',
                'type'         => 'textarea',
                'rows'         => 8,
                'instructions' => __('Server-only internal notes for snapshotting and grading.', 'meddigest-ai-sca'),
            ],
            [
                'key'           => 'field_mdsca_mock_pool_enabled',
                'label'         => __('Mock Pool Enabled', 'meddigest-ai-sca'),
                'name'          => 'mdsca_mock_pool_enabled',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
            ],
            [
                'key'           => 'field_mdsca_mock_ready_status',
                'label'         => __('Mock Ready Status', 'meddigest-ai-sca'),
                'name'          => 'mdsca_mock_ready_status',
                'type'          => 'select',
                'choices'       => [
                    'draft'    => __('Draft', 'meddigest-ai-sca'),
                    'review'   => __('Needs Review', 'meddigest-ai-sca'),
                    'approved' => __('Approved', 'meddigest-ai-sca'),
                ],
                'default_value' => 'draft',
                'ui'            => 1,
            ],
            [
                'key'   => 'field_mdsca_mock_primary_group_term_id',
                'label' => __('Mock Primary Group Term ID', 'meddigest-ai-sca'),
                'name'  => 'mdsca_mock_primary_group_term_id',
                'type'  => 'number',
                'min'   => 0,
                'step'  => 1,
            ],
        ];
    }
}
