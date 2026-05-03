<?php
/**
 * Single station feedback route.
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
    wp_die(esc_html($attempt->get_error_message()), esc_html__('AI Feedback', 'meddigest-ai-sca'), ['response' => 403]);
}

$feedback = (new \MedDigest\AiSca\Practice\FeedbackJob())->get_feedback($attempt_uuid);
$full_feedback = [];

if ($feedback && !empty($feedback['full_feedback_json'])) {
    $decoded_feedback = json_decode($feedback['full_feedback_json'], true);
    $full_feedback    = is_array($decoded_feedback) ? $decoded_feedback : [];
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
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('mdsca-ai-template mdsca-station-feedback-page'); ?>>
    <main class="mdsca-station mdsca-station-feedback" data-mdsca-attempt-uuid="<?php echo esc_attr($attempt_uuid); ?>" data-mdsca-feedback-status="<?php echo esc_attr($feedback['processing_status'] ?? 'pending'); ?>">
        <h1><?php echo esc_html__('AI Consultation Feedback', 'meddigest-ai-sca'); ?></h1>
        <?php if (!$feedback || !in_array($feedback['processing_status'], ['completed', 'requires_openai_configuration', 'failed'], true)) : ?>
            <div class="mdsca-notice">
                <?php echo esc_html__('Generating feedback...', 'meddigest-ai-sca'); ?>
            </div>
        <?php else : ?>
            <section class="mdsca-panel">
                <h2><?php echo esc_html__('Practice Verdict', 'meddigest-ai-sca'); ?></h2>
                <p><?php echo esc_html($feedback['practice_verdict']); ?></p>
                <?php if ('completed' === $feedback['processing_status']) : ?>
                    <dl class="mdsca-feedback-grades">
                        <dt><?php echo esc_html__('Data Gathering and Diagnosis', 'meddigest-ai-sca'); ?></dt>
                        <dd><?php echo esc_html($feedback['dgd_grade']); ?></dd>
                        <dt><?php echo esc_html__('Clinical Management and Communication', 'meddigest-ai-sca'); ?></dt>
                        <dd><?php echo esc_html($feedback['cmc_grade']); ?></dd>
                        <dt><?php echo esc_html__('Relating to Others', 'meddigest-ai-sca'); ?></dt>
                        <dd><?php echo esc_html($feedback['rto_grade']); ?></dd>
                    </dl>
                <?php endif; ?>
            </section>
            <?php if ('completed' === $feedback['processing_status']) : ?>
                <?php
                $feedback_sections = [
                    'strengths'               => __('Strengths', 'meddigest-ai-sca'),
                    'critical_misses'         => __('Critical Misses', 'meddigest-ai-sca'),
                    'missing_questions'       => __('Missing Questions and Explanations', 'meddigest-ai-sca'),
                    'safety_netting_problems' => __('Safety-netting Problems', 'meddigest-ai-sca'),
                    'transcript_evidence'     => __('Transcript Evidence', 'meddigest-ai-sca'),
                    'improvements'            => __('Further Improvements', 'meddigest-ai-sca'),
                ];
                ?>
                <?php foreach ($feedback_sections as $section_key => $section_label) : ?>
                    <?php if (!empty($full_feedback[$section_key]) && is_array($full_feedback[$section_key])) : ?>
                        <section class="mdsca-panel">
                            <h2><?php echo esc_html($section_label); ?></h2>
                            <ul class="mdsca-feedback-list">
                                <?php foreach ($full_feedback[$section_key] as $item) : ?>
                                    <?php
                                    if (!is_array($item)) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <strong><?php echo esc_html($item['point'] ?? ''); ?></strong>
                                        <?php if (!empty($item['evidence'])) : ?>
                                            <span><?php echo esc_html($item['evidence']); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!empty($full_feedback['three_priorities']) && is_array($full_feedback['three_priorities'])) : ?>
                    <section class="mdsca-panel">
                        <h2><?php echo esc_html__('Three Priorities', 'meddigest-ai-sca'); ?></h2>
                        <ol class="mdsca-feedback-list">
                            <?php foreach ($full_feedback['three_priorities'] as $priority) : ?>
                                <li><?php echo esc_html($priority); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                <?php endif; ?>
                <?php if (!empty($full_feedback['evidence_gaps']) && is_array($full_feedback['evidence_gaps'])) : ?>
                    <section class="mdsca-panel">
                        <h2><?php echo esc_html__('Evidence Gaps', 'meddigest-ai-sca'); ?></h2>
                        <ul class="mdsca-feedback-list">
                            <?php foreach ($full_feedback['evidence_gaps'] as $gap) : ?>
                                <li><?php echo esc_html($gap); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        <div class="mdsca-station-message" role="status" aria-live="polite"></div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
