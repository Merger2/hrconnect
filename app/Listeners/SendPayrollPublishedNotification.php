<?php

namespace App\Listeners;

use App\Events\PayrollApproved;
use App\Notifications\PayrollPublished as PayrollPublishedNotification;

class SendPayrollPublishedNotification
{
    public function handle(PayrollApproved $event): void
    {
        $payroll = $event->payroll;
        $user = $payroll->employee?->user;

        if ($user) {
            $user->notify(new PayrollPublishedNotification($payroll));
        }
    }
}
