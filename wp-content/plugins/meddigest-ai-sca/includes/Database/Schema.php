<?php
/**
 * Database schema definitions.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Schema
{
    public const DB_VERSION = '2026-05-03.1';

    /**
     * Get plugin table names.
     */
    public static function tables()
    {
        global $wpdb;

        return [
            'wallets' => $wpdb->prefix . 'meddigest_ai_wallets',
            'ledger'  => $wpdb->prefix . 'meddigest_ai_ledger',
        ];
    }

    /**
     * Return SQL statements for dbDelta.
     */
    public function sql()
    {
        global $wpdb;

        $tables          = self::tables();
        $charset_collate = $wpdb->get_charset_collate();

        return [
            "CREATE TABLE {$tables['wallets']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                balance_available int(11) NOT NULL DEFAULT 0,
                balance_locked int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_id (user_id),
                KEY balance_available (balance_available),
                KEY updated_at (updated_at)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['ledger']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                ledger_uuid char(36) NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                delta int(11) NOT NULL,
                balance_after int(11) NOT NULL,
                entry_type varchar(40) NOT NULL,
                source_type varchar(80) NOT NULL,
                source_uuid varchar(191) NOT NULL,
                idempotency_key varchar(191) NOT NULL,
                metadata longtext NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY ledger_uuid (ledger_uuid),
                UNIQUE KEY idempotency_key (idempotency_key),
                KEY user_id_created_at (user_id, created_at),
                KEY user_entry_type (user_id, entry_type),
                KEY source_lookup (source_type, source_uuid)
            ) {$charset_collate};",
        ];
    }
}

