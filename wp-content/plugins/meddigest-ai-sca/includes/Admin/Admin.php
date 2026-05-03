<?php
/**
 * Admin coordinator.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class Admin
{
    /**
     * Register admin services.
     */
    public function register()
    {
        (new SettingsPage())->register();
        (new CaseColumns())->register();
        (new CaseFilters())->register();
        (new MigrationHelper())->register();
    }
}
