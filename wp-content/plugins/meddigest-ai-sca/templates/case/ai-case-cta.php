<?php
/**
 * Case-page AI CTA.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$case_id     = isset($case_id) ? absint($case_id) : 0;
$balance     = isset($balance) && is_array($balance) ? $balance : ['available' => 0, 'locked' => 0, 'total' => 0];
$has_premium = !empty($has_premium);
$active      = isset($active) && is_array($active) ? $active : null;
$setup_url   = isset($setup_url) ? (string) $setup_url : '';
$pricing_url = isset($pricing_url) ? (string) $pricing_url : '';
$premium_url = isset($premium_url) ? (string) $premium_url : '';
$active_same_case = $active && absint($active['case_post_id']) === $case_id;
?>
<section class="mdsca-case-cta" data-mdsca-case-id="<?php echo esc_attr($case_id); ?>">
    <div class="mdsca-case-cta__copy">
        <h2><?php echo esc_html__('AI Consultation Practice', 'meddigest-ai-sca'); ?></h2>

        <?php if ($active_same_case) : ?>
            <p><?php echo esc_html__('You have an active AI consultation for this case.', 'meddigest-ai-sca'); ?></p>
        <?php elseif ($active) : ?>
            <p><?php echo esc_html__('You have another active AI consultation. Resume it before starting a new station.', 'meddigest-ai-sca'); ?></p>
        <?php elseif (!$has_premium) : ?>
            <p><?php echo esc_html__('Upgrade to SCA Cases Premium to use AI consultation practice.', 'meddigest-ai-sca'); ?></p>
        <?php elseif (absint($balance['available']) > 0) : ?>
            <p>
                <?php
                printf(
                    esc_html__('Use 1 AI credit for a 12-minute consultation. You have %d credits available.', 'meddigest-ai-sca'),
                    absint($balance['available'])
                );
                ?>
            </p>
        <?php else : ?>
            <p><?php echo esc_html__('AI consultation credits are required to start this station.', 'meddigest-ai-sca'); ?></p>
        <?php endif; ?>
    </div>

    <div class="mdsca-case-cta__action">
        <?php if ($active) : ?>
            <a class="mdsca-button" href="<?php echo esc_url(home_url('/sca-ai/station/' . $active['attempt_uuid'] . '/live/')); ?>">
                <?php echo esc_html__('Resume AI Consultation', 'meddigest-ai-sca'); ?>
            </a>
        <?php elseif (!$has_premium) : ?>
            <a class="mdsca-button" href="<?php echo esc_url($premium_url); ?>">
                <?php echo esc_html__('Upgrade to SCA Cases Premium', 'meddigest-ai-sca'); ?>
            </a>
        <?php elseif (absint($balance['available']) > 0) : ?>
            <a class="mdsca-button" href="<?php echo esc_url($setup_url); ?>">
                <?php echo esc_html__('Start 12-Min AI Consultation', 'meddigest-ai-sca'); ?>
            </a>
            <a class="mdsca-link" href="<?php echo esc_url($pricing_url); ?>">
                <?php echo esc_html__('Buy more AI credits', 'meddigest-ai-sca'); ?>
            </a>
        <?php else : ?>
            <a class="mdsca-button" href="<?php echo esc_url($pricing_url); ?>">
                <?php echo esc_html__('Buy AI Credits', 'meddigest-ai-sca'); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
