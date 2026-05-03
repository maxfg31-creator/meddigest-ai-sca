<?php
/**
 * AI Practice history list.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$history = isset($history) && is_array($history) ? $history : ['items' => [], 'page' => 1, 'total_pages' => 0];
$items   = isset($history['items']) && is_array($history['items']) ? $history['items'] : [];
?>
<section class="mdsca-account-history">
    <h3><?php echo esc_html__('History', 'meddigest-ai-sca'); ?></h3>

    <?php if (empty($items)) : ?>
        <p><?php echo esc_html__('No AI practice history yet.', 'meddigest-ai-sca'); ?></p>
    <?php else : ?>
        <ul class="mdsca-account-history__list">
            <?php foreach ($items as $item) : ?>
                <?php
                if (!is_array($item)) {
                    continue;
                }
                ?>
                <li>
                    <div>
                        <strong><?php echo esc_html($item['title'] ?? ''); ?></strong>
                        <span><?php echo esc_html(ucfirst($item['type'] ?? '')); ?> · <?php echo esc_html($item['status'] ?? ''); ?></span>
                    </div>
                    <a href="<?php echo esc_url($item['url'] ?? '#'); ?>">
                        <?php echo esc_html(in_array(($item['status'] ?? ''), ['setup', 'live', 'running', 'processing'], true) ? __('Resume', 'meddigest-ai-sca') : __('View Results', 'meddigest-ai-sca')); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (absint($history['total_pages'] ?? 0) > 1) : ?>
        <nav class="mdsca-account-history__pagination" aria-label="<?php echo esc_attr__('AI Practice history pagination', 'meddigest-ai-sca'); ?>">
            <?php
            $page        = absint($history['page']);
            $total_pages = absint($history['total_pages']);
            ?>
            <?php if ($page > 1) : ?>
                <a href="<?php echo esc_url(add_query_arg('mdsca_ai_page', $page - 1)); ?>"><?php echo esc_html__('Previous', 'meddigest-ai-sca'); ?></a>
            <?php endif; ?>
            <span><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'meddigest-ai-sca'), $page, $total_pages)); ?></span>
            <?php if ($page < $total_pages) : ?>
                <a href="<?php echo esc_url(add_query_arg('mdsca_ai_page', $page + 1)); ?>"><?php echo esc_html__('Next', 'meddigest-ai-sca'); ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
