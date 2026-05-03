<?php
/**
 * Generic status notice component.
 *
 * @package MedDigest\AiSca
 */

if (!defined('ABSPATH')) {
    exit;
}

$message = isset($message) ? (string) $message : '';
$type    = isset($type) ? sanitize_html_class((string) $type) : 'info';
?>
<div class="mdsca-notice mdsca-notice-<?php echo esc_attr($type); ?>">
    <?php echo esc_html($message); ?>
</div>

