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
    public const DB_VERSION = '2026-05-03.3';

    /**
     * Get plugin table names.
     */
    public static function tables()
    {
        global $wpdb;

        return [
            'wallets'       => $wpdb->prefix . 'meddigest_ai_wallets',
            'ledger'        => $wpdb->prefix . 'meddigest_ai_ledger',
            'case_config'   => $wpdb->prefix . 'meddigest_ai_case_config',
            'case_facts'    => $wpdb->prefix . 'meddigest_ai_case_facts',
            'case_marking'  => $wpdb->prefix . 'meddigest_ai_case_marking_items',
            'attempts'      => $wpdb->prefix . 'meddigest_ai_attempts',
            'feedback'      => $wpdb->prefix . 'meddigest_ai_feedback',
            'mock_runs'     => $wpdb->prefix . 'meddigest_ai_mock_runs',
            'mock_stations' => $wpdb->prefix . 'meddigest_ai_mock_stations',
            'consents'      => $wpdb->prefix . 'meddigest_ai_consents',
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
            "CREATE TABLE {$tables['case_config']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                case_post_id bigint(20) unsigned NOT NULL,
                enabled tinyint(1) NOT NULL DEFAULT 0,
                mode varchar(30) NOT NULL DEFAULT 'video',
                first_speaker varchar(30) NOT NULL DEFAULT 'patient',
                voice_id varchar(80) NOT NULL DEFAULT '',
                ai_version varchar(80) NOT NULL DEFAULT '',
                reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
                reviewed_at datetime NULL,
                doctor_brief_override longtext NULL,
                pre_start_instructions_override longtext NULL,
                patient_profile_json longtext NULL,
                internal_notes_json longtext NULL,
                mock_pool_enabled tinyint(1) NOT NULL DEFAULT 0,
                mock_ready_status varchar(30) NOT NULL DEFAULT 'draft',
                mock_primary_group_term_id bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY case_post_id (case_post_id),
                KEY enabled (enabled),
                KEY mock_pool_ready (mock_pool_enabled, mock_ready_status),
                KEY mock_primary_group_term_id (mock_primary_group_term_id)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['case_facts']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                case_config_id bigint(20) unsigned NOT NULL,
                label varchar(191) NOT NULL DEFAULT '',
                fact_text longtext NULL,
                reveal_condition longtext NULL,
                reveal_examples longtext NULL,
                domain varchar(30) NOT NULL DEFAULT '',
                is_critical tinyint(1) NOT NULL DEFAULT 0,
                notes longtext NULL,
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY case_config_id (case_config_id),
                KEY domain (domain),
                KEY is_critical (is_critical)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['case_marking']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                case_config_id bigint(20) unsigned NOT NULL,
                domain varchar(30) NOT NULL DEFAULT '',
                item_text longtext NULL,
                weight decimal(10,2) NOT NULL DEFAULT 1.00,
                is_critical tinyint(1) NOT NULL DEFAULT 0,
                fail_if_missing tinyint(1) NOT NULL DEFAULT 0,
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY case_config_id (case_config_id),
                KEY domain (domain),
                KEY critical_fail (is_critical, fail_if_missing)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['attempts']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                attempt_uuid char(36) NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                case_post_id bigint(20) unsigned NOT NULL,
                status varchar(40) NOT NULL DEFAULT 'setup',
                membership_snapshot_json longtext NULL,
                hold_ledger_uuid char(36) NOT NULL DEFAULT '',
                commit_ledger_uuid char(36) NOT NULL DEFAULT '',
                transcript_json longtext NULL,
                snapshot_json longtext NULL,
                started_at datetime NULL,
                live_started_at datetime NULL,
                hard_stop_at datetime NULL,
                ended_at datetime NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY attempt_uuid (attempt_uuid),
                KEY user_status_created (user_id, status, created_at),
                KEY user_case_status (user_id, case_post_id, status),
                KEY case_post_id (case_post_id),
                KEY hard_stop_at (hard_stop_at)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['feedback']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                attempt_uuid char(36) NOT NULL,
                processing_status varchar(40) NOT NULL DEFAULT 'pending',
                retry_count int(11) NOT NULL DEFAULT 0,
                practice_verdict longtext NULL,
                dgd_grade varchar(10) NOT NULL DEFAULT '',
                cmc_grade varchar(10) NOT NULL DEFAULT '',
                rto_grade varchar(10) NOT NULL DEFAULT '',
                global_json longtext NULL,
                full_feedback_json longtext NULL,
                rendered_snapshot_json longtext NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY attempt_uuid (attempt_uuid),
                KEY processing_status (processing_status),
                KEY updated_at (updated_at)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['mock_runs']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                mock_uuid char(36) NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                status varchar(40) NOT NULL DEFAULT 'running',
                membership_snapshot_json longtext NULL,
                hold_ledger_uuid char(36) NOT NULL DEFAULT '',
                commit_ledger_uuid char(36) NOT NULL DEFAULT '',
                schedule_json longtext NULL,
                station_snapshot_json longtext NULL,
                results_json longtext NULL,
                started_at datetime NULL,
                current_phase varchar(40) NOT NULL DEFAULT 'reading',
                current_station int(11) NOT NULL DEFAULT 1,
                phase_ends_at datetime NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY mock_uuid (mock_uuid),
                KEY user_status_created (user_id, status, created_at),
                KEY status_updated (status, updated_at),
                KEY current_phase (current_phase),
                KEY phase_ends_at (phase_ends_at)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['mock_stations']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                mock_uuid char(36) NOT NULL,
                station_number int(11) NOT NULL,
                attempt_uuid char(36) NOT NULL DEFAULT '',
                case_post_id bigint(20) unsigned NOT NULL,
                mock_primary_group_term_id bigint(20) unsigned NOT NULL DEFAULT 0,
                reading_start_at datetime NULL,
                live_start_at datetime NULL,
                hard_stop_at datetime NULL,
                ended_at datetime NULL,
                grade_status varchar(40) NOT NULL DEFAULT 'pending',
                transcript_json longtext NULL,
                feedback_json longtext NULL,
                station_snapshot_json longtext NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY mock_station (mock_uuid, station_number),
                KEY mock_uuid (mock_uuid),
                KEY station_number (station_number),
                KEY attempt_uuid (attempt_uuid),
                KEY case_post_id (case_post_id),
                KEY mock_primary_group_term_id (mock_primary_group_term_id),
                KEY grade_status (grade_status)
            ) {$charset_collate};",
            "CREATE TABLE {$tables['consents']} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                object_type varchar(40) NOT NULL,
                object_uuid char(36) NOT NULL,
                consent_version varchar(40) NOT NULL DEFAULT 'v1',
                ip_address varchar(100) NOT NULL DEFAULT '',
                user_agent varchar(255) NOT NULL DEFAULT '',
                agreed_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY object_consent (object_type, object_uuid, consent_version),
                KEY user_object (user_id, object_type),
                KEY agreed_at (agreed_at)
            ) {$charset_collate};",
        ];
    }
}
