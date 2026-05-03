<?php
namespace MedDigest\AiSca\REST;

use MedDigest\AiSca\Mock\MockLaunchService;
use MedDigest\AiSca\Mock\MockResultsAggregator;
use MedDigest\AiSca\Mock\MockRunner;

if (!defined('ABSPATH')) {
    exit;
}

final class MockController
{
    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/mock/start',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'start'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/mock/(?P<mock_uuid>[a-f0-9-]{36})/status',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'status'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/mock/(?P<mock_uuid>[a-f0-9-]{36})/realtime-token',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'realtime_token'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/mock/(?P<mock_uuid>[a-f0-9-]{36})/transcript',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'transcript'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/mock/(?P<mock_uuid>[a-f0-9-]{36})/results',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'results'],
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
     * Start mock.
     */
    public function start()
    {
        $mock = (new MockLaunchService())->create_mock(get_current_user_id());

        if (is_wp_error($mock)) {
            return $mock;
        }

        return rest_ensure_response(
            [
                'mock_uuid'   => $mock['mock_uuid'],
                'status'      => $mock['status'],
                'run_url'     => home_url('/sca-ai/mock/' . $mock['mock_uuid'] . '/run/'),
                'results_url' => home_url('/sca-ai/mock/' . $mock['mock_uuid'] . '/results/'),
            ]
        );
    }

    /**
     * Mock status.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function status($request)
    {
        $status = (new MockRunner())->status(get_current_user_id(), sanitize_text_field($request['mock_uuid']));

        if (is_wp_error($status)) {
            return $status;
        }

        return rest_ensure_response($status);
    }

    /**
     * Current station Realtime token.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function realtime_token($request)
    {
        $token = (new MockRunner())->realtime_token(get_current_user_id(), sanitize_text_field($request['mock_uuid']));

        if (is_wp_error($token)) {
            return $token;
        }

        return rest_ensure_response($token);
    }

    /**
     * Save current station transcript.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function transcript($request)
    {
        $turns = $request->get_param('turns');

        if (!is_array($turns)) {
            return new \WP_Error('meddigest_ai_sca_invalid_transcript', __('Transcript turns must be an array.', 'meddigest-ai-sca'), ['status' => 400]);
        }

        $result = (new MockRunner())->save_transcript(
            get_current_user_id(),
            sanitize_text_field($request['mock_uuid']),
            absint($request->get_param('station_number')),
            $turns
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Stored mock results. Does not trigger OpenAI calls.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function results($request)
    {
        $results = (new MockResultsAggregator())->results(get_current_user_id(), sanitize_text_field($request['mock_uuid']));

        if (is_wp_error($results)) {
            return $results;
        }

        return rest_ensure_response($results);
    }
}
