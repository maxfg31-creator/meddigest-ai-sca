<?php
/**
 * Owner-only AI practice history REST endpoint.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\REST;

use MedDigest\AiSca\Practice\HistoryService;

if (!defined('ABSPATH')) {
    exit;
}

final class HistoryController
{
    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/history',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'history'],
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
     * Paginated owner-only AI history.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function history($request)
    {
        $page     = absint($request->get_param('page') ?: 1);
        $per_page = absint($request->get_param('per_page') ?: 20);

        return rest_ensure_response((new HistoryService())->get_history(get_current_user_id(), $page, $per_page));
    }
}
