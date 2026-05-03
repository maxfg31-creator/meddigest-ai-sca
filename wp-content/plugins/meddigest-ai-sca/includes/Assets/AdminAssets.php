<?php
/**
 * Admin asset registration.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Assets;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminAssets
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook_suffix Hook suffix.
     */
    public function enqueue($hook_suffix)
    {
        if ('settings_page_meddigest-ai-sca' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'meddigest-ai-sca-admin',
            MEDDIGEST_AI_SCA_URL . 'assets/css/admin.css',
            [],
            MEDDIGEST_AI_SCA_VERSION
        );
    }
}

