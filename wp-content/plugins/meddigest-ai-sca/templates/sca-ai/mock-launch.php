<?php
/**
 * Full Mock launch route.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$user_id        = get_current_user_id();
$mock_service   = new \MedDigest\AiSca\Mock\MockLaunchService();
$active_mock    = $mock_service->get_active_mock_for_user($user_id);
$premium        = (new \MedDigest\AiSca\MemberPress\EligibilityService())->user_has_sca_cases_premium($user_id);
$balance        = (new \MedDigest\AiSca\Credits\CreditService())->get_balance($user_id);
$active_station = (new \MedDigest\AiSca\Practice\StationAttemptService())->get_active_attempt_for_user($user_id);
$coverage       = (new \MedDigest\AiSca\Mock\MockCoverageService())->coverage_report();
$can_start      = !$active_mock && !$active_station && $premium && absint($balance['available']) >= 12 && !empty($coverage['ready']);
$active_mock_url = $active_mock && \MedDigest\AiSca\Mock\MockLaunchService::STATUS_PROCESSING === $active_mock['status']
    ? home_url('/sca-ai/mock/' . $active_mock['mock_uuid'] . '/results/')
    : ($active_mock ? home_url('/sca-ai/mock/' . $active_mock['mock_uuid'] . '/run/') : '');

wp_enqueue_style('meddigest-ai-sca-mock');
wp_enqueue_script('meddigest-ai-sca-mock-runner');
wp_localize_script(
    'meddigest-ai-sca-mock-runner',
    'mdscaMock',
    [
        'restUrl' => esc_url_raw(rest_url(MEDDIGEST_AI_SCA_REST_NAMESPACE)),
        'nonce'   => wp_create_nonce('wp_rest'),
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
<body <?php body_class('mdsca-ai-template mdsca-mock-launch-page'); ?>>
    <main class="mdsca-mock mdsca-mock-launch" data-mdsca-can-start="<?php echo $can_start ? '1' : '0'; ?>">
        <a class="mdsca-back-link" href="<?php echo esc_url(home_url('/sca-cases/')); ?>">
            <?php echo esc_html__('Back to SCA cases', 'meddigest-ai-sca'); ?>
        </a>
        <h1><?php echo esc_html__('Full Mock SCA', 'meddigest-ai-sca'); ?></h1>
        <p><?php echo esc_html__('A 12-station timed AI mock lasting 3 hours 10 minutes.', 'meddigest-ai-sca'); ?></p>

        <div class="mdsca-mock-grid">
            <div class="mdsca-mock-stat"><strong>12</strong><?php echo esc_html__('Stations', 'meddigest-ai-sca'); ?></div>
            <div class="mdsca-mock-stat"><strong>3:00</strong><?php echo esc_html__('Reading before each station', 'meddigest-ai-sca'); ?></div>
            <div class="mdsca-mock-stat"><strong>12:00</strong><?php echo esc_html__('Live consultation per station', 'meddigest-ai-sca'); ?></div>
        </div>

        <?php if ($active_mock) : ?>
            <div class="mdsca-notice">
                <?php echo esc_html__('You already have an active Full Mock SCA.', 'meddigest-ai-sca'); ?>
                <a href="<?php echo esc_url($active_mock_url); ?>"><?php echo esc_html__('Resume mock', 'meddigest-ai-sca'); ?></a>
            </div>
        <?php elseif ($active_station) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('Finish your active AI consultation before launching a full mock.', 'meddigest-ai-sca'); ?></div>
        <?php elseif (!$premium) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('Active SCA Cases Premium is required to launch Full Mock SCA.', 'meddigest-ai-sca'); ?></div>
        <?php elseif (absint($balance['available']) < 12) : ?>
            <div class="mdsca-notice mdsca-notice-warning"><?php echo esc_html__('12 available AI credits are required to launch Full Mock SCA.', 'meddigest-ai-sca'); ?></div>
        <?php elseif (empty($coverage['ready'])) : ?>
            <div class="mdsca-notice mdsca-notice-warning">
                <?php echo esc_html__('Full Mock SCA is not available because mock group coverage is incomplete.', 'meddigest-ai-sca'); ?>
                <?php if (current_user_can('manage_options')) : ?>
                    <span><?php echo esc_html__('Review the MedDigest AI SCA settings coverage panel.', 'meddigest-ai-sca'); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <section class="mdsca-panel">
            <h2><?php echo esc_html__('Checks', 'meddigest-ai-sca'); ?></h2>
            <label><input type="checkbox" class="mdsca-mock-required-check"> <?php echo esc_html__('I understand this is educational practice, not clinical advice.', 'meddigest-ai-sca'); ?></label>
            <label><input type="checkbox" class="mdsca-mock-required-check"> <?php echo esc_html__('I am ready to use my microphone for 12 separate live AI stations.', 'meddigest-ai-sca'); ?></label>
            <label><input type="checkbox" class="mdsca-mock-required-check"> <?php echo esc_html__('I understand 12 credits are used when station 1 reading begins.', 'meddigest-ai-sca'); ?></label>
        </section>

        <button type="button" class="mdsca-button mdsca-start-mock" <?php disabled(!$can_start); ?>>
            <?php echo esc_html__('Start Full Mock SCA', 'meddigest-ai-sca'); ?>
        </button>
        <div class="mdsca-mock-message" role="status" aria-live="polite"></div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
