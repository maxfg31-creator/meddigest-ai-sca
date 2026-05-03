<?php
/**
 * Frontend asset registration.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Assets;

if (!defined('ABSPATH')) {
    exit;
}

final class FrontendAssets
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Register frontend assets.
     */
    public function register_assets()
    {
        wp_register_style(
            'meddigest-ai-sca-frontend',
            MEDDIGEST_AI_SCA_URL . 'assets/css/frontend.css',
            [],
            MEDDIGEST_AI_SCA_VERSION
        );
    }
}

