<?php
namespace MedDigest\AiSca\Practice;

if (!defined('ABSPATH')) {
    exit;
}

final class QuickCancelPolicy
{
    /**
     * Whether a station should be treated as quick-cancel.
     *
     * @param array $attempt Attempt row.
     */
    public function is_quick_cancel(array $attempt)
    {
        if (empty($attempt['live_started_at'])) {
            return true;
        }

        $started = strtotime($attempt['live_started_at']);
        $ended   = !empty($attempt['ended_at']) ? strtotime($attempt['ended_at']) : time();

        return $started && $ended && ($ended - $started) < 30;
    }
}
