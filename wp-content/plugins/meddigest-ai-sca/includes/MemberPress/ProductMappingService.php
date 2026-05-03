<?php
/**
 * MemberPress product mapping service.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\MemberPress;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductMappingService
{
    public const OPTION_NAME = 'meddigest_ai_sca_settings';

    public const CREDIT_PACKS = [
        'essential'    => [
            'label'       => 'Essential Pack',
            'price_label' => '£10',
            'credits'     => 8,
            'note'        => 'Good for trying single stations',
        ],
        'practice_pro' => [
            'label'       => 'Practice Pro',
            'price_label' => '£25',
            'credits'     => 25,
            'note'        => 'Most popular',
        ],
        'exam_ready'   => [
            'label'       => 'Exam Ready',
            'price_label' => '£45',
            'credits'     => 50,
            'note'        => 'Best value',
        ],
    ];

    /**
     * Ensure default option exists.
     */
    public static function ensure_default_settings()
    {
        if (false === get_option(self::OPTION_NAME, false)) {
            add_option(self::OPTION_NAME, self::defaults(), '', false);
        }
    }

    /**
     * Default settings.
     */
    public static function defaults()
    {
        return [
            'sca_cases_premium_product_id' => 0,
            'credit_pack_products'         => [
                'essential'    => ['product_id' => 0],
                'practice_pro' => ['product_id' => 0],
                'exam_ready'   => ['product_id' => 0],
            ],
        ];
    }

    /**
     * Sanitize settings from admin form.
     *
     * @param array $input Raw input.
     */
    public static function sanitize_settings($input)
    {
        $input    = is_array($input) ? $input : [];
        $defaults = self::defaults();

        $settings = [
            'sca_cases_premium_product_id' => isset($input['sca_cases_premium_product_id']) ? absint($input['sca_cases_premium_product_id']) : 0,
            'credit_pack_products'         => $defaults['credit_pack_products'],
        ];

        foreach (array_keys(self::CREDIT_PACKS) as $pack_key) {
            $product_id = 0;

            if (isset($input['credit_pack_products'][$pack_key]['product_id'])) {
                $product_id = absint($input['credit_pack_products'][$pack_key]['product_id']);
            }

            $settings['credit_pack_products'][$pack_key] = [
                'product_id' => $product_id,
            ];
        }

        return $settings;
    }

    /**
     * Get settings with defaults applied.
     */
    public function get_settings()
    {
        self::ensure_default_settings();

        $stored = get_option(self::OPTION_NAME, []);
        $stored = is_array($stored) ? $stored : [];

        return array_replace_recursive(self::defaults(), $stored);
    }

    /**
     * Get mapped SCA Cases Premium product ID.
     */
    public function get_sca_cases_premium_product_id()
    {
        $settings = $this->get_settings();

        return absint($settings['sca_cases_premium_product_id']);
    }

    /**
     * Get configured credit packs.
     */
    public function get_credit_packs()
    {
        $settings = $this->get_settings();
        $packs    = [];

        foreach (self::CREDIT_PACKS as $pack_key => $pack) {
            $product_id = isset($settings['credit_pack_products'][$pack_key]['product_id'])
                ? absint($settings['credit_pack_products'][$pack_key]['product_id'])
                : 0;

            $packs[$pack_key] = array_merge(
                $pack,
                [
                    'key'        => $pack_key,
                    'product_id' => $product_id,
                    'url'        => $this->get_product_url($product_id),
                    'configured' => $this->is_valid_memberpress_product($product_id),
                ]
            );
        }

        return $packs;
    }

    /**
     * Find a credit pack by MemberPress product ID.
     *
     * @param int $product_id Product ID.
     */
    public function get_credit_pack_by_product_id($product_id)
    {
        $product_id = absint($product_id);

        foreach ($this->get_credit_packs() as $pack) {
            if ($product_id > 0 && $product_id === absint($pack['product_id'])) {
                return $pack;
            }
        }

        return null;
    }

    /**
     * Get product URL.
     *
     * @param int $product_id Product ID.
     */
    private function get_product_url($product_id)
    {
        $product_id = absint($product_id);

        if ($product_id <= 0 || !function_exists('get_permalink')) {
            return '';
        }

        $url = get_permalink($product_id);

        return $url ? $url : '';
    }

    /**
     * Validate mapped product ID.
     *
     * @param int $product_id Product ID.
     */
    public function is_valid_memberpress_product($product_id)
    {
        $product_id = absint($product_id);

        if ($product_id <= 0 || !function_exists('get_post_type')) {
            return false;
        }

        return 'memberpressproduct' === get_post_type($product_id);
    }
}

