<?php
/**
 * Admin notice helper.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class Notices
{
    /**
     * Render a notice.
     *
     * @param string $message Message.
     * @param string $type    Notice type.
     */
    public static function render($message, $type = 'info')
    {
        $allowed = ['info', 'success', 'warning', 'error'];
        $type    = in_array($type, $allowed, true) ? $type : 'info';

        printf(
            '<div class="notice notice-%1$s"><p>%2$s</p></div>',
            esc_attr($type),
            wp_kses_post($message)
        );
    }
}

