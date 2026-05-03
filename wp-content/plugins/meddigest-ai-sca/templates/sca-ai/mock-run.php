<?php
/**
 * Full Mock run route.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$mock_uuid = sanitize_text_field(get_query_var('mdsca_mock_uuid'));
$status    = (new \MedDigest\AiSca\Mock\MockRunner())->status(get_current_user_id(), $mock_uuid);

if (is_wp_error($status)) {
    wp_die(esc_html($status->get_error_message()), esc_html__('Full Mock SCA', 'meddigest-ai-sca'), ['response' => 403]);
}

wp_enqueue_style('meddigest-ai-sca-mock');
wp_enqueue_script('meddigest-ai-sca-mock-runner');
wp_localize_script(
    'meddigest-ai-sca-mock-runner',
    'mdscaMock',
    [
        'restUrl'  => esc_url_raw(rest_url(MEDDIGEST_AI_SCA_REST_NAMESPACE)),
        'nonce'    => wp_create_nonce('wp_rest'),
        'mockUuid' => $mock_uuid,
    ]
);
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('mdsca-ai-template mdsca-mock-run-page'); ?>>
    <main class="mdsca-mock mdsca-mock-run" data-mdsca-mock-uuid="<?php echo esc_attr($mock_uuid); ?>">
        <h1><?php echo esc_html__('Full Mock SCA', 'meddigest-ai-sca'); ?></h1>
        <p class="mdsca-badge" data-mdsca-mock-phase-label><?php echo esc_html(ucfirst($status['phase'])); ?></p>
        <div class="mdsca-mock-timer" data-mdsca-mock-timer><?php echo esc_html(gmdate('i:s', absint($status['seconds_remaining']))); ?></div>
        <section class="mdsca-mock-phase">
            <h2 data-mdsca-mock-station-heading>
                <?php echo esc_html(sprintf(__('Station %d of 12', 'meddigest-ai-sca'), absint($status['station_number']))); ?>
            </h2>
            <div data-mdsca-mock-phase-body>
                <?php if ('reading' === $status['phase'] && !empty($status['current_station'])) : ?>
                    <h3><?php echo esc_html($status['current_station']['title']); ?></h3>
                    <div><?php echo wp_kses_post(wpautop($status['current_station']['doctor_brief'])); ?></div>
                <?php elseif ('live' === $status['phase']) : ?>
                    <p><?php echo esc_html__('Live AI consultation in progress.', 'meddigest-ai-sca'); ?></p>
                <?php elseif ('break' === $status['phase']) : ?>
                    <p><?php echo esc_html__('10-minute break after station 6.', 'meddigest-ai-sca'); ?></p>
                <?php else : ?>
                    <p><?php echo esc_html__('Your mock is being processed.', 'meddigest-ai-sca'); ?></p>
                <?php endif; ?>
            </div>
            <div class="mdsca-mock-audio" data-mdsca-mock-audio aria-label="<?php echo esc_attr__('Full mock live audio area', 'meddigest-ai-sca'); ?>"></div>
        </section>
        <div class="mdsca-mock-message" role="status" aria-live="polite"></div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
