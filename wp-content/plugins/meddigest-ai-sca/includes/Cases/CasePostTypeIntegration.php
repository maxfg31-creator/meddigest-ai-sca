<?php
/**
 * Existing case post type integration.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Cases;

if (!defined('ABSPATH')) {
    exit;
}

final class CasePostTypeIntegration
{
    /**
     * Candidate post types that may hold existing SCA cases.
     */
    public static function case_post_types()
    {
        $default = ['sca_case', 'sca-cases', 'case', 'cases'];
        $types   = apply_filters('meddigest_ai_sca_case_post_types', $default);
        $types   = is_array($types) ? $types : $default;
        $types   = array_map('sanitize_key', $types);

        if (function_exists('post_type_exists')) {
            $types = array_values(array_filter($types, 'post_type_exists'));
        }

        return $types;
    }

    /**
     * Whether a post type should receive AI settings.
     *
     * @param string $post_type Post type.
     */
    public static function is_case_post_type($post_type)
    {
        if (is_array($post_type)) {
            foreach ($post_type as $single_type) {
                if (self::is_case_post_type($single_type)) {
                    return true;
                }
            }

            return false;
        }

        return in_array(sanitize_key($post_type), self::case_post_types(), true);
    }
}
