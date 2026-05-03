<?php
namespace MedDigest\AiSca\OpenAI;

if (!defined('ABSPATH')) {
    exit;
}

final class StructuredOutputSchemas
{
    /**
     * Schema for station feedback.
     */
    public function station_feedback()
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => [
                'practice_verdict',
                'dgd_grade',
                'cmc_grade',
                'rto_grade',
                'global',
                'strengths',
                'critical_misses',
                'missing_questions',
                'safety_netting_problems',
                'transcript_evidence',
                'improvements',
                'three_priorities',
                'evidence_gaps',
            ],
            'properties'           => [
                'practice_verdict' => [
                    'type'        => 'string',
                    'description' => 'A concise educational verdict for the learner.',
                ],
                'dgd_grade'        => $this->grade_schema('Data gathering and diagnosis grade.'),
                'cmc_grade'        => $this->grade_schema('Clinical management and clinical communication grade.'),
                'rto_grade'        => $this->grade_schema('Relating to others grade.'),
                'global'           => [
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'required'             => ['score_percent', 'pass_recommendation', 'critical_issues'],
                    'properties'           => [
                        'score_percent'       => [
                            'type'        => 'integer',
                            'minimum'     => 0,
                            'maximum'     => 100,
                            'description' => 'Overall score estimate from transcript evidence only.',
                        ],
                        'pass_recommendation' => [
                            'type' => 'boolean',
                        ],
                        'critical_issues'     => [
                            'type'  => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
                'strengths'        => [
                    'type'  => 'array',
                    'items' => $this->feedback_item_schema(),
                ],
                'critical_misses'  => [
                    'type'  => 'array',
                    'items' => $this->feedback_item_schema(),
                ],
                'missing_questions' => [
                    'type'  => 'array',
                    'items' => $this->feedback_item_schema(),
                ],
                'safety_netting_problems' => [
                    'type'  => 'array',
                    'items' => $this->feedback_item_schema(),
                ],
                'transcript_evidence' => [
                    'type'  => 'array',
                    'items' => $this->feedback_item_schema(),
                ],
                'improvements'     => [
                    'type'  => 'array',
                    'items' => $this->feedback_item_schema(),
                ],
                'three_priorities' => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
                'evidence_gaps'    => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * Feedback item schema.
     */
    private function feedback_item_schema()
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['point', 'evidence'],
            'properties'           => [
                'point'    => ['type' => 'string'],
                'evidence' => ['type' => 'string'],
            ],
        ];
    }

    /**
     * Grade schema.
     *
     * @param string $description Description.
     */
    private function grade_schema($description)
    {
        return [
            'type'        => 'string',
            'enum'        => ['clear_fail', 'borderline', 'pass', 'strong_pass', 'not_assessable'],
            'description' => $description,
        ];
    }
}
