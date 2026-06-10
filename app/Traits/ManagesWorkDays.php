<?php

namespace App\Traits;

use App\Enums\RequestStatus;
use App\Models\Holiday;
use App\Models\Leave;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

trait ManagesWorkDays
{
    /**
     * Cache holiday dates untuk range yang sedang diproses.
     */
    protected ?Collection $holidayCache = null;

    protected ?string $holidayCacheStart = null;

    protected ?string $holidayCacheEnd = null;

    /**
     * Menghitung jumlah hari kerja efektif, membuang Weekend, Hari Libur Nasional,
     * dan hari unpaid leave yang sudah disetujui (B-4).
     *
     * Optimasi: holiday di-fetch SEKALI sebagai flat array, bukan query per hari.
     */
    public function countWorkingDays(CarbonInterface $start, CarbonInterface $end): int
    {
        $originalStart = $start->copy();
        $start = $start->copy();
        $end = $end->copy();

        $holidays = $this->getHolidaysFlat($originalStart, $end);

        $workingDays = 0;

        while ($start->lte($end)) {
            if (! $start->isWeekend() && ! in_array($start->toDateString(), $holidays)) {
                $workingDays++;
            }
            $start = $start->addDay();
        }

        // B-4: Kurangi unpaid leave yang sudah disetujui (hanya hari kerja efektif)
        $unpaidDays = $this->countUnpaidLeaveDays($originalStart, $end, $holidays);

        return max(0, $workingDays - $unpaidDays);
    }

    /**
     * Hitung jumlah hari unpaid leave yang disetujui dalam range tanggal,
     * hanya menghitung hari kerja (exclude weekend + holiday).
     */
    protected function countUnpaidLeaveDays(CarbonInterface $rangeStart, CarbonInterface $rangeEnd, array $holidays): int
    {
        $totalDays = 0;
        $approvedUnpaidLeaves = Leave::whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
            ->whereIn('status', [
                RequestStatus::APPROVED_L1->value,
                RequestStatus::APPROVED->value,
            ])
            ->where('start_date', '<=', $rangeEnd->toDateString())
            ->where('end_date', '>=', $rangeStart->toDateString())
            ->get();

        foreach ($approvedUnpaidLeaves as $leave) {
            $leaveStart = CarbonImmutable::parse($leave->start_date)->max($rangeStart);
            $leaveEnd = CarbonImmutable::parse($leave->end_date)->min($rangeEnd);

            if ($leaveStart->lte($leaveEnd)) {
                $current = $leaveStart->copy();
                while ($current->lte($leaveEnd)) {
                    if (! $current->isWeekend() && ! in_array($current->toDateString(), $holidays)) {
                        $totalDays++;
                    }
                    $current = $current->addDay();
                }
            }
        }

        return $totalDays;
    }

    /**
     * Fetch holidays sebagai flat array of date strings untuk range tertentu.
     * Hasil di-cache per instance untuk menghindari multiple query.
     */
    protected function getHolidaysFlat(CarbonInterface $start, CarbonInterface $end): array
    {
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        if ($this->holidayCache !== null
            && $this->holidayCacheStart === $startStr
            && $this->holidayCacheEnd === $endStr
        ) {
            return $this->holidayCache->toArray();
        }

        $this->holidayCache = Holiday::where('is_active', true)
            ->whereBetween('date', [$startStr, $endStr])
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString());

        $this->holidayCacheStart = $startStr;
        $this->holidayCacheEnd = $endStr;

        return $this->holidayCache->toArray();
    }
}
