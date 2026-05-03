<?php
/**
 * Case fact repository.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Cases;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseFactRepository
{
    /**
     * Replace normalized facts from JSON data.
     *
     * @param int    $case_config_id Case config ID.
     * @param string $json           JSON.
     */
    public function replace_from_json($case_config_id, $json)
    {
        global $wpdb;

        $case_config_id = absint($case_config_id);

        if ($case_config_id <= 0) {
            return;
        }

        $tables = Schema::tables();
        $wpdb->delete($tables['case_facts'], ['case_config_id' => $case_config_id]);

        $items = json_decode((string) $json, true);

        if (!is_array($items)) {
            return;
        }

        $now = Clock::mysql_utc();

        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $wpdb->insert(
                $tables['case_facts'],
                [
                    'case_config_id'  => $case_config_id,
                    'label'           => sanitize_text_field($item['label'] ?? ''),
                    'fact_text'       => sanitize_textarea_field($item['fact_text'] ?? ''),
                    'reveal_condition' => sanitize_textarea_field($item['reveal_condition'] ?? ''),
                    'reveal_examples' => sanitize_textarea_field($item['reveal_examples'] ?? ''),
                    'domain'          => sanitize_key($item['domain'] ?? ''),
                    'is_critical'     => !empty($item['is_critical']) ? 1 : 0,
                    'notes'           => sanitize_textarea_field($item['notes'] ?? ''),
                    'sort_order'      => $index,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]
            );
        }
    }
}
