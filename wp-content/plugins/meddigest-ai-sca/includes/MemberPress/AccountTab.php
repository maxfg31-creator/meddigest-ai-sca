<?php
/**
 * MemberPress account tab integration.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\MemberPress;

use MedDigest\AiSca\Credits\CreditService;
use MedDigest\AiSca\Frontend\NoCache;
use MedDigest\AiSca\Frontend\TemplateLoader;
use MedDigest\AiSca\Mock\MockLaunchService;
use MedDigest\AiSca\Practice\HistoryService;
use MedDigest\AiSca\Practice\StationAttemptService;

if (!defined('ABSPATH')) {
    exit;
}

final class AccountTab
{
    private const ACTION = 'mdsca-ai-practice';

    /**
     * Register MemberPress hooks.
     */
    public function register()
    {
        add_action('template_redirect', [$this, 'send_no_cache_headers']);
        add_action('mepr_account_nav', [$this, 'render_nav_item']);
        add_action('mepr_account_nav_content', [$this, 'render_tab_content']);
    }

    /**
     * Send no-cache headers for the owner-only account tab before markup starts.
     */
    public function send_no_cache_headers()
    {
        if ($this->is_current_tab() && $this->should_show()) {
            (new NoCache())->send();
        }
    }

    /**
     * Render tab nav item.
     */
    public function render_nav_item()
    {
        if (!$this->should_show()) {
            return;
        }

        $active = $this->is_current_tab() ? ' mepr-active-nav-tab' : '';

        printf(
            '<span class="mepr-nav-item mdsca-ai-practice%1$s"><a href="%2$s">%3$s</a></span>',
            esc_attr($active),
            esc_url(add_query_arg('action', self::ACTION)),
            esc_html__('AI Practice', 'meddigest-ai-sca')
        );
    }

    /**
     * Render tab content when selected.
     */
    public function render_tab_content()
    {
        if (!$this->is_current_tab() || !$this->should_show()) {
            return;
        }

        (new NoCache())->send();

        $user_id        = get_current_user_id();
        $eligibility    = new EligibilityService();
        $credits        = new CreditService();
        $history        = new HistoryService();
        $page           = isset($_GET['mdsca_ai_page']) ? absint(wp_unslash($_GET['mdsca_ai_page'])) : 1;
        $balance        = $credits->get_balance($user_id);
        $premium        = $eligibility->user_has_sca_cases_premium($user_id);
        $active_station = (new StationAttemptService())->get_active_attempt_for_user($user_id);
        $active_mock    = (new MockLaunchService())->get_active_mock_for_user($user_id);

        wp_enqueue_style('meddigest-ai-sca-frontend');
        wp_enqueue_style('meddigest-ai-sca-mock');

        echo (new TemplateLoader())->render(
            'account/ai-practice-tab.php',
            [
                'has_premium'    => $premium,
                'balance'        => [
                    'available' => $premium ? (int) $balance['available'] : 0,
                    'locked'    => $premium ? (int) $balance['locked'] : (int) $balance['total'],
                    'total'     => (int) $balance['total'],
                ],
                'active_station' => $active_station,
                'active_mock'    => $active_mock,
                'history'        => $history->get_history($user_id, $page, 20),
                'current_page'   => $page,
                'pricing_url'    => home_url('/pricing/#ai-credits'),
            ]
        );
    }

    /**
     * Whether the tab should appear.
     */
    private function should_show()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();

        return (new EligibilityService())->user_has_sca_cases_premium($user_id)
            || (new HistoryService())->history_exists($user_id);
    }

    /**
     * Whether current account action is AI Practice.
     */
    private function is_current_tab()
    {
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

        return self::ACTION === $action;
    }
}
