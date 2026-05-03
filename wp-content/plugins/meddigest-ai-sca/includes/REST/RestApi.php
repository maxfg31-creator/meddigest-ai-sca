<?php
/**
 * REST API coordinator.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\REST;

if (!defined('ABSPATH')) {
    exit;
}

final class RestApi
{
    /**
     * Register REST routes.
     */
    public function register()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register route controllers.
     */
    public function register_routes()
    {
        (new MeStateController())->register_routes();
    }
}

