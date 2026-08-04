<?php

namespace App\Support;

use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OvertimeCalculator
{
    /**
     * Resolve the overtime window into full Carbon datetimes.
     *
     * @return Carbon[] [start, end]
     */
    public function resolveWindow(string $date, string $startTime, string $endTime): array
    {
        $start = Carbon::parse($date)->setTimeFromTimeString($startTime);
        $end = Carbon::parse($date)->setTimeFromTimeString($endTime);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * Calculate duration in minutes between two Carbon instances.
     */
    public function durationInMinutes(Carbon $start, Carbon $end): int
    {
        return (int) $start->diffInMinutes($end);
    }

    /**
     * Check whether a new overtime window overlaps with any existing overtimes.
     *
     * @param  Collection<int, Overtime>  $existingOvertimes
     */
    public function hasOverlap(Collection $existingOvertimes, Carbon $start, Carbon $end): bool
    {
        foreach ($existingOvertimes as $existing) {
            // Kolom start_time/end_time bertipe `time` — tanpa tanggal. Gabungkan
            // dengan tanggal `date` dari record biar perbandingan interval benar
            // (kalau tidak, Carbon::parse('18:00:00') memakai tanggal hari ini).
            $existingStart = Carbon::parse($existing->date)->setTimeFromTimeString((string) $existing->start_time);
            $existingEnd = Carbon::parse($existing->date)->setTimeFromTimeString((string) $existing->end_time);

            if ($start->lessThan($existingEnd) && $end->greaterThan($existingStart)) {
                return true;
            }
        }

        return false;
    }
}
