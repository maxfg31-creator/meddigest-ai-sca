<?php
/**
 * Idempotency helpers.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Credits;

if (!defined('ABSPATH')) {
    exit;
}

final class Idempotency
{
    /**
     * Build a safe idempotency key.
     *
     * @param string $parts Key parts.
     */
    public static function key(...$parts)
    {
        $normalized = array_map(
            static function ($part) {
                return sanitize_key((string) $part);
            },
            $parts
        );

        return substr(implode(':', $normalized), 0, 191);
    }
}

