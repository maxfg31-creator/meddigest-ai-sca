<?php
/**
 * Frontend shortcodes.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Frontend;

use MedDigest\AiSca\Credits\CreditService;
use MedDigest\AiSca\MemberPress\EligibilityService;
use MedDigest\AiSca\MemberPress\ProductMappingService;

if (!defined('ABSPATH')) {
    exit;
}

final class Shortcodes
{
    private const CREDIT_PACKS_SHORTCODE = 'meddigest_ai_credit_packs';

    /**
     * Register shortcodes.
     */
    public function register()
    {
        if (shortcode_exists(self::CREDIT_PACKS_SHORTCODE)) {
            add_action('admin_notices', [$this, 'render_shortcode_collision_notice']);
            return;
        }

        add_shortcode(self::CREDIT_PACKS_SHORTCODE, [$this, 'render_credit_packs']);
    }

    /**
     * Render shortcode collision notice.
     */
    public function render_shortcode_collision_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('MedDigest AI SCA could not register [meddigest_ai_credit_packs] because another plugin or Code Snippet already registered that shortcode.', 'meddigest-ai-sca')
        );
    }

    /**
     * Render AI credit packs for the pricing page.
     */
    public function render_credit_packs()
    {
        static $rendered = false;

        if ($rendered) {
            if (current_user_can('manage_options')) {
                return '<div class="mdsca-notice mdsca-notice-warning">' . esc_html__('MedDigest AI credit packs shortcode was rendered more than once on this page. The duplicate output was suppressed.', 'meddigest-ai-sca') . '</div>';
            }

            return '';
        }

        $rendered = true;

        if (!is_user_logged_in()) {
            return '';
        }

        $user_id     = get_current_user_id();
        $eligibility = new EligibilityService();

        if (!$eligibility->user_has_sca_cases_premium($user_id)) {
            return '';
        }

        wp_enqueue_style('meddigest-ai-sca-frontend');

        $mapping = new ProductMappingService();
        $credits = new CreditService();

        return (new TemplateLoader())->render(
            'pricing/credit-packs.php',
            [
                'packs'   => $mapping->get_credit_packs(),
                'balance' => $credits->get_balance($user_id),
            ]
        );
    }
}

