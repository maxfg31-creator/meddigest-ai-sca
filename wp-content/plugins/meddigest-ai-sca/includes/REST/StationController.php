<?php
namespace MedDigest\AiSca\REST;

use MedDigest\AiSca\Cases\CaseConfigRepository;
use MedDigest\AiSca\Cases\CaseSnapshotService;
use MedDigest\AiSca\OpenAI\RealtimeTokenService;
use MedDigest\AiSca\Practice\FeedbackJob;
use MedDigest\AiSca\Practice\StationAttemptService;

if (!defined('ABSPATH')) {
    exit;
}

final class StationController
{
    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/station/start',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'start'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/station/(?P<attempt_uuid>[a-f0-9-]{36})/realtime-token',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'realtime_token'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/station/(?P<attempt_uuid>[a-f0-9-]{36})/status',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'status'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/station/(?P<attempt_uuid>[a-f0-9-]{36})/end',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'end'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/station/(?P<attempt_uuid>[a-f0-9-]{36})/transcript',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'transcript'],
                'permission_callback' => [$this, 'permission'],
            ]
        );

        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/station/(?P<attempt_uuid>[a-f0-9-]{36})/feedback',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'feedback'],
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
     * Start station setup.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function start($request)
    {
        $case_post_id = absint($request->get_param('case_post_id'));

        if (!$case_post_id) {
            return new \WP_Error('meddigest_ai_sca_case_required', __('A case ID is required.', 'meddigest-ai-sca'), ['status' => 400]);
        }

        $attempt = (new StationAttemptService())->create_attempt(get_current_user_id(), $case_post_id);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        return rest_ensure_response($this->attempt_response($attempt));
    }

    /**
     * Mint Realtime token and begin live station.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function realtime_token($request)
    {
        $service      = new StationAttemptService();
        $attempt_uuid = sanitize_text_field($request['attempt_uuid']);
        $attempt      = $service->get_owned_attempt(get_current_user_id(), $attempt_uuid);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        if (!in_array($attempt['status'], [StationAttemptService::STATUS_SETUP, StationAttemptService::STATUS_LIVE], true)) {
            return new \WP_Error('meddigest_ai_sca_invalid_attempt_state', __('This station is not ready for a Realtime session.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        if (StationAttemptService::STATUS_LIVE === $attempt['status'] && !empty($attempt['hard_stop_at']) && strtotime($attempt['hard_stop_at']) <= time()) {
            $service->end_attempt(get_current_user_id(), $attempt_uuid);

            return new \WP_Error('meddigest_ai_sca_station_time_expired', __('This station has ended.', 'meddigest-ai-sca'), ['status' => 409]);
        }

        $token = (new RealtimeTokenService())->create_client_secret($attempt);

        if (is_wp_error($token)) {
            if (StationAttemptService::STATUS_SETUP === $attempt['status']) {
                $service->end_attempt(get_current_user_id(), $attempt_uuid);
            }

            return $token;
        }

        $live_attempt = $service->begin_live(get_current_user_id(), $attempt_uuid);

        if (is_wp_error($live_attempt)) {
            return $live_attempt;
        }

        return rest_ensure_response(
            [
                'attempt' => $this->attempt_response($live_attempt),
                'token'   => $token,
            ]
        );
    }

    /**
     * Attempt status.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function status($request)
    {
        $attempt = (new StationAttemptService())->get_owned_attempt(get_current_user_id(), sanitize_text_field($request['attempt_uuid']));

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        return rest_ensure_response($this->attempt_response($attempt));
    }

    /**
     * End attempt.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function end($request)
    {
        $attempt = (new StationAttemptService())->end_attempt(get_current_user_id(), sanitize_text_field($request['attempt_uuid']));

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        return rest_ensure_response($this->attempt_response($attempt));
    }

    /**
     * Persist transcript turns captured from Realtime events.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function transcript($request)
    {
        $turns = $request->get_param('turns');

        if (!is_array($turns)) {
            return new \WP_Error('meddigest_ai_sca_invalid_transcript', __('Transcript turns must be an array.', 'meddigest-ai-sca'), ['status' => 400]);
        }

        $attempt = (new StationAttemptService())->save_transcript(
            get_current_user_id(),
            sanitize_text_field($request['attempt_uuid']),
            $turns
        );

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        return rest_ensure_response(['saved' => true]);
    }

    /**
     * Return feedback.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function feedback($request)
    {
        $attempt_uuid = sanitize_text_field($request['attempt_uuid']);
        $attempt      = (new StationAttemptService())->get_owned_attempt(get_current_user_id(), $attempt_uuid);

        if (is_wp_error($attempt)) {
            return $attempt;
        }

        $feedback = (new FeedbackJob())->get_feedback($attempt_uuid);

        return rest_ensure_response(
            [
                'attempt'  => $this->attempt_response($attempt),
                'feedback' => $feedback ?: ['processing_status' => 'pending'],
            ]
        );
    }

    /**
     * Safe attempt response.
     *
     * @param array $attempt Attempt row.
     */
    private function attempt_response(array $attempt)
    {
        $now       = time();
        $hard_stop = !empty($attempt['hard_stop_at']) ? strtotime($attempt['hard_stop_at']) : null;

        return [
            'attempt_uuid'     => $attempt['attempt_uuid'],
            'case_post_id'     => absint($attempt['case_post_id']),
            'status'           => $attempt['status'],
            'live_started_at'  => $attempt['live_started_at'],
            'hard_stop_at'     => $attempt['hard_stop_at'],
            'seconds_remaining' => $hard_stop ? max(0, $hard_stop - $now) : null,
            'setup'            => (new CaseSnapshotService())->build_candidate_setup(absint($attempt['case_post_id'])),
            'ai_enabled'       => (new CaseConfigRepository())->is_ai_enabled(absint($attempt['case_post_id'])),
        ];
    }
}
