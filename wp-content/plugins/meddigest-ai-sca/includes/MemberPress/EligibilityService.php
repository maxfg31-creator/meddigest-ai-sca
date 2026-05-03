<?php
/**
 * MemberPress eligibility checks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\MemberPress;

if (!defined('ABSPATH')) {
    exit;
}

final class EligibilityService
{
    /**
     * Whether a user has active SCA Cases Premium.
     *
     * @param int $user_id User ID.
     */
    public function user_has_sca_cases_premium($user_id)
    {
        $user_id    = absint($user_id);
        $product_id = (new ProductMappingService())->get_sca_cases_premium_product_id();

        if ($user_id <= 0 || $product_id <= 0) {
            return false;
        }

        $filtered = apply_filters('meddigest_ai_sca_user_has_sca_cases_premium', null, $user_id, $product_id);
        if (is_bool($filtered)) {
            return $filtered;
        }

        if (class_exists('\MeprUser')) {
            try {
                $member = new \MeprUser($user_id);

                if (method_exists($member, 'is_already_subscribed_to')) {
                    return (bool) $member->is_already_subscribed_to($product_id);
                }

                if (method_exists($member, 'is_active_on_membership')) {
                    return (bool) $member->is_active_on_membership($product_id);
                }
            } catch (\Throwable $throwable) {
                return false;
            }
        }

        return false;
    }
}

