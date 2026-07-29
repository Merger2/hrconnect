<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\PayrollApproved;
use App\Events\PayrollPaid;
use App\Events\PayrollRejected;
use App\Events\PayrollSubmitted;
use App\Events\PayrollVerified;
use App\Listeners\SendPayrollPaidNotification;
use App\Listeners\SendPayrollPublishedNotification;
use App\Listeners\SendPayrollRejectedNotification;
use App\Listeners\SendPayrollSubmittedNotification;
use App\Listeners\SendPayrollVerifiedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PayrollSubmitted::class => [
            SendPayrollSubmittedNotification::class,
        ],
        PayrollVerified::class => [
            SendPayrollVerifiedNotification::class,
        ],
        PayrollApproved::class => [
            SendPayrollPublishedNotification::class,
        ],
        PayrollPaid::class => [
            SendPayrollPaidNotification::class,
        ],
        PayrollRejected::class => [
            SendPayrollRejectedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
