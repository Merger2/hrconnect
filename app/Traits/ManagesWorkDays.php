<?php

namespace App\Traits;

use App\Models\Holiday;
use Carbon\Carbon;

trait ManagesWorkDays
{
    /**
     * Menghitung jumlah hari kerja efektif, membuang Weekend dan Hari Libur Nasional.
     *
     * Optimasi: holiday di-fetch SEKALI sebagai flat array, bukan query per hari.
     */
    public function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $start = $start->copy();
        $end = $end->copy();

        $holidays = Holiday::where('is_active', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $workingDays = 0;

        while ($start->lte($end)) {
            if (! $start->isWeekend() && ! in_array($start->toDateString(), $holidays)) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }
}
