<?php
/**
 * Deactivation tasks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca;

if (!defined('ABSPATH')) {
    exit;
}

final class Deactivation
{
    /**
     * Run deactivation tasks.
     */
    public static function deactivate()
    {
        // Intentionally no destructive cleanup. Wallet and ledger data must persist.
    }
}

