<?php
/**
 * Single station live route.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$attempt_uuid = sanitize_text_field(get_query_var('mdsca_attempt_uuid'));
$attempt      = (new \MedDigest\AiSca\Practice\StationAttemptService())->get_owned_attempt(get_current_user_id(), $attempt_uuid);

if (is_wp_error($attempt)) {
    wp_die(esc_html($attempt->get_error_message()), esc_html__('AI Station', 'meddigest-ai-sca'), ['response' => 403]);
}

wp_enqueue_style('meddigest-ai-sca-station');
wp_enqueue_script('meddigest-ai-sca-station-live');
wp_localize_script(
    'meddigest-ai-sca-station-live',
    'mdscaStation',
    [
        'restUrl'     => esc_url_raw(rest_url(MEDDIGEST_AI_SCA_REST_NAMESPACE)),
        'nonce'       => wp_create_nonce('wp_rest'),
        'attemptUuid' => $attempt_uuid,
    ]
);

$snapshot = !empty($attempt['snapshot_json']) ? json_decode($attempt['snapshot_json'], true) : [];
$snapshot = is_array($snapshot) ? $snapshot : [];
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('mdsca-ai-template mdsca-station-live-page'); ?>>
    <main class="mdsca-station mdsca-station-live" data-mdsca-attempt-uuid="<?php echo esc_attr($attempt_uuid); ?>">
        <h1><?php echo esc_html($snapshot['title'] ?? __('AI Consultation', 'meddigest-ai-sca')); ?></h1>
        <p class="mdsca-badge"><?php echo esc_html__('12-minute live station', 'meddigest-ai-sca'); ?></p>
        <div class="mdsca-timer" data-mdsca-hard-stop="<?php echo esc_attr($attempt['hard_stop_at']); ?>">12:00</div>
        <div class="mdsca-audio-status" role="status" aria-live="polite">
            <?php echo esc_html__('Preparing secure Realtime session...', 'meddigest-ai-sca'); ?>
        </div>
        <div class="mdsca-live-audio" aria-label="<?php echo esc_attr__('Live AI audio area', 'meddigest-ai-sca'); ?>"></div>
        <button type="button" class="mdsca-button mdsca-end-station">
            <?php echo esc_html__('End Consultation', 'meddigest-ai-sca'); ?>
        </button>
        <div class="mdsca-station-message" role="status" aria-live="polite"></div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
