<?php

namespace App\Observers;

use App\Enums\PayrollStatus;
use App\Jobs\GeneratePayslipPdfJob;
use App\Models\Payroll;

class PayrollObserver
{
    public function updated(Payroll $payroll): void
    {
        if ($payroll->wasChanged('status') && $payroll->status === PayrollStatus::PUBLISHED) {
            // B-2: afterCommit() — jangan dispatch sebelum transaction commit
            GeneratePayslipPdfJob::dispatch($payroll)->afterCommit();
        }
    }
}
