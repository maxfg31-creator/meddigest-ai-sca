<?php
/**
 * Database installer and migrator.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const VERSION_OPTION = 'meddigest_ai_sca_db_version';

    /**
     * Register lightweight upgrade checks.
     */
    public function register()
    {
        add_action('init', [$this, 'maybe_upgrade'], 1);
        add_action('admin_init', [$this, 'maybe_upgrade']);
    }

    /**
     * Install or update plugin tables.
     */
    public function install()
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        foreach ((new Schema())->sql() as $sql) {
            dbDelta($sql);
        }

        update_option(self::VERSION_OPTION, Schema::DB_VERSION, false);
    }

    /**
     * Run dbDelta when schema version changes.
     */
    public function maybe_upgrade()
    {
        $installed_version = get_option(self::VERSION_OPTION);

        if (Schema::DB_VERSION !== $installed_version) {
            $this->install();
        }
    }
}
