<?php
/**
 * UUID helper.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Uuid
{
    /**
     * Generate a UUIDv4 string.
     */
    public static function v4()
    {
        if (function_exists('wp_generate_uuid4')) {
            return wp_generate_uuid4();
        }

        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

