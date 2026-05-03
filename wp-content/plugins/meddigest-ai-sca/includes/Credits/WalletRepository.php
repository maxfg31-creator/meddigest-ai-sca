<?php
/**
 * Wallet persistence.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Credits;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class WalletRepository
{
    /**
     * Ensure a wallet row exists for a user.
     *
     * @param int $user_id User ID.
     */
    public function ensure_wallet($user_id)
    {
        global $wpdb;

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$tables['wallets']} (user_id, balance_available, balance_locked, created_at, updated_at)
                VALUES (%d, 0, 0, %s, %s)
                ON DUPLICATE KEY UPDATE updated_at = updated_at",
                $user_id,
                $now,
                $now
            )
        );
    }

    /**
     * Get a user's wallet balance.
     *
     * @param int $user_id User ID.
     */
    public function get_balance($user_id)
    {
        global $wpdb;

        $this->ensure_wallet($user_id);

        $tables = Schema::tables();
        $row    = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT balance_available, balance_locked FROM {$tables['wallets']} WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        if (!$row) {
            return [
                'available' => 0,
                'locked'    => 0,
                'total'     => 0,
            ];
        }

        $available = (int) $row['balance_available'];
        $locked    = (int) $row['balance_locked'];

        return [
            'available' => $available,
            'locked'    => $locked,
            'total'     => $available + $locked,
        ];
    }

    /**
     * Increment available balance atomically.
     *
     * @param int $user_id User ID.
     * @param int $delta   Credit delta.
     */
    public function increment_available($user_id, $delta)
    {
        global $wpdb;

        $this->ensure_wallet($user_id);

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tables['wallets']}
                SET balance_available = balance_available + %d, updated_at = %s
                WHERE user_id = %d AND (balance_available + %d) >= 0",
                $delta,
                $now,
                $user_id,
                $delta
            )
        );

        if (false === $result || 0 === $result) {
            return false;
        }

        return $this->get_balance($user_id);
    }

    /**
     * Move credits from available to locked.
     *
     * @param int $user_id User ID.
     * @param int $credits Credits.
     */
    public function hold_available($user_id, $credits)
    {
        global $wpdb;

        $credits = absint($credits);

        if ($credits <= 0) {
            return false;
        }

        $this->ensure_wallet($user_id);

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tables['wallets']}
                SET balance_available = balance_available - %d,
                    balance_locked = balance_locked + %d,
                    updated_at = %s
                WHERE user_id = %d AND balance_available >= %d",
                $credits,
                $credits,
                $now,
                $user_id,
                $credits
            )
        );

        if (false === $result || 0 === $result) {
            return false;
        }

        return $this->get_balance($user_id);
    }

    /**
     * Commit previously held credits.
     *
     * @param int $user_id User ID.
     * @param int $credits Credits.
     */
    public function commit_locked($user_id, $credits)
    {
        global $wpdb;

        $credits = absint($credits);

        if ($credits <= 0) {
            return false;
        }

        $this->ensure_wallet($user_id);

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tables['wallets']}
                SET balance_locked = balance_locked - %d,
                    updated_at = %s
                WHERE user_id = %d AND balance_locked >= %d",
                $credits,
                $now,
                $user_id,
                $credits
            )
        );

        if (false === $result || 0 === $result) {
            return false;
        }

        return $this->get_balance($user_id);
    }

    /**
     * Release held credits back to available balance.
     *
     * @param int $user_id User ID.
     * @param int $credits Credits.
     */
    public function release_locked($user_id, $credits)
    {
        global $wpdb;

        $credits = absint($credits);

        if ($credits <= 0) {
            return false;
        }

        $this->ensure_wallet($user_id);

        $tables = Schema::tables();
        $now    = Clock::mysql_utc();
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tables['wallets']}
                SET balance_available = balance_available + %d,
                    balance_locked = balance_locked - %d,
                    updated_at = %s
                WHERE user_id = %d AND balance_locked >= %d",
                $credits,
                $credits,
                $now,
                $user_id,
                $credits
            )
        );

        if (false === $result || 0 === $result) {
            return false;
        }

        return $this->get_balance($user_id);
    }
}
