<?php
namespace MedDigest\AiSca\OpenAI;

if (!defined('ABSPATH')) {
    exit;
}

final class GraderPromptBuilder
{
    /**
     * Build grader input messages.
     *
     * @param array $attempt Attempt row.
     */
    public function build(array $attempt)
    {
        $snapshot       = $this->decode_json($attempt['snapshot_json'] ?? '');
        $transcript     = $this->decode_json($attempt['transcript_json'] ?? '');
        $transcript_text = $this->transcript_text($transcript);

        if ('' === trim($transcript_text)) {
            $transcript_text = '[No transcript evidence was captured for this attempt.]';
        }

        $case_context = [
            'title'               => $snapshot['title'] ?? '',
            'mode'                => $snapshot['mode'] ?? '',
            'doctor_brief'        => $snapshot['doctor_brief_override'] ?? '',
            'marking_context'     => $snapshot['internal_notes_json'] ?? '',
            'patient_profile_json' => $snapshot['patient_profile_json'] ?? '',
        ];

        return [
            [
                'role'    => 'system',
                'content' => $this->system_prompt(),
            ],
            [
                'role'    => 'user',
                'content' => wp_json_encode(
                    [
                        'case_context'    => $case_context,
                        'transcript_only' => $transcript_text,
                    ]
                ),
            ],
        ];
    }

    /**
     * System prompt for grading.
     */
    private function system_prompt()
    {
        return implode(
            "\n",
            [
                'You are grading an SCA practice station for educational feedback.',
                'Use only the supplied transcript evidence when awarding credit.',
                'Do not infer that the candidate completed an action unless it is present in the transcript.',
                'If transcript evidence is absent or incomplete, mark affected domains as not_assessable and explain the evidence gap.',
                'Use the supplied case context and marking context only to decide what evidence would count.',
                'Include strengths, critical misses, missing questions or explanations, safety-netting problems, transcript evidence, and exactly three priority actions where possible.',
                'Return concise, learner-safe feedback in the requested JSON schema.',
            ]
        );
    }

    /**
     * Decode JSON into an array.
     *
     * @param string $json JSON string.
     */
    private function decode_json($json)
    {
        $decoded = is_string($json) && '' !== $json ? json_decode($json, true) : [];

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Convert transcript JSON to grader-readable text.
     *
     * @param array $transcript Transcript data.
     */
    private function transcript_text(array $transcript)
    {
        if (isset($transcript['text']) && is_string($transcript['text'])) {
            return $transcript['text'];
        }

        if (empty($transcript['turns']) || !is_array($transcript['turns'])) {
            return '';
        }

        $lines = [];

        foreach ($transcript['turns'] as $turn) {
            if (!is_array($turn)) {
                continue;
            }

            $speaker = isset($turn['speaker']) ? sanitize_text_field((string) $turn['speaker']) : 'speaker';
            $text    = isset($turn['text']) ? trim(wp_strip_all_tags((string) $turn['text'])) : '';

            if ('' === $text) {
                continue;
            }

            $lines[] = $speaker . ': ' . $text;
        }

        return implode("\n", $lines);
    }
}
