<?php
/**
 * Credit hold/commit/release flows.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Credits;

use MedDigest\AiSca\Support\Logger;
use MedDigest\AiSca\Support\Uuid;

if (!defined('ABSPATH')) {
    exit;
}

final class CreditHoldService
{
    /**
     * @var WalletRepository
     */
    private $wallets;

    /**
     * @var LedgerRepository
     */
    private $ledger;

    public function __construct()
    {
        $this->wallets = new WalletRepository();
        $this->ledger  = new LedgerRepository();
    }

    /**
     * Create an idempotent hold.
     *
     * @param int    $user_id         User ID.
     * @param int    $credits         Credits.
     * @param string $source_type     Source type.
     * @param string $source_uuid     Source UUID.
     * @param string $idempotency_key Idempotency key.
     * @param array  $metadata        Metadata.
     */
    public function hold_credits($user_id, $credits, $source_type, $source_uuid, $idempotency_key, array $metadata = [])
    {
        return $this->mutate_locked_balance('credit_hold', $user_id, $credits, $source_type, $source_uuid, $idempotency_key, $metadata);
    }

    /**
     * Commit a prior hold.
     *
     * @param int    $user_id         User ID.
     * @param int    $credits         Credits.
     * @param string $source_type     Source type.
     * @param string $source_uuid     Source UUID.
     * @param string $idempotency_key Idempotency key.
     * @param array  $metadata        Metadata.
     */
    public function commit_hold($user_id, $credits, $source_type, $source_uuid, $idempotency_key, array $metadata = [])
    {
        return $this->mutate_locked_balance('credit_commit', $user_id, $credits, $source_type, $source_uuid, $idempotency_key, $metadata);
    }

    /**
     * Release a prior hold.
     *
     * @param int    $user_id         User ID.
     * @param int    $credits         Credits.
     * @param string $source_type     Source type.
     * @param string $source_uuid     Source UUID.
     * @param string $idempotency_key Idempotency key.
     * @param array  $metadata        Metadata.
     */
    public function release_hold($user_id, $credits, $source_type, $source_uuid, $idempotency_key, array $metadata = [])
    {
        return $this->mutate_locked_balance('credit_release', $user_id, $credits, $source_type, $source_uuid, $idempotency_key, $metadata);
    }

    /**
     * Mutate wallet and ledger for hold/commit/release.
     *
     * @param string $entry_type      Entry type.
     * @param int    $user_id         User ID.
     * @param int    $credits         Credits.
     * @param string $source_type     Source type.
     * @param string $source_uuid     Source UUID.
     * @param string $idempotency_key Idempotency key.
     * @param array  $metadata        Metadata.
     */
    private function mutate_locked_balance($entry_type, $user_id, $credits, $source_type, $source_uuid, $idempotency_key, array $metadata)
    {
        global $wpdb;

        $user_id = absint($user_id);
        $credits = absint($credits);

        if ($user_id <= 0 || $credits <= 0 || '' === $idempotency_key) {
            return [
                'created' => false,
                'reason'  => 'invalid_hold_request',
            ];
        }

        $existing = $this->ledger->find_by_idempotency_key($idempotency_key);
        if ($existing) {
            return [
                'created'     => false,
                'reason'      => 'already_recorded',
                'ledger_uuid' => $existing['ledger_uuid'],
                'balance'     => $this->wallets->get_balance($user_id),
            ];
        }

        try {
            $wpdb->query('START TRANSACTION');

            $existing = $this->ledger->find_by_idempotency_key($idempotency_key);
            if ($existing) {
                $wpdb->query('COMMIT');

                return [
                    'created'     => false,
                    'reason'      => 'already_recorded',
                    'ledger_uuid' => $existing['ledger_uuid'],
                    'balance'     => $this->wallets->get_balance($user_id),
                ];
            }

            if ('credit_hold' === $entry_type) {
                $balance = $this->wallets->hold_available($user_id, $credits);
                $delta   = 0;
            } elseif ('credit_commit' === $entry_type) {
                $balance = $this->wallets->commit_locked($user_id, $credits);
                $delta   = -1 * $credits;
            } else {
                $balance = $this->wallets->release_locked($user_id, $credits);
                $delta   = 0;
            }

            if (false === $balance) {
                $wpdb->query('ROLLBACK');

                return [
                    'created' => false,
                    'reason'  => 'wallet_update_failed',
                ];
            }

            $ledger_uuid = Uuid::v4();
            $inserted    = $this->ledger->insert(
                [
                    'ledger_uuid'     => $ledger_uuid,
                    'user_id'         => $user_id,
                    'delta'           => $delta,
                    'balance_after'   => (int) $balance['available'],
                    'entry_type'      => $entry_type,
                    'source_type'     => $source_type,
                    'source_uuid'     => $source_uuid,
                    'idempotency_key' => $idempotency_key,
                    'metadata'        => $metadata,
                ]
            );

            if (!$inserted) {
                $wpdb->query('ROLLBACK');

                return [
                    'created' => false,
                    'reason'  => 'ledger_insert_failed',
                ];
            }

            $wpdb->query('COMMIT');

            return [
                'created'     => true,
                'ledger_uuid' => $ledger_uuid,
                'balance'     => $balance,
            ];
        } catch (\Throwable $throwable) {
            $wpdb->query('ROLLBACK');
            Logger::error($throwable->getMessage());

            return [
                'created' => false,
                'reason'  => 'exception',
            ];
        }
    }
}
