<?php
/**
 * Product mapping admin field rendering.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductMappings
{
    /**
     * Get MemberPress product posts.
     */
    public function get_products()
    {
        if (!function_exists('post_type_exists') || !post_type_exists('memberpressproduct')) {
            return [];
        }

        return get_posts(
            [
                'post_type'      => 'memberpressproduct',
                'post_status'    => ['publish', 'draft', 'private'],
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );
    }

    /**
     * Render product select.
     *
     * @param string $field_name Field name.
     * @param int    $value      Selected product ID.
     */
    public function render_select($field_name, $value)
    {
        $products = $this->get_products();
        $value    = absint($value);

        printf('<select name="%s">', esc_attr($field_name));
        echo '<option value="0">' . esc_html__('Select a MemberPress product', 'meddigest-ai-sca') . '</option>';

        foreach ($products as $product) {
            printf(
                '<option value="%1$d" %2$s>%3$s (#%1$d)</option>',
                absint($product->ID),
                selected($value, $product->ID, false),
                esc_html(get_the_title($product))
            );
        }

        echo '</select>';

        if (empty($products)) {
            echo '<p class="description">' . esc_html__('No MemberPress products were found. Confirm MemberPress is active before mapping products.', 'meddigest-ai-sca') . '</p>';
        }
    }
}

