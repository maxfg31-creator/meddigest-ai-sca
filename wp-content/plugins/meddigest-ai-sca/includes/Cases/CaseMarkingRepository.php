<?php
/**
 * Case marking repository.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Cases;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Support\Clock;

if (!defined('ABSPATH')) {
    exit;
}

final class CaseMarkingRepository
{
    /**
     * Replace normalized marking items from JSON data.
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
        $wpdb->delete($tables['case_marking'], ['case_config_id' => $case_config_id]);

        $items = json_decode((string) $json, true);

        if (!is_array($items)) {
            return;
        }

        $now        = Clock::mysql_utc();
        $sort_order = 0;

        foreach ($items as $domain => $domain_items) {
            if (!is_array($domain_items)) {
                continue;
            }

            foreach ($domain_items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $wpdb->insert(
                    $tables['case_marking'],
                    [
                        'case_config_id'  => $case_config_id,
                        'domain'          => sanitize_key($domain),
                        'item_text'       => sanitize_textarea_field($item['item_text'] ?? $item['item'] ?? ''),
                        'weight'          => (float) ($item['weight'] ?? 1),
                        'is_critical'     => !empty($item['is_critical']) ? 1 : 0,
                        'fail_if_missing' => !empty($item['fail_if_missing']) ? 1 : 0,
                        'sort_order'      => $sort_order,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]
                );

                $sort_order++;
            }
        }
    }
}
