<?php
/**
 * MemberPress transaction hooks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\MemberPress;

use MedDigest\AiSca\Credits\CreditService;
use MedDigest\AiSca\Credits\Idempotency;
use MedDigest\AiSca\Support\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class TransactionHandler
{
    /**
     * Register MemberPress hooks.
     */
    public function register()
    {
        add_action('mepr-event-transaction-completed', [$this, 'handle_completed_event'], 10, 1);
        add_action('mepr-txn-status-complete', [$this, 'handle_completed_transaction'], 10, 1);
    }

    /**
     * Handle MemberPress event wrapper.
     *
     * @param object $event MemberPress event.
     */
    public function handle_completed_event($event)
    {
        if (!is_object($event) || !method_exists($event, 'get_data')) {
            return;
        }

        $transaction = $event->get_data();
        $this->handle_completed_transaction($transaction);
    }

    /**
     * Handle completed MemberPress transaction.
     *
     * @param object $transaction MemberPress transaction-like object.
     */
    public function handle_completed_transaction($transaction)
    {
        if (!is_object($transaction)) {
            return;
        }

        $product_id = $this->extract_int($transaction, ['product_id', 'product']);
        $user_id    = $this->extract_int($transaction, ['user_id', 'user']);
        $txn_id     = $this->extract_transaction_id($transaction);

        if ($product_id <= 0 || $user_id <= 0 || '' === $txn_id) {
            return;
        }

        if (!$this->is_complete_status($transaction)) {
            return;
        }

        $mapping = new ProductMappingService();
        $pack    = $mapping->get_credit_pack_by_product_id($product_id);

        if (!$pack) {
            return;
        }

        $idempotency_key = Idempotency::key('memberpress', 'transaction', $txn_id, 'credit_issue');

        $result = (new CreditService())->issue_credits(
            $user_id,
            (int) $pack['credits'],
            'memberpress_transaction',
            (string) $txn_id,
            $idempotency_key,
            [
                'product_id' => $product_id,
                'pack_key'   => $pack['key'],
                'pack_label' => $pack['label'],
            ]
        );

        if (!empty($result['created'])) {
            do_action('meddigest_ai_sca_credits_issued', $user_id, $pack, $transaction, $result);
        } elseif (!empty($result['reason']) && 'already_recorded' !== $result['reason']) {
            Logger::error('Credit issue failed for MemberPress transaction ' . $txn_id . ': ' . $result['reason']);
        }
    }

    /**
     * Extract integer property/method value.
     *
     * @param object $object Object.
     * @param array  $names  Candidate names.
     */
    private function extract_int($object, array $names)
    {
        foreach ($names as $name) {
            if (isset($object->{$name}) && is_scalar($object->{$name})) {
                return absint($object->{$name});
            }

            $method = 'get_' . $name;
            if (method_exists($object, $method)) {
                $value = $object->{$method}();

                if (is_scalar($value)) {
                    return absint($value);
                }

                if (is_object($value) && isset($value->ID)) {
                    return absint($value->ID);
                }

                if (is_object($value) && isset($value->id)) {
                    return absint($value->id);
                }
            }
        }

        return 0;
    }

    /**
     * Extract a stable transaction ID.
     *
     * @param object $transaction Transaction.
     */
    private function extract_transaction_id($transaction)
    {
        foreach (['id', 'uuid', 'trans_num', 'transaction_id'] as $name) {
            if (isset($transaction->{$name}) && is_scalar($transaction->{$name}) && '' !== (string) $transaction->{$name}) {
                return (string) $transaction->{$name};
            }

            $method = 'get_' . $name;
            if (method_exists($transaction, $method)) {
                $value = $transaction->{$method}();
                if (is_scalar($value) && '' !== (string) $value) {
                    return (string) $value;
                }
            }
        }

        return '';
    }

    /**
     * Ensure transaction is complete when status is present.
     *
     * @param object $transaction Transaction.
     */
    private function is_complete_status($transaction)
    {
        if (!isset($transaction->status) && !method_exists($transaction, 'get_status')) {
            return true;
        }

        $status = isset($transaction->status) ? $transaction->status : $transaction->get_status();
        $status = strtolower((string) $status);

        return in_array($status, ['complete', 'completed'], true);
    }
}
