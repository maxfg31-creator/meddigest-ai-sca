<?php
/**
 * Placeholder for Milestone 2 hold/commit/release flows.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Credits;

if (!defined('ABSPATH')) {
    exit;
}

final class CreditHoldService
{
    /**
     * Hold flows are introduced with station and mock launches in later milestones.
     */
    public function is_available()
    {
        return false;
    }
}

