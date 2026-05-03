<?php
/**
 * Route registrar for /sca-ai/* routes.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

final class RouteRegistrar
{
    /**
     * Register /sca-ai/* station routes.
     */
    public function register()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_template']);
    }

    /**
     * Add rewrite rules.
     */
    public function add_rewrite_rules()
    {
        add_rewrite_rule('^sca-ai/station/([^/]+)/setup/?$', 'index.php?mdsca_ai_route=station_setup&mdsca_case_slug=$matches[1]', 'top');
        add_rewrite_rule('^sca-ai/station/([a-f0-9-]{36})/live/?$', 'index.php?mdsca_ai_route=station_live&mdsca_attempt_uuid=$matches[1]', 'top');
        add_rewrite_rule('^sca-ai/station/([a-f0-9-]{36})/feedback/?$', 'index.php?mdsca_ai_route=station_feedback&mdsca_attempt_uuid=$matches[1]', 'top');
        add_rewrite_rule('^sca-ai/mock/launch/?$', 'index.php?mdsca_ai_route=mock_launch', 'top');
        add_rewrite_rule('^sca-ai/mock/([a-f0-9-]{36})/run/?$', 'index.php?mdsca_ai_route=mock_run&mdsca_mock_uuid=$matches[1]', 'top');
        add_rewrite_rule('^sca-ai/mock/([a-f0-9-]{36})/results/?$', 'index.php?mdsca_ai_route=mock_results&mdsca_mock_uuid=$matches[1]', 'top');
    }

    /**
     * Add query vars.
     *
     * @param array $vars Vars.
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'mdsca_ai_route';
        $vars[] = 'mdsca_case_slug';
        $vars[] = 'mdsca_attempt_uuid';
        $vars[] = 'mdsca_mock_uuid';

        return $vars;
    }

    /**
     * Load minimal route templates.
     *
     * @param string $template Template path.
     */
    public function load_template($template)
    {
        $route = get_query_var('mdsca_ai_route');

        if (!$route) {
            return $template;
        }

        if (function_exists('nocache_headers')) {
            (new NoCache())->send();
        }

        $map = [
            'station_setup'    => 'templates/sca-ai/station-setup.php',
            'station_live'     => 'templates/sca-ai/station-live.php',
            'station_feedback' => 'templates/sca-ai/station-feedback.php',
            'mock_launch'      => 'templates/sca-ai/mock-launch.php',
            'mock_run'         => 'templates/sca-ai/mock-run.php',
            'mock_results'     => 'templates/sca-ai/mock-results.php',
        ];

        if (empty($map[$route])) {
            return $template;
        }

        $path = MEDDIGEST_AI_SCA_DIR . $map[$route];

        return is_readable($path) ? $path : $template;
    }
}
