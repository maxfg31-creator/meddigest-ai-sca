<?php
namespace MedDigest\AiSca\OpenAI;

if (!defined('ABSPATH')) {
    exit;
}

final class PatientAgentPromptBuilder
{
    /**
     * Build patient-agent instructions without marking scheme/rubric.
     *
     * @param array $attempt Attempt row.
     */
    public function build(array $attempt)
    {
        $snapshot = !empty($attempt['snapshot_json']) ? json_decode($attempt['snapshot_json'], true) : [];
        $snapshot = is_array($snapshot) ? $snapshot : [];

        $mode    = sanitize_text_field($snapshot['mode'] ?? 'video');
        $profile = sanitize_textarea_field($snapshot['patient_profile_json'] ?? '');

        return trim(
            "You are role-playing as the patient in a MedDigest SCA practice station.\n"
            . "Consultation mode: {$mode}.\n"
            . "Stay in character as the patient. Do not mention grading, marking schemes, rubrics, or hidden examiner logic.\n"
            . "Reveal information only when clinically appropriate or when the doctor asks relevant questions.\n\n"
            . "Patient profile and behavior notes:\n{$profile}"
        );
    }
}
