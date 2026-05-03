<?php
/**
 * Frontend shortcodes.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Frontend;

use MedDigest\AiSca\Credits\CreditService;
use MedDigest\AiSca\Cases\CaseConfigRepository;
use MedDigest\AiSca\Cases\CasePostTypeIntegration;
use MedDigest\AiSca\MemberPress\EligibilityService;
use MedDigest\AiSca\MemberPress\ProductMappingService;
use MedDigest\AiSca\Practice\StationAttemptService;

if (!defined('ABSPATH')) {
    exit;
}

final class Shortcodes
{
    private const CREDIT_PACKS_SHORTCODE = 'meddigest_ai_credit_packs';
    private const CASE_CTA_SHORTCODE     = 'meddigest_ai_case_cta';

    /**
     * Register shortcodes.
     */
    public function register()
    {
        if (shortcode_exists(self::CREDIT_PACKS_SHORTCODE)) {
            add_action('admin_notices', [$this, 'render_shortcode_collision_notice']);
        } else {
            add_shortcode(self::CREDIT_PACKS_SHORTCODE, [$this, 'render_credit_packs']);
        }

        if (shortcode_exists(self::CASE_CTA_SHORTCODE)) {
            add_action('admin_notices', [$this, 'render_case_cta_collision_notice']);
        } else {
            add_shortcode(self::CASE_CTA_SHORTCODE, [$this, 'render_case_cta']);
        }

        add_filter('the_content', [$this, 'maybe_prepend_case_cta'], 12);
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
     * Render case CTA shortcode collision notice.
     */
    public function render_case_cta_collision_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('MedDigest AI SCA could not register [meddigest_ai_case_cta] because another plugin or Code Snippet already registered that shortcode.', 'meddigest-ai-sca')
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

    /**
     * Render case-page AI CTA.
     *
     * @param array $atts Shortcode attributes.
     */
    public function render_case_cta($atts = [])
    {
        $atts = shortcode_atts(
            [
                'case_id' => 0,
            ],
            $atts,
            self::CASE_CTA_SHORTCODE
        );

        $case_id = absint($atts['case_id']);

        if (!$case_id) {
            $case_id = get_the_ID();
        }

        if (!$case_id || !(new CaseConfigRepository())->is_ai_enabled($case_id)) {
            return '';
        }

        wp_enqueue_style('meddigest-ai-sca-frontend');

        $user_id      = get_current_user_id();
        $eligibility  = new EligibilityService();
        $credits      = new CreditService();
        $attempts     = new StationAttemptService();
        $has_premium  = $user_id ? $eligibility->user_has_sca_cases_premium($user_id) : false;
        $balance      = $user_id ? $credits->get_balance($user_id) : ['available' => 0, 'locked' => 0, 'total' => 0];
        $active       = $user_id ? $attempts->get_active_attempt_for_user($user_id) : null;

        return (new TemplateLoader())->render(
            'case/ai-case-cta.php',
            [
                'case_id'      => $case_id,
                'has_premium'  => $has_premium,
                'balance'      => $balance,
                'active'       => $active,
                'setup_url'    => home_url('/sca-ai/station/' . get_post_field('post_name', $case_id) . '/setup/'),
                'pricing_url'  => home_url('/pricing/#ai-credits'),
                'premium_url'  => home_url('/pricing/#sca-cases-premium'),
            ]
        );
    }

    /**
     * Prepend CTA at template/content level on AI-enabled case singles.
     *
     * @param string $content Content.
     */
    public function maybe_prepend_case_cta($content)
    {
        if (is_admin() || !is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id   = get_the_ID();
        $post_type = get_post_type($post_id);

        if (!CasePostTypeIntegration::is_case_post_type($post_type)) {
            return $content;
        }

        if (has_shortcode($content, self::CASE_CTA_SHORTCODE)) {
            return $content;
        }

        if (!(new CaseConfigRepository())->is_ai_enabled($post_id)) {
            return $content;
        }

        $enabled = apply_filters('meddigest_ai_sca_auto_prepend_case_cta', true, $post_id);

        if (!$enabled) {
            return $content;
        }

        return $this->render_case_cta(['case_id' => $post_id]) . $content;
    }
}
