<?php
/**
 * AI credit pack section.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$packs   = isset($packs) && is_array($packs) ? $packs : [];
$balance = isset($balance) && is_array($balance) ? $balance : ['available' => 0, 'locked' => 0, 'total' => 0];
?>
<section id="ai-credits" class="mdsca-credit-packs" aria-labelledby="mdsca-credit-packs-title">
    <div class="mdsca-credit-packs__header">
        <h2 id="mdsca-credit-packs-title"><?php echo esc_html__('AI Consultation Credits', 'meddigest-ai-sca'); ?></h2>
        <p>
            <?php
            printf(
                esc_html__('You currently have %d AI consultation credits available.', 'meddigest-ai-sca'),
                absint($balance['available'])
            );
            ?>
        </p>
    </div>

    <div class="mdsca-credit-packs__grid">
        <?php foreach ($packs as $pack) : ?>
            <?php include MEDDIGEST_AI_SCA_DIR . 'templates/components/credit-pack-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <p class="mdsca-credit-packs__note">
        <?php echo esc_html__('12 credits required to launch Full Mock SCA.', 'meddigest-ai-sca'); ?>
    </p>
</section>

