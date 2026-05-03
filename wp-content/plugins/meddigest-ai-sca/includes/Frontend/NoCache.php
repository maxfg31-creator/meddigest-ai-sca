<?php
/**
 * No-cache helper for future owner-only routes.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

final class NoCache
{
    /**
     * Send private no-cache headers when possible.
     */
    public function send()
    {
        if (function_exists('nocache_headers') && !headers_sent()) {
            nocache_headers();
        }
    }
}
