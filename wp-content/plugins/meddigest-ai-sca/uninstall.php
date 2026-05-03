<?php
/**
 * Conservative uninstall handler.
 *
 * Financial wallet and ledger tables are intentionally preserved by default.
 *
 * @package MedDigest\AiSca
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('meddigest_ai_sca_admin_notices');

