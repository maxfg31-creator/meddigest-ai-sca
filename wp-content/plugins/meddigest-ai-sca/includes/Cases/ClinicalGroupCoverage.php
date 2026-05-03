<?php
/**
 * Clinical group coverage checks.
 *
 * @package MedDigest\AiSca
 */

namespace MedDigest\AiSca\Cases;

use MedDigest\AiSca\Mock\MockCoverageService;

if (!defined('ABSPATH')) {
    exit;
}

final class ClinicalGroupCoverage
{
    /**
     * Return the Full Mock coverage report.
     */
    public function report()
    {
        return (new MockCoverageService())->coverage_report();
    }
}
