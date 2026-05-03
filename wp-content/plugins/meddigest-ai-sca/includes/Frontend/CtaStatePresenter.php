<?php
/**
 * CTA state presenter placeholder for later milestones.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

final class CtaStatePresenter
{
    /**
     * Milestone 1 only exposes pricing credit pack state.
     */
    public function is_available()
    {
        return false;
    }
}

