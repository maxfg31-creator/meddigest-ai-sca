<?php
/**
 * Credit pack card.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$pack       = isset($pack) && is_array($pack) ? $pack : [];
$configured = !empty($pack['configured']);
$url        = isset($pack['url']) ? (string) $pack['url'] : '';
?>
<article class="mdsca-credit-pack">
    <div class="mdsca-credit-pack__body">
        <h3><?php echo esc_html($pack['label'] ?? ''); ?></h3>
        <p class="mdsca-credit-pack__price"><?php echo esc_html($pack['price_label'] ?? ''); ?></p>
        <p class="mdsca-credit-pack__credits">
            <?php
            printf(
                esc_html__('%d credits', 'meddigest-ai-sca'),
                absint($pack['credits'] ?? 0)
            );
            ?>
        </p>
        <p class="mdsca-credit-pack__note"><?php echo esc_html($pack['note'] ?? ''); ?></p>
    </div>

    <div class="mdsca-credit-pack__action">
        <?php if ($configured && $url) : ?>
            <a class="mdsca-button" href="<?php echo esc_url($url); ?>">
                <?php echo esc_html__('Add Credits', 'meddigest-ai-sca'); ?>
            </a>
        <?php elseif (current_user_can('manage_options')) : ?>
            <span class="mdsca-button mdsca-button--disabled" aria-disabled="true">
                <?php echo esc_html__('Map product in settings', 'meddigest-ai-sca'); ?>
            </span>
        <?php else : ?>
            <span class="mdsca-button mdsca-button--disabled" aria-disabled="true">
                <?php echo esc_html__('Unavailable', 'meddigest-ai-sca'); ?>
            </span>
        <?php endif; ?>
    </div>
</article>

