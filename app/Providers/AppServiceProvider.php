<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\BpjsConfig;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\TaxConfig;
use App\Observers\AttendanceObserver;
use App\Observers\BpjsConfigObserver;
use App\Observers\EmployeeObserver;
use App\Observers\HolidayObserver;
use App\Observers\LeaveObserver;
use App\Observers\PayrollObserver;
use App\Observers\TaxConfigObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register Eloquent Model Observers.
     *
     * Observer untuk:
     * - TaxConfig, BpjsConfig, Holiday — cache invalidation (Sesi 5-7)
     * - Employee — auto-assign default shift (Sesi 8)
     * - Attendance — cache invalidation (Sesi 9, prep for V2 dashboard)
     */
    protected function registerObservers(): void
    {
        TaxConfig::observe(TaxConfigObserver::class);
        BpjsConfig::observe(BpjsConfigObserver::class);
        Holiday::observe(HolidayObserver::class);
        Employee::observe(EmployeeObserver::class);
        Attendance::observe(AttendanceObserver::class);
        Leave::observe(LeaveObserver::class);
        Payroll::observe(PayrollObserver::class);
    }
}
