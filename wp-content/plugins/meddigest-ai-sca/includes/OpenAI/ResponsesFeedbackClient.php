<?php
namespace MedDigest\AiSca\OpenAI;

if (!defined('ABSPATH')) {
    exit;
}

final class ResponsesFeedbackClient
{
    /**
     * Generate structured feedback for an attempt.
     *
     * @param array $attempt Attempt row.
     */
    public function generate_feedback(array $attempt)
    {
        $api_key = $this->api_key();

        if ('' === $api_key) {
            return new \WP_Error(
                'meddigest_ai_sca_openai_key_missing',
                __('OpenAI API key is not configured on the server.', 'meddigest-ai-sca'),
                ['non_retryable' => true]
            );
        }

        $schema = (new StructuredOutputSchemas())->station_feedback();
        $body   = [
            'model' => apply_filters(
                'meddigest_ai_sca_feedback_model',
                defined('MEDDIGEST_AI_SCA_FEEDBACK_MODEL') ? MEDDIGEST_AI_SCA_FEEDBACK_MODEL : 'gpt-5.4'
            ),
            'store' => false,
            'input' => (new GraderPromptBuilder())->build($attempt),
            'text'  => [
                'format' => [
                    'type'        => 'json_schema',
                    'name'        => 'meddigest_ai_sca_station_feedback',
                    'description' => 'Structured SCA station feedback.',
                    'strict'      => true,
                    'schema'      => $schema,
                ],
            ],
        ];

        $response = wp_remote_post(
            'https://api.openai.com/v1/responses',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 60,
                'body'    => wp_json_encode($body),
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        if ($code < 200 || $code >= 300 || !is_array($data)) {
            return new \WP_Error(
                'meddigest_ai_sca_feedback_request_failed',
                __('OpenAI feedback generation failed.', 'meddigest-ai-sca'),
                ['status_code' => $code, 'body' => $raw]
            );
        }

        $feedback = $this->extract_structured_output($data);

        if (is_wp_error($feedback)) {
            return $feedback;
        }

        return [
            'feedback' => $feedback,
            'raw'      => $this->safe_response_snapshot($data),
        ];
    }

    /**
     * Extract structured JSON from a Responses API response.
     *
     * @param array $data Response data.
     */
    private function extract_structured_output(array $data)
    {
        if (!empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (empty($item['content']) || !is_array($item['content'])) {
                    continue;
                }

                foreach ($item['content'] as $content) {
                    if (!is_array($content)) {
                        continue;
                    }

                    if (isset($content['parsed']) && is_array($content['parsed'])) {
                        return $content['parsed'];
                    }

                    if (isset($content['text']) && is_string($content['text'])) {
                        $decoded = json_decode($content['text'], true);

                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }
                }
            }
        }

        if (isset($data['output_text']) && is_string($data['output_text'])) {
            $decoded = json_decode($data['output_text'], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return new \WP_Error(
            'meddigest_ai_sca_feedback_parse_failed',
            __('OpenAI feedback response could not be parsed.', 'meddigest-ai-sca')
        );
    }

    /**
     * Keep minimal provider metadata for audit without storing prompts in feedback rows.
     *
     * @param array $data Response data.
     */
    private function safe_response_snapshot(array $data)
    {
        return [
            'id'                 => $data['id'] ?? '',
            'model'              => $data['model'] ?? '',
            'status'             => $data['status'] ?? '',
            'created_at'         => $data['created_at'] ?? '',
            'incomplete_details' => $data['incomplete_details'] ?? null,
            'usage'              => $data['usage'] ?? null,
        ];
    }

    /**
     * Server-side API key lookup.
     */
    private function api_key()
    {
        if (defined('MEDDIGEST_AI_SCA_OPENAI_API_KEY') && MEDDIGEST_AI_SCA_OPENAI_API_KEY) {
            return (string) MEDDIGEST_AI_SCA_OPENAI_API_KEY;
        }

        $env = getenv('MEDDIGEST_AI_SCA_OPENAI_API_KEY');

        if ($env) {
            return (string) $env;
        }

        $fallback = getenv('OPENAI_API_KEY');

        return $fallback ? (string) $fallback : '';
    }
}
