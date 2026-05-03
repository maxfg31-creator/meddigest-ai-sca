<?php
/**
 * Time helper.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Clock
{
    /**
     * Current UTC time in MySQL format.
     */
    public static function mysql_utc()
    {
        if (function_exists('current_time')) {
            return current_time('mysql', true);
        }

        return gmdate('Y-m-d H:i:s');
    }
}

