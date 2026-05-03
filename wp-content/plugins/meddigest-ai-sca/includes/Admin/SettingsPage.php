<?php
/**
 * Admin settings page.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

use MedDigest\AiSca\MemberPress\ProductMappingService;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsPage
{
    private const PAGE_SLUG = 'meddigest-ai-sca';
    private const GROUP     = 'meddigest_ai_sca_settings_group';

    /**
     * Register admin hooks.
     */
    public function register()
    {
        add_action('admin_menu', [$this, 'add_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page.
     */
    public function add_page()
    {
        add_options_page(
            __('MedDigest AI SCA', 'meddigest-ai-sca'),
            __('MedDigest AI SCA', 'meddigest-ai-sca'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render']
        );
    }

    /**
     * Register settings.
     */
    public function register_settings()
    {
        register_setting(
            self::GROUP,
            ProductMappingService::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [ProductMappingService::class, 'sanitize_settings'],
                'default'           => ProductMappingService::defaults(),
            ]
        );
    }

    /**
     * Render settings page.
     */
    public function render()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $mapping_service = new ProductMappingService();
        $settings        = $mapping_service->get_settings();
        $fields          = new ProductMappings();

        echo '<div class="wrap mdsca-admin">';
        echo '<h1>' . esc_html__('MedDigest AI SCA', 'meddigest-ai-sca') . '</h1>';
        echo '<p>' . esc_html__('Milestone 1 settings map MemberPress products to the fixed MedDigest AI SCA credit rules. Do not hard-code product IDs in templates or snippets.', 'meddigest-ai-sca') . '</p>';

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);

        echo '<h2>' . esc_html__('MemberPress Products', 'meddigest-ai-sca') . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('SCA Cases Premium', 'meddigest-ai-sca') . '</th><td>';
        $fields->render_select(
            ProductMappingService::OPTION_NAME . '[sca_cases_premium_product_id]',
            $settings['sca_cases_premium_product_id']
        );
        echo '<p class="description">' . esc_html__('Required for launching AI stations and seeing AI credit packs.', 'meddigest-ai-sca') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';

        echo '<h2>' . esc_html__('AI Consultation Credit Packs', 'meddigest-ai-sca') . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';

        foreach (ProductMappingService::CREDIT_PACKS as $pack_key => $pack) {
            $product_id = isset($settings['credit_pack_products'][$pack_key]['product_id'])
                ? absint($settings['credit_pack_products'][$pack_key]['product_id'])
                : 0;

            printf(
                '<tr><th scope="row">%1$s</th><td>',
                esc_html(sprintf('%s - %s credits - %s', $pack['label'], $pack['credits'], $pack['price_label']))
            );

            $fields->render_select(
                ProductMappingService::OPTION_NAME . '[credit_pack_products][' . $pack_key . '][product_id]',
                $product_id
            );

            printf('<p class="description">%s</p>', esc_html($pack['note']));
            echo '</td></tr>';
        }

        echo '</tbody></table>';

        submit_button(__('Save MedDigest AI SCA Settings', 'meddigest-ai-sca'));
        echo '</form>';

        echo '<hr />';
        (new SnippetConflictChecker())->render_report();
        echo '</div>';
    }
}

