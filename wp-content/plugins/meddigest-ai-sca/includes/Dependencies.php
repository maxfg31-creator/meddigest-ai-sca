<?php
/**
 * Dependency checks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca;

use MedDigest\AiSca\Admin\Notices;

if (!defined('ABSPATH')) {
    exit;
}

final class Dependencies
{
    /**
     * Register dependency notices.
     */
    public function register()
    {
        add_action('admin_notices', [$this, 'render_missing_dependency_notices']);
    }

    /**
     * Whether MemberPress appears to be available.
     */
    public static function has_memberpress()
    {
        return defined('MEPR_VERSION') || class_exists('MeprTransaction') || class_exists('MeprUser');
    }

    /**
     * Whether ACF appears to be available.
     */
    public static function has_acf()
    {
        return function_exists('acf_add_local_field_group') || class_exists('ACF');
    }

    /**
     * Render dependency notices in admin.
     */
    public function render_missing_dependency_notices()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!self::has_memberpress()) {
            Notices::render(
                __('MedDigest AI SCA requires MemberPress for memberships, checkout, and completed transaction credit issuance.', 'meddigest-ai-sca'),
                'warning'
            );
        }

        if (!self::has_acf()) {
            Notices::render(
                __('MedDigest AI SCA expects Advanced Custom Fields for case editor fields. Case AI settings will be unavailable until ACF is active.', 'meddigest-ai-sca'),
                'warning'
            );
        }
    }
}
