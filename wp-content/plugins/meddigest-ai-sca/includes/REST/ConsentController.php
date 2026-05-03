<?php
namespace MedDigest\AiSca\REST;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Mock\MockLaunchService;
use MedDigest\AiSca\Practice\StationAttemptService;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class ConsentController
{
    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/consent',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'save_consent'],
                'permission_callback' => [$this, 'permission'],
            ]
        );
    }

    /**
     * Require login and REST nonce.
     */
    public function permission()
    {
        if (!is_user_logged_in()) {
            return new \WP_Error('meddigest_ai_sca_login_required', __('You must be logged in.', 'meddigest-ai-sca'), ['status' => 401]);
        }

        $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('meddigest_ai_sca_nonce_required', __('A valid REST nonce is required.', 'meddigest-ai-sca'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Save consent for an attempt.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function save_consent($request)
    {
        global $wpdb;

        $object_type = sanitize_key($request->get_param('object_type') ?: 'station_attempt');
        $object_uuid = sanitize_text_field($request->get_param('object_uuid'));
        $user_id     = get_current_user_id();

        if (!in_array($object_type, ['station_attempt', 'mock_run'], true)) {
            return new \WP_Error('meddigest_ai_sca_invalid_consent_type', __('Unsupported consent object type.', 'meddigest-ai-sca'), ['status' => 400]);
        }

        $attempt = 'mock_run' === $object_type
            ? (new MockLaunchService())->get_owned_mock($user_id, $object_uuid)
            : (new StationAttemptService())->get_owned_attempt($user_id, $object_uuid);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        $tables = Schema::tables();
        $wpdb->insert(
            $tables['consents'],
            [
                'user_id'         => $user_id,
                'object_type'     => $object_type,
                'object_uuid'     => $object_uuid,
                'consent_version' => 'v1',
                'ip_address'      => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
                'user_agent'      => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255) : '',
                'agreed_at'       => Clock::mysql_utc(),
            ]
        );

        return rest_ensure_response(['saved' => true]);
    }
}
