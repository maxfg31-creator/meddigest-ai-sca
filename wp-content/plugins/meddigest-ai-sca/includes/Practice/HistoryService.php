<?php
/**
 * AI Practice history queries.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Practice;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\Mock\MockLaunchService;

if (!defined('ABSPATH')) {
    exit;
}

final class HistoryService
{
    /**
     * Paginated AI history for one user.
     *
     * @param int $user_id User ID.
     * @param int $page    Page.
     * @param int $per_page Per page.
     */
    public function get_history($user_id, $page = 1, $per_page = 20)
    {
        $user_id  = absint($user_id);
        $page     = max(1, absint($page));
        $per_page = min(50, max(1, absint($per_page)));

        if (!$user_id) {
            return [
                'items'       => [],
                'total'       => 0,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => 0,
            ];
        }

        $total       = $this->count_history($user_id);
        $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;
        $offset      = ($page - 1) * $per_page;
        $items       = $this->combined_history($user_id, $per_page, $offset);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => $total_pages,
        ];
    }

    /**
     * Whether the user has AI history.
     *
     * @param int $user_id User ID.
     */
    public function history_exists($user_id)
    {
        global $wpdb;

        $user_id = absint($user_id);

        if (!$user_id) {
            return false;
        }

        $tables = Schema::tables();

        $station_exists = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$tables['attempts']} WHERE user_id = %d LIMIT 1",
                $user_id
            )
        );

        if ($station_exists > 0) {
            return true;
        }

        $mock_exists = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$tables['mock_runs']} WHERE user_id = %d LIMIT 1",
                $user_id
            )
        );

        return $mock_exists > 0;
    }

    /**
     * Count total history rows.
     *
     * @param int $user_id User ID.
     */
    private function count_history($user_id)
    {
        global $wpdb;

        $tables = Schema::tables();

        $station_count = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(1) FROM {$tables['attempts']} WHERE user_id = %d", $user_id)
        );
        $mock_count    = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(1) FROM {$tables['mock_runs']} WHERE user_id = %d", $user_id)
        );

        return $station_count + $mock_count;
    }

    /**
     * Combined, paginated station/mock history.
     *
     * @param int $user_id  User ID.
     * @param int $per_page Per page.
     * @param int $offset   Offset.
     */
    private function combined_history($user_id, $per_page, $offset)
    {
        global $wpdb;

        $tables = Schema::tables();
        $sql    = "
            (SELECT
                'station' AS item_type,
                a.attempt_uuid AS item_uuid,
                a.case_post_id AS case_post_id,
                a.status AS item_status,
                '' AS item_phase,
                a.created_at AS created_at,
                a.updated_at AS updated_at,
                COALESCE(f.processing_status, '') AS processing_status,
                COALESCE(f.practice_verdict, '') AS summary,
                '' AS results_json
            FROM {$tables['attempts']} a
            LEFT JOIN {$tables['feedback']} f ON f.attempt_uuid = a.attempt_uuid
            WHERE a.user_id = %d)
            UNION ALL
            (SELECT
                'mock' AS item_type,
                m.mock_uuid AS item_uuid,
                0 AS case_post_id,
                m.status AS item_status,
                m.current_phase AS item_phase,
                m.created_at AS created_at,
                m.updated_at AS updated_at,
                '' AS processing_status,
                '' AS summary,
                COALESCE(m.results_json, '') AS results_json
            FROM {$tables['mock_runs']} m
            WHERE m.user_id = %d)
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d";

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                $user_id,
                $user_id,
                absint($per_page),
                absint($offset)
            ),
            ARRAY_A
        );

        $items = [];

        foreach ($rows ?: [] as $row) {
            if ('station' === $row['item_type']) {
                $items[] = $this->station_item($row);
                continue;
            }

            $items[] = $this->mock_item($row);
        }

        return $items;
    }

    /**
     * Build station history item.
     *
     * @param array $row Row.
     */
    private function station_item(array $row)
    {
        $status = $row['item_status'];
        $url    = home_url('/sca-ai/station/' . $row['item_uuid'] . '/feedback/');

        if (in_array($status, [StationAttemptService::STATUS_SETUP, StationAttemptService::STATUS_LIVE], true)) {
            $url = home_url('/sca-ai/station/' . $row['item_uuid'] . '/live/');
        }

        return [
            'type'              => 'station',
            'uuid'              => $row['item_uuid'],
            'title'             => get_the_title(absint($row['case_post_id'])) ?: __('AI Consultation', 'meddigest-ai-sca'),
            'status'            => $status,
            'processing_status' => $row['processing_status'] ?: '',
            'summary'           => $row['summary'] ?: '',
            'created_at'        => $row['created_at'],
            'updated_at'        => $row['updated_at'],
            'url'               => $url,
        ];
    }

    /**
     * Build mock history item.
     *
     * @param array $row Row.
     */
    private function mock_item(array $row)
    {
        $is_active = in_array($row['item_status'], [MockLaunchService::STATUS_RUNNING, MockLaunchService::STATUS_PROCESSING], true);
        $url       = $is_active && MockLaunchService::STATUS_RUNNING === $row['item_status']
            ? home_url('/sca-ai/mock/' . $row['item_uuid'] . '/run/')
            : home_url('/sca-ai/mock/' . $row['item_uuid'] . '/results/');

        $results = !empty($row['results_json']) ? json_decode($row['results_json'], true) : [];
        $results = is_array($results) ? $results : [];

        return [
            'type'       => 'mock',
            'uuid'       => $row['item_uuid'],
            'title'      => __('Full Mock SCA', 'meddigest-ai-sca'),
            'status'     => $row['item_status'],
            'phase'      => $row['item_phase'],
            'summary'    => !empty($results['status']) ? sanitize_text_field($results['status']) : '',
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'url'        => $url,
        ];
    }
}
