<?php
/**
 * Immutable credit ledger persistence.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Credits;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class LedgerRepository
{
    /**
     * Find a ledger row by idempotency key.
     *
     * @param string $idempotency_key Idempotency key.
     */
    public function find_by_idempotency_key($idempotency_key)
    {
        global $wpdb;

        $tables = Schema::tables();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['ledger']} WHERE idempotency_key = %s LIMIT 1",
                $idempotency_key
            ),
            ARRAY_A
        );
    }

    /**
     * Insert a ledger entry.
     *
     * @param array $entry Ledger entry.
     */
    public function insert(array $entry)
    {
        global $wpdb;

        $tables   = Schema::tables();
        $metadata = isset($entry['metadata']) ? $entry['metadata'] : [];

        $result = $wpdb->insert(
            $tables['ledger'],
            [
                'ledger_uuid'     => $entry['ledger_uuid'],
                'user_id'         => (int) $entry['user_id'],
                'delta'           => (int) $entry['delta'],
                'balance_after'   => (int) $entry['balance_after'],
                'entry_type'      => $entry['entry_type'],
                'source_type'     => $entry['source_type'],
                'source_uuid'     => $entry['source_uuid'],
                'idempotency_key' => $entry['idempotency_key'],
                'metadata'        => wp_json_encode($metadata),
                'created_at'      => Clock::mysql_utc(),
            ],
            [
                '%s',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        return false !== $result;
    }
}

