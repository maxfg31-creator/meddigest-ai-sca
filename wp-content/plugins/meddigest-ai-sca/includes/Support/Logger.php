<?php
/**
 * Defensive logger.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Logger
{
    /**
     * Log an error only when WordPress debugging is enabled.
     *
     * @param string $message Message.
     */
    public static function error($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[MedDigest AI SCA] ' . $message);
        }
    }
}

