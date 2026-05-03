<?php
/**
 * Current user state endpoint.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\REST;

use MedDigest\AiSca\Credits\CreditService;
use MedDigest\AiSca\MemberPress\EligibilityService;

if (!defined('ABSPATH')) {
    exit;
}

final class MeStateController
{
    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route(
            MEDDIGEST_AI_SCA_REST_NAMESPACE,
            '/me/state',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_state'],
                'permission_callback' => [$this, 'permission'],
            ]
        );
    }

    /**
     * Permission callback.
     */
    public function permission()
    {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'meddigest_ai_sca_login_required',
                __('You must be logged in to view AI practice state.', 'meddigest-ai-sca'),
                ['status' => 401]
            );
        }

        $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error(
                'meddigest_ai_sca_nonce_required',
                __('A valid REST nonce is required to view AI practice state.', 'meddigest-ai-sca'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Return safe current-user state.
     */
    public function get_state()
    {
        $user_id     = get_current_user_id();
        $eligibility = new EligibilityService();
        $credits     = new CreditService();

        $has_premium = $eligibility->user_has_sca_cases_premium($user_id);
        $balance     = $credits->get_balance($user_id);

        $usable_credits = $has_premium ? (int) $balance['available'] : 0;
        $locked_credits = $has_premium ? (int) $balance['locked'] : (int) $balance['total'];

        return rest_ensure_response(
            [
                'logged_in'                 => true,
                'has_sca_cases_premium'     => $has_premium,
                'credits'                   => [
                    'available' => $usable_credits,
                    'locked'    => $locked_credits,
                    'total'     => (int) $balance['total'],
                ],
                'pricing_credit_packs_visible' => $has_premium,
                'history_exists'            => false,
                'active_station'            => null,
                'active_mock'               => null,
                'cta'                       => [
                    'full_mock' => $this->full_mock_cta($has_premium, $usable_credits),
                ],
            ]
        );
    }

    /**
     * Build safe full mock CTA state.
     *
     * @param bool $has_premium    Has active SCA Cases Premium.
     * @param int  $usable_credits Usable credits.
     */
    private function full_mock_cta($has_premium, $usable_credits)
    {
        if (!$has_premium) {
            return [
                'state'  => 'join_premium',
                'label'  => __('Join SCA Cases Premium', 'meddigest-ai-sca'),
                'target' => home_url('/pricing/#sca-cases-premium'),
            ];
        }

        if ($usable_credits < 12) {
            return [
                'state'  => 'buy_credits',
                'label'  => __('Buy AI Credits', 'meddigest-ai-sca'),
                'target' => home_url('/pricing/#ai-credits'),
                'note'   => __('12 credits required to launch Full Mock SCA.', 'meddigest-ai-sca'),
            ];
        }

        return [
            'state'  => 'start_mock',
            'label'  => __('Start Full Mock SCA', 'meddigest-ai-sca'),
            'target' => home_url('/sca-ai/mock/launch/'),
        ];
    }
}
