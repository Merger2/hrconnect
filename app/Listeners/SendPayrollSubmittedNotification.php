<?php

namespace App\Listeners;

use App\Events\PayrollSubmitted;
use App\Models\User;
use App\Notifications\PayrollSubmitted as PayrollSubmittedNotification;

class SendPayrollSubmittedNotification
{
    public function handle(PayrollSubmitted $event): void
    {
        $payroll = $event->payroll;

        User::role('admin')->each(function (User $user) use ($payroll): void {
            $user->notify(new PayrollSubmittedNotification($payroll));
        });
    }
}
