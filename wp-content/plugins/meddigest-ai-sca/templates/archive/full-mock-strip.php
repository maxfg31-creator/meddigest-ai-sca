<?php
/**
 * SCA archive Full Mock strip.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$default_label = isset($default_label) ? (string) $default_label : __('Join SCA Cases Premium', 'meddigest-ai-sca');
$default_url   = isset($default_url) ? (string) $default_url : home_url('/pricing/#sca-cases-premium');
?>
<section class="mdsca-full-mock-strip" data-mdsca-full-mock-strip data-mdsca-state="public">
    <div class="mdsca-full-mock-strip__main">
        <p class="mdsca-kicker"><?php echo esc_html__('Full Mock SCA', 'meddigest-ai-sca'); ?></p>
        <h2><?php echo esc_html__('12-station timed AI mock', 'meddigest-ai-sca'); ?></h2>
        <p data-mdsca-full-mock-note>
            <?php echo esc_html__('Practice the complete SCA sequence with 12 timed AI consultations. Requires active SCA Cases Premium and 12 AI credits.', 'meddigest-ai-sca'); ?>
        </p>
    </div>
    <div class="mdsca-full-mock-strip__action">
        <a class="mdsca-button" data-mdsca-full-mock-cta href="<?php echo esc_url($default_url); ?>">
            <?php echo esc_html($default_label); ?>
        </a>
    </div>
</section>
