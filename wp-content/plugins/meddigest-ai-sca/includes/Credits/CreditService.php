<?php
/**
 * Credit wallet service.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Credits;

use MedDigest\AiSca\Support\Logger;
use MedDigest\AiSca\Support\Uuid;

if (!defined('ABSPATH')) {
    exit;
}

final class CreditService
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
     * Get current balance.
     *
     * @param int $user_id User ID.
     */
    public function get_balance($user_id)
    {
        if ($user_id <= 0) {
            return [
                'available' => 0,
                'locked'    => 0,
                'total'     => 0,
            ];
        }

        return $this->wallets->get_balance($user_id);
    }

    /**
     * Issue credits idempotently after completed payment.
     *
     * @param int    $user_id         User ID.
     * @param int    $credits         Credits to issue.
     * @param string $source_type     Source type.
     * @param string $source_uuid     Source UUID or stable source ID.
     * @param string $idempotency_key Idempotency key.
     * @param array  $metadata        Metadata.
     */
    public function issue_credits($user_id, $credits, $source_type, $source_uuid, $idempotency_key, array $metadata = [])
    {
        global $wpdb;

        $user_id = (int) $user_id;
        $credits = (int) $credits;

        if ($user_id <= 0 || $credits <= 0 || '' === $idempotency_key) {
            return [
                'created' => false,
                'reason'  => 'invalid_issue_request',
            ];
        }

        $existing = $this->ledger->find_by_idempotency_key($idempotency_key);
        if ($existing) {
            return [
                'created'     => false,
                'reason'      => 'already_recorded',
                'ledger_uuid' => $existing['ledger_uuid'],
                'balance'     => $this->get_balance($user_id),
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
                    'balance'     => $this->get_balance($user_id),
                ];
            }

            $balance = $this->wallets->increment_available($user_id, $credits);
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
                    'delta'           => $credits,
                    'balance_after'   => $balance['available'],
                    'entry_type'      => 'credit_issue',
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

