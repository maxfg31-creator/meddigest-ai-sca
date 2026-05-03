<?php
/**
 * Case snapshot service.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Cases;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseSnapshotService
{
    /**
     * Build a safe server-side attempt snapshot.
     *
     * @param int $case_post_id Case post ID.
     */
    public function build_snapshot($case_post_id)
    {
        $case_post_id = absint($case_post_id);
        $post         = get_post($case_post_id);
        $config       = (new CaseConfigRepository())->get_by_case_post_id($case_post_id);

        if (!$post || !$config) {
            return [];
        }

        return [
            'case_post_id'                    => $case_post_id,
            'title'                           => get_the_title($case_post_id),
            'permalink'                       => get_permalink($case_post_id),
            'doctor_brief_override'           => $config['doctor_brief_override'] ?? '',
            'pre_start_instructions_override' => $config['pre_start_instructions_override'] ?? '',
            'mode'                            => $config['mode'] ?? 'video',
            'first_speaker'                   => $config['first_speaker'] ?? 'patient',
            'voice_id'                        => $config['voice_id'] ?? '',
            'ai_version'                      => $config['ai_version'] ?? '',
            'patient_profile_json'            => $config['patient_profile_json'] ?? '',
            'internal_notes_json'             => $config['internal_notes_json'] ?? '',
            'snapshot_created_at'             => gmdate('Y-m-d H:i:s'),
        ];
    }

    /**
     * Build the candidate-visible setup payload.
     *
     * @param int $case_post_id Case post ID.
     */
    public function build_candidate_setup($case_post_id)
    {
        $snapshot = $this->build_snapshot($case_post_id);

        if (empty($snapshot)) {
            return [];
        }

        return [
            'case_post_id'          => $snapshot['case_post_id'],
            'title'                 => $snapshot['title'],
            'mode'                  => $snapshot['mode'],
            'doctor_brief'          => $snapshot['doctor_brief_override'],
            'pre_start_instructions' => $snapshot['pre_start_instructions_override'],
        ];
    }
}
