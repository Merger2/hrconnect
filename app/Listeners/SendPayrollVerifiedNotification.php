<?php

namespace App\Listeners;

use App\Events\PayrollVerified;
use App\Models\User;
use App\Notifications\PayrollVerified as PayrollVerifiedNotification;

class SendPayrollVerifiedNotification
{
    public function handle(PayrollVerified $event): void
    {
        $payroll = $event->payroll;

        User::role('finance')->each(function (User $user) use ($payroll): void {
            $user->notify(new PayrollVerifiedNotification($payroll));
        });
    }
}
