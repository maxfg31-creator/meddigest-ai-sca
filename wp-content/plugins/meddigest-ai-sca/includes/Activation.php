<?php
/**
 * Activation tasks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca;

use MedDigest\AiSca\Database\Installer;
use MedDigest\AiSca\Frontend\RouteRegistrar;
use MedDigest\AiSca\MemberPress\ProductMappingService;

if (!defined('ABSPATH')) {
    exit;
}

final class Activation
{
    /**
     * Run activation tasks.
     */
    public static function activate()
    {
        (new Installer())->install();
        ProductMappingService::ensure_default_settings();
        (new RouteRegistrar())->add_rewrite_rules();

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }
}
