<?php
namespace MedDigest\AiSca\Mock;

if (!defined('ABSPATH')) {
    exit;
}

final class MockSchedule
{
    public const READING_SECONDS = 180;
    public const LIVE_SECONDS    = 720;
    public const BREAK_SECONDS   = 600;
    public const STATIONS        = 12;

    /**
     * Build server-time schedule from a UTC timestamp.
     *
     * @param int $start_ts UTC timestamp.
     */
    public function build($start_ts)
    {
        $cursor   = absint($start_ts);
        $stations = [];

        for ($station = 1; $station <= self::STATIONS; $station++) {
            $reading_start = $cursor;
            $reading_end   = $reading_start + self::READING_SECONDS;
            $live_start    = $reading_end;
            $live_end      = $live_start + self::LIVE_SECONDS;

            $stations[] = [
                'station_number'   => $station,
                'reading_start_at' => gmdate('Y-m-d H:i:s', $reading_start),
                'reading_end_at'   => gmdate('Y-m-d H:i:s', $reading_end),
                'live_start_at'    => gmdate('Y-m-d H:i:s', $live_start),
                'live_end_at'      => gmdate('Y-m-d H:i:s', $live_end),
            ];

            $cursor = $live_end;

            if (6 === $station) {
                $cursor += self::BREAK_SECONDS;
            }
        }

        return [
            'started_at'      => gmdate('Y-m-d H:i:s', $start_ts),
            'total_seconds'   => self::STATIONS * (self::READING_SECONDS + self::LIVE_SECONDS) + self::BREAK_SECONDS,
            'break_after'     => 6,
            'break_seconds'   => self::BREAK_SECONDS,
            'completed_at'    => gmdate('Y-m-d H:i:s', $cursor),
            'stations'        => $stations,
        ];
    }

    /**
     * Determine current phase from a schedule.
     *
     * @param array $schedule Schedule.
     * @param int   $now_ts   Current UTC timestamp.
     */
    public function current_phase(array $schedule, $now_ts)
    {
        $now_ts = absint($now_ts);

        if (empty($schedule['stations']) || !is_array($schedule['stations'])) {
            return [
                'phase'             => 'processing',
                'station_number'    => 12,
                'phase_ends_at'     => '',
                'seconds_remaining' => 0,
            ];
        }

        foreach ($schedule['stations'] as $station) {
            $number        = absint($station['station_number'] ?? 0);
            $reading_start = strtotime($station['reading_start_at'] ?? '');
            $reading_end   = strtotime($station['reading_end_at'] ?? '');
            $live_start    = strtotime($station['live_start_at'] ?? '');
            $live_end      = strtotime($station['live_end_at'] ?? '');

            if ($now_ts >= $reading_start && $now_ts < $reading_end) {
                return $this->phase('reading', $number, $station['reading_end_at'], $reading_end - $now_ts);
            }

            if ($now_ts >= $live_start && $now_ts < $live_end) {
                return $this->phase('live', $number, $station['live_end_at'], $live_end - $now_ts);
            }

            if (6 === $number) {
                $next = $schedule['stations'][6] ?? null;

                if ($next) {
                    $break_end = strtotime($next['reading_start_at'] ?? '');

                    if ($now_ts >= $live_end && $now_ts < $break_end) {
                        return $this->phase('break', 6, $next['reading_start_at'], $break_end - $now_ts);
                    }
                }
            }
        }

        return $this->phase('processing', 12, $schedule['completed_at'] ?? '', 0);
    }

    /**
     * Phase response.
     *
     * @param string $phase          Phase.
     * @param int    $station_number Station number.
     * @param string $phase_ends_at  Phase end.
     * @param int    $remaining      Seconds remaining.
     */
    private function phase($phase, $station_number, $phase_ends_at, $remaining)
    {
        return [
            'phase'             => $phase,
            'station_number'    => absint($station_number),
            'phase_ends_at'     => (string) $phase_ends_at,
            'seconds_remaining' => max(0, absint($remaining)),
        ];
    }
}
