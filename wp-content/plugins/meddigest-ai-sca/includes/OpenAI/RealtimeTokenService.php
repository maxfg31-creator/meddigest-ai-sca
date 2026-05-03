<?php
namespace MedDigest\AiSca\OpenAI;

use MedDigest\AiSca\Support\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class RealtimeTokenService
{
    /**
     * Create a short-lived Realtime client secret.
     *
     * @param array $attempt Attempt row.
     */
    public function create_client_secret(array $attempt)
    {
        $api_key = $this->api_key();

        if ('' === $api_key) {
            return new \WP_Error(
                'meddigest_ai_sca_openai_key_missing',
                __('OpenAI API key is not configured on the server.', 'meddigest-ai-sca'),
                ['status' => 500]
            );
        }

        $snapshot = !empty($attempt['snapshot_json']) ? json_decode($attempt['snapshot_json'], true) : [];
        $snapshot = is_array($snapshot) ? $snapshot : [];
        $voice    = sanitize_key($snapshot['voice_id'] ?? '');
        $voice    = $voice ?: apply_filters('meddigest_ai_sca_default_realtime_voice', 'marin');
        $model    = apply_filters('meddigest_ai_sca_realtime_model', defined('MEDDIGEST_AI_SCA_REALTIME_MODEL') ? MEDDIGEST_AI_SCA_REALTIME_MODEL : 'gpt-realtime-1.5');

        $body = [
            'expires_after' => [
                'anchor'  => 'created_at',
                'seconds' => 60,
            ],
            'session'       => [
                'type'         => 'realtime',
                'model'        => $model,
                'instructions' => (new PatientAgentPromptBuilder())->build($attempt),
                'audio'        => [
                    'input'  => [
                        'transcription' => [
                            'model'    => apply_filters('meddigest_ai_sca_realtime_transcription_model', 'gpt-4o-transcribe'),
                            'language' => apply_filters('meddigest_ai_sca_realtime_transcription_language', 'en'),
                        ],
                    ],
                    'output' => [
                        'voice' => $voice,
                    ],
                ],
            ],
        ];

        $response = wp_remote_post(
            'https://api.openai.com/v1/realtime/client_secrets',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 20,
                'body'    => wp_json_encode($body),
            ]
        );

        if (is_wp_error($response)) {
            Logger::error($response->get_error_message());

            return new \WP_Error('meddigest_ai_sca_realtime_token_failed', __('Realtime token generation failed.', 'meddigest-ai-sca'), ['status' => 502]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300 || !is_array($data)) {
            Logger::error('OpenAI Realtime token failed with HTTP ' . $code);

            return new \WP_Error('meddigest_ai_sca_realtime_token_failed', __('Realtime token generation failed.', 'meddigest-ai-sca'), ['status' => 502]);
        }

        return $data;
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
