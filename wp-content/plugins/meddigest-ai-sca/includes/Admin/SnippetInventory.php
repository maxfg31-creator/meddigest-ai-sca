<?php
/**
 * Code Snippets database inventory.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class SnippetInventory
{
    /**
     * Get snippets from the Code Snippets table when present.
     */
    public function get_snippets()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'snippets';

        if (!$this->table_exists($table)) {
            return [];
        }

        $columns = $wpdb->get_col("DESC {$table}", 0);
        $order   = '';

        if (in_array('active', $columns, true) && in_array('name', $columns, true)) {
            $order = ' ORDER BY active DESC, name ASC';
        } elseif (in_array('id', $columns, true)) {
            $order = ' ORDER BY id ASC';
        }

        return $wpdb->get_results("SELECT * FROM {$table}{$order}", ARRAY_A);
    }

    /**
     * Whether a DB table exists.
     *
     * @param string $table Table name.
     */
    public function table_exists($table)
    {
        global $wpdb;

        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        return $found === $table;
    }
}
