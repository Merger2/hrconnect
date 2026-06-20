<?php

namespace App\Observers;

use App\Enums\PayrollStatus;
use App\Jobs\GeneratePayslipPdfJob;
use App\Models\Payroll;
use App\Notifications\PayrollPublished;

class PayrollObserver
{
    public function updated(Payroll $payroll): void
    {
        if ($payroll->wasChanged('status') && $payroll->status === PayrollStatus::PUBLISHED) {
            GeneratePayslipPdfJob::dispatch($payroll)->afterCommit();

            if ($payroll->employee?->user) {
                $payroll->employee->user->notify(new PayrollPublished($payroll));
            }
        }
    }
}
