<?php
/**
 * Main plugin coordinator.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca;

use MedDigest\AiSca\Admin\Admin;
use MedDigest\AiSca\ACF\CaseConfigSync;
use MedDigest\AiSca\ACF\CaseFieldGroups;
use MedDigest\AiSca\Assets\AdminAssets;
use MedDigest\AiSca\Assets\FrontendAssets;
use MedDigest\AiSca\Database\Installer;
use MedDigest\AiSca\Frontend\RouteRegistrar;
use MedDigest\AiSca\Frontend\Shortcodes;
use MedDigest\AiSca\MemberPress\TransactionHandler;
use MedDigest\AiSca\Practice\FeedbackJob;
use MedDigest\AiSca\REST\RestApi;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin services.
     */
    public function init()
    {
        $this->load_textdomain();

        (new Dependencies())->register();
        (new Installer())->register();
        (new FrontendAssets())->register();
        (new AdminAssets())->register();
        (new CaseFieldGroups())->register();
        (new CaseConfigSync())->register();
        (new RouteRegistrar())->register();
        (new Shortcodes())->register();
        (new TransactionHandler())->register();
        (new FeedbackJob())->register();
        (new RestApi())->register();

        if (is_admin()) {
            (new Admin())->register();
        }
    }

    /**
     * Load translations if WordPress is available.
     */
    private function load_textdomain()
    {
        if (function_exists('load_plugin_textdomain')) {
            load_plugin_textdomain(
                'meddigest-ai-sca',
                false,
                dirname(plugin_basename(MEDDIGEST_AI_SCA_FILE)) . '/languages'
            );
        }
    }

    private function __construct()
    {
    }
}
