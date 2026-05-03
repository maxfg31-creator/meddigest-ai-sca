<?php
/**
 * WordPress privacy exporter and eraser hooks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Privacy;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Practice\HistoryService;

if (!defined('ABSPATH')) {
    exit;
}

final class PrivacyTools
{
    /**
     * Register privacy integrations.
     */
    public function register()
    {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_exporter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_eraser']);
    }

    /**
     * Register exporter.
     *
     * @param array $exporters Exporters.
     */
    public function register_exporter(array $exporters)
    {
        $exporters['meddigest-ai-sca'] = [
            'exporter_friendly_name' => __('MedDigest AI SCA', 'meddigest-ai-sca'),
            'callback'               => [$this, 'export_data'],
        ];

        return $exporters;
    }

    /**
     * Register eraser.
     *
     * @param array $erasers Erasers.
     */
    public function register_eraser(array $erasers)
    {
        $erasers['meddigest-ai-sca'] = [
            'eraser_friendly_name' => __('MedDigest AI SCA', 'meddigest-ai-sca'),
            'callback'             => [$this, 'erase_data'],
        ];

        return $erasers;
    }

    /**
     * Export user AI practice summary.
     *
     * @param string $email_address Email.
     * @param int    $page          Page.
     */
    public function export_data($email_address, $page = 1)
    {
        $user = get_user_by('email', $email_address);

        if (!$user) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        $history = (new HistoryService())->get_history($user->ID, absint($page), 20);
        $data    = [];

        foreach ($history['items'] as $item) {
            $data[] = [
                'group_id'    => 'meddigest-ai-sca-history',
                'group_label' => __('MedDigest AI SCA History', 'meddigest-ai-sca'),
                'item_id'     => $item['type'] . '-' . $item['uuid'],
                'data'        => [
                    [
                        'name'  => __('Type', 'meddigest-ai-sca'),
                        'value' => $item['type'],
                    ],
                    [
                        'name'  => __('Title', 'meddigest-ai-sca'),
                        'value' => $item['title'],
                    ],
                    [
                        'name'  => __('Status', 'meddigest-ai-sca'),
                        'value' => $item['status'],
                    ],
                    [
                        'name'  => __('Created', 'meddigest-ai-sca'),
                        'value' => $item['created_at'],
                    ],
                ],
            ];
        }

        return [
            'data' => $data,
            'done' => absint($history['page']) >= absint($history['total_pages']),
        ];
    }

    /**
     * Erase user AI practice data.
     *
     * @param string $email_address Email.
     * @param int    $page          Page.
     */
    public function erase_data($email_address, $page = 1)
    {
        global $wpdb;

        $user = get_user_by('email', $email_address);

        if (!$user) {
            return [
                'items_removed'  => false,
                'items_retained' => false,
                'messages'       => [],
                'done'           => true,
            ];
        }

        $tables  = Schema::tables();
        $user_id = absint($user->ID);

        $mock_uuids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT mock_uuid FROM {$tables['mock_runs']} WHERE user_id = %d",
                $user_id
            )
        );

        foreach ($mock_uuids ?: [] as $mock_uuid) {
            $wpdb->delete($tables['mock_stations'], ['mock_uuid' => $mock_uuid]);
        }

        $wpdb->delete($tables['mock_runs'], ['user_id' => $user_id]);
        $wpdb->delete($tables['consents'], ['user_id' => $user_id]);

        $attempts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT attempt_uuid FROM {$tables['attempts']} WHERE user_id = %d",
                $user_id
            )
        );

        foreach ($attempts ?: [] as $attempt_uuid) {
            $wpdb->delete($tables['feedback'], ['attempt_uuid' => $attempt_uuid]);
        }

        $wpdb->delete($tables['attempts'], ['user_id' => $user_id]);
        $wpdb->delete($tables['wallets'], ['user_id' => $user_id]);
        $wpdb->update($tables['ledger'], ['user_id' => 0, 'metadata' => null], ['user_id' => $user_id]);

        return [
            'items_removed'  => true,
            'items_retained' => false,
            'messages'       => [__('MedDigest AI SCA practice data was erased or anonymized.', 'meddigest-ai-sca')],
            'done'           => true,
        ];
    }
}
