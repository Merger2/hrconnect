<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PayrollPaid;
use App\Notifications\PayrollPaid as PayrollPaidNotification;

final class SendPayrollPaidNotification
{
    public function handle(PayrollPaid $event): void
    {
        $payroll = $event->payroll;
        $user = $payroll->employee?->user;

        if ($user) {
            $user->notify(new PayrollPaidNotification($payroll));
        }
    }
}
