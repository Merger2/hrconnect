<?php

namespace App\Listeners;

use App\Events\PayrollRejected;
use App\Notifications\PayrollRejected as PayrollRejectedNotification;

class SendPayrollRejectedNotification
{
    public function handle(PayrollRejected $event): void
    {
        $payroll = $event->payroll;
        $user = $payroll->employee?->user;

        if ($user) {
            $user->notify(new PayrollRejectedNotification($payroll, $event->reason));
        }
    }
}
