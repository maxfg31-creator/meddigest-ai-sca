<?php
/**
 * Full Mock results route.
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
$payload   = (new \MedDigest\AiSca\Mock\MockResultsAggregator())->results(get_current_user_id(), $mock_uuid);

if (is_wp_error($payload)) {
    wp_die(esc_html($payload->get_error_message()), esc_html__('Full Mock Results', 'meddigest-ai-sca'), ['response' => 403]);
}

$results = isset($payload['results']) && is_array($payload['results']) ? $payload['results'] : [];

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
<body <?php body_class('mdsca-ai-template mdsca-mock-results-page'); ?>>
    <main class="mdsca-mock mdsca-mock-results" data-mdsca-mock-results data-mdsca-results-status="<?php echo esc_attr($results['status'] ?? $payload['status']); ?>">
        <h1><?php echo esc_html__('Full Mock SCA Results', 'meddigest-ai-sca'); ?></h1>

        <?php if (($results['status'] ?? '') !== 'completed') : ?>
            <div class="mdsca-notice">
                <?php echo esc_html($results['message'] ?? __('Generating your final mock results...', 'meddigest-ai-sca')); ?>
            </div>
        <?php else : ?>
            <p><?php echo esc_html(sprintf(__('Completed %d stations.', 'meddigest-ai-sca'), absint($results['station_count'] ?? 0))); ?></p>
            <?php if (!empty($results['stations']) && is_array($results['stations'])) : ?>
                <ul class="mdsca-mock-results-list">
                    <?php foreach ($results['stations'] as $station) : ?>
                        <?php
                        if (!is_array($station)) {
                            continue;
                        }
                        ?>
                        <li>
                            <strong><?php echo esc_html(sprintf(__('Station %d', 'meddigest-ai-sca'), absint($station['station_number'] ?? 0))); ?></strong>
                            <span><?php echo esc_html($station['case_title'] ?? ''); ?></span>
                            <p><?php echo esc_html($station['practice_verdict'] ?? ''); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
        <div class="mdsca-mock-message" role="status" aria-live="polite"></div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
