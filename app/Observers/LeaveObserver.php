<?php

namespace App\Observers;

use App\Models\Leave;
use Illuminate\Support\Facades\Cache;

class LeaveObserver
{
    public function saved(Leave $leave): void
    {
        $this->invalidateCache($leave);
    }

    public function deleted(Leave $leave): void
    {
        $this->invalidateCache($leave);
    }

    private function invalidateCache(Leave $leave): void
    {
        $employeeId = $leave->employee_id;
        Cache::forget("leave:history:{$employeeId}");
        Cache::forget("leave:pending:{$employeeId}");
        Cache::forget("leave:summary:{$employeeId}:".$leave->start_date?->format('Y'));
    }
}
