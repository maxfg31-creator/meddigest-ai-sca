<?php
/**
 * MemberPress AI Practice tab.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$has_premium    = !empty($has_premium);
$balance        = isset($balance) && is_array($balance) ? $balance : ['available' => 0, 'locked' => 0, 'total' => 0];
$active_station = isset($active_station) && is_array($active_station) ? $active_station : null;
$active_mock    = isset($active_mock) && is_array($active_mock) ? $active_mock : null;
$history        = isset($history) && is_array($history) ? $history : ['items' => [], 'page' => 1, 'total_pages' => 0];
$pricing_url    = isset($pricing_url) ? (string) $pricing_url : home_url('/pricing/#ai-credits');
?>
<section class="mdsca-account-tab">
    <h2><?php echo esc_html__('AI Practice', 'meddigest-ai-sca'); ?></h2>

    <div class="mdsca-account-summary">
        <div>
            <strong><?php echo esc_html(absint($balance['available'])); ?></strong>
            <span><?php echo esc_html__('Available AI credits', 'meddigest-ai-sca'); ?></span>
        </div>
        <div>
            <strong><?php echo esc_html(absint($balance['locked'])); ?></strong>
            <span><?php echo esc_html__('Locked credits', 'meddigest-ai-sca'); ?></span>
        </div>
        <div>
            <a class="mdsca-button" href="<?php echo esc_url($pricing_url); ?>"><?php echo esc_html__('Buy AI Credits', 'meddigest-ai-sca'); ?></a>
        </div>
    </div>

    <?php if (!$has_premium) : ?>
        <div class="mdsca-notice mdsca-notice-warning">
            <?php echo esc_html__('Your AI history remains available. Active SCA Cases Premium is required to launch new AI practice.', 'meddigest-ai-sca'); ?>
        </div>
    <?php endif; ?>

    <?php if ($active_station || $active_mock) : ?>
        <section class="mdsca-account-active">
            <h3><?php echo esc_html__('Active Practice', 'meddigest-ai-sca'); ?></h3>
            <?php if ($active_station) : ?>
                <p>
                    <a href="<?php echo esc_url(home_url('/sca-ai/station/' . $active_station['attempt_uuid'] . '/live/')); ?>">
                        <?php echo esc_html__('Resume AI Consultation', 'meddigest-ai-sca'); ?>
                    </a>
                </p>
            <?php endif; ?>
            <?php if ($active_mock) : ?>
                <?php
                $mock_url = \MedDigest\AiSca\Mock\MockLaunchService::STATUS_PROCESSING === $active_mock['status']
                    ? home_url('/sca-ai/mock/' . $active_mock['mock_uuid'] . '/results/')
                    : home_url('/sca-ai/mock/' . $active_mock['mock_uuid'] . '/run/');
                ?>
                <p>
                    <a href="<?php echo esc_url($mock_url); ?>">
                        <?php echo esc_html__('Resume Full Mock SCA', 'meddigest-ai-sca'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php
    echo (new \MedDigest\AiSca\Frontend\TemplateLoader())->render(
        'account/ai-history-list.php',
        [
            'history' => $history,
        ]
    );
    ?>
</section>
