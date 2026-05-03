<?php
/**
 * Single station setup route.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$case_slug = sanitize_title(get_query_var('mdsca_case_slug'));
$case_post = null;

foreach (\MedDigest\AiSca\Cases\CasePostTypeIntegration::case_post_types() as $post_type) {
    $candidate = get_page_by_path($case_slug, OBJECT, $post_type);
    if ($candidate) {
        $case_post = $candidate;
        break;
    }
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$user_id = get_current_user_id();
$case_id = $case_post ? absint($case_post->ID) : 0;

wp_enqueue_style('meddigest-ai-sca-station');
wp_enqueue_script('meddigest-ai-sca-station-live');
wp_localize_script(
    'meddigest-ai-sca-station-live',
    'mdscaStation',
    [
        'restUrl' => esc_url_raw(rest_url(MEDDIGEST_AI_SCA_REST_NAMESPACE)),
        'nonce'   => wp_create_nonce('wp_rest'),
    ]
);

$setup      = $case_id ? (new \MedDigest\AiSca\Cases\CaseSnapshotService())->build_candidate_setup($case_id) : [];
$enabled    = $case_id ? (new \MedDigest\AiSca\Cases\CaseConfigRepository())->is_ai_enabled($case_id) : false;
$premium    = (new \MedDigest\AiSca\MemberPress\EligibilityService())->user_has_sca_cases_premium($user_id);
$balance    = (new \MedDigest\AiSca\Credits\CreditService())->get_balance($user_id);
$can_begin  = $case_id && $enabled && $premium && absint($balance['available']) >= 1;
$body_class = 'mdsca-ai-template mdsca-station-setup-page';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class($body_class); ?>>
    <main class="mdsca-station mdsca-station-setup" data-mdsca-case-id="<?php echo esc_attr($case_id); ?>" data-mdsca-can-begin="<?php echo $can_begin ? '1' : '0'; ?>">
        <a class="mdsca-back-link" href="<?php echo esc_url($case_id ? get_permalink($case_id) : home_url('/sca-cases/')); ?>">
            <?php echo esc_html__('Back to case', 'meddigest-ai-sca'); ?>
        </a>

        <h1><?php echo esc_html($setup['title'] ?? __('AI Consultation Setup', 'meddigest-ai-sca')); ?></h1>
        <p class="mdsca-badge"><?php echo esc_html(ucfirst($setup['mode'] ?? 'video')); ?></p>

        <?php if (!$case_id) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('Case not found.', 'meddigest-ai-sca'); ?></div>
        <?php elseif (!$enabled) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('AI practice is not enabled for this case.', 'meddigest-ai-sca'); ?></div>
        <?php elseif (!$premium) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('Active SCA Cases Premium is required to launch AI practice.', 'meddigest-ai-sca'); ?></div>
        <?php elseif (absint($balance['available']) < 1) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('You need at least 1 AI credit to start this station.', 'meddigest-ai-sca'); ?></div>
        <?php endif; ?>

        <section class="mdsca-panel">
            <h2><?php echo esc_html__('Doctor Brief', 'meddigest-ai-sca'); ?></h2>
            <div class="mdsca-doctor-brief">
                <?php echo wp_kses_post(wpautop($setup['doctor_brief'] ?? '')); ?>
            </div>
        </section>

        <section class="mdsca-panel">
            <h2><?php echo esc_html__('Checks', 'meddigest-ai-sca'); ?></h2>
            <label><input type="checkbox" class="mdsca-required-check"> <?php echo esc_html__('I understand this is educational practice, not clinical advice.', 'meddigest-ai-sca'); ?></label>
            <label><input type="checkbox" class="mdsca-required-check"> <?php echo esc_html__('I am ready to use my microphone for this AI consultation.', 'meddigest-ai-sca'); ?></label>
            <label><input type="checkbox" class="mdsca-required-check"> <?php echo esc_html__('I understand 1 credit is held now and debited when the live station begins.', 'meddigest-ai-sca'); ?></label>
        </section>

        <button type="button" class="mdsca-button mdsca-start-station" <?php disabled(!$can_begin); ?>>
            <?php echo esc_html__('Begin AI Consultation', 'meddigest-ai-sca'); ?>
        </button>

        <div class="mdsca-station-message" role="status" aria-live="polite"></div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
