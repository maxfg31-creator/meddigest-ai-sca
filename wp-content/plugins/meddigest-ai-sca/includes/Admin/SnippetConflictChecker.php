<?php
/**
 * Code Snippets conflict scanner.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

use MedDigest\AiSca\Database\Schema;
use MedDigest\AiSca\MemberPress\ProductMappingService;

if (!defined('ABSPATH')) {
    exit;
}

final class SnippetConflictChecker
{
    /**
     * Identifiers this plugin owns or plans to own.
     */
    public function identifiers()
    {
        $tables = Schema::tables();

        return [
            'meddigest_ai_credit_packs',
            'meddigest_ai_full_mock_strip',
            'meddigest_ai_case_cta',
            'meddigest-ai/v1',
            ProductMappingService::OPTION_NAME,
            'meddigest_ai_sca',
            'mdsca_',
            $tables['wallets'],
            $tables['ledger'],
            'mepr-event-transaction-completed',
            'mepr-txn-status-complete',
        ];
    }

    /**
     * Find possible conflicts in Code Snippets.
     */
    public function find_conflicts()
    {
        $inventory = new SnippetInventory();
        $snippets  = $inventory->get_snippets();
        $matches   = [];

        foreach ($snippets as $snippet) {
            $code = isset($snippet['code']) ? (string) $snippet['code'] : '';

            foreach ($this->identifiers() as $identifier) {
                if ('' !== $identifier && false !== stripos($code, $identifier)) {
                    $matches[] = [
                        'id'         => isset($snippet['id']) ? (int) $snippet['id'] : 0,
                        'name'       => isset($snippet['name']) ? (string) $snippet['name'] : '',
                        'active'     => !empty($snippet['active']),
                        'identifier' => $identifier,
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Render a settings-page conflict report.
     */
    public function render_report()
    {
        $inventory = new SnippetInventory();
        $snippets  = $inventory->get_snippets();

        echo '<h2>' . esc_html__('Code Snippets Compatibility', 'meddigest-ai-sca') . '</h2>';
        echo '<p>' . esc_html__('Existing custom site behavior may live in database-level Code Snippets. Keep snippets active during staging checks and review possible identifier conflicts before rollout.', 'meddigest-ai-sca') . '</p>';

        if (empty($snippets)) {
            echo '<p><strong>' . esc_html__('No Code Snippets table entries were detected from this plugin context.', 'meddigest-ai-sca') . '</strong></p>';
            return;
        }

        printf(
            '<p>%s</p>',
            esc_html(sprintf(__('Detected %d snippet records.', 'meddigest-ai-sca'), count($snippets)))
        );

        $matches = $this->find_conflicts();

        if (empty($matches)) {
            echo '<p><strong>' . esc_html__('No planned MedDigest AI SCA identifiers were found inside snippet code.', 'meddigest-ai-sca') . '</strong></p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Snippet', 'meddigest-ai-sca') . '</th>';
        echo '<th>' . esc_html__('Active', 'meddigest-ai-sca') . '</th>';
        echo '<th>' . esc_html__('Matched identifier', 'meddigest-ai-sca') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($matches as $match) {
            printf(
                '<tr><td>%1$s (#%2$d)</td><td>%3$s</td><td><code>%4$s</code></td></tr>',
                esc_html($match['name']),
                absint($match['id']),
                $match['active'] ? esc_html__('Yes', 'meddigest-ai-sca') : esc_html__('No', 'meddigest-ai-sca'),
                esc_html($match['identifier'])
            );
        }

        echo '</tbody></table>';
    }
}

