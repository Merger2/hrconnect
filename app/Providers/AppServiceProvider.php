<?php

namespace App\Providers;

use App\Contracts\AttendanceServiceInterface;
use App\Contracts\AuditServiceInterface;
use App\Livewire\Admin\ImportExport\AttendanceImportExport;
use App\Livewire\Admin\ImportExport\UserImportExport;
use App\Models\Attendance;
use App\Models\BpjsConfig;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Role;
use App\Observers\AttendanceObserver;
use App\Observers\BpjsConfigObserver;
use App\Observers\CompanySettingObserver;
use App\Observers\EmployeeObserver;
use App\Observers\HolidayObserver;
use App\Observers\LeaveObserver;
use App\Observers\PayrollObserver;
use App\Observers\RoleObserver;
use App\Services\Attendance\CommunityService;
use App\Services\Attendance\GeofenceService;
use App\Services\Audit\CommunityAuditService;
use App\Services\Security\EmbeddingService;
use App\Services\Security\FaceRecognitionService;
use App\Services\Support\NavigationService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Livewire;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Pgvector\Laravel\Schema as PgvectorSchema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A-7: Service bindings for DI
        $this->app->singleton(FaceRecognitionService::class);
        $this->app->singleton(GeofenceService::class);
        $this->app->singleton(EmbeddingService::class);
        $this->app->singleton(NavigationService::class);

        // Service contracts
        $this->app->bind(AttendanceServiceInterface::class, CommunityService::class);
        $this->app->bind(AuditServiceInterface::class, CommunityAuditService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        PgvectorSchema::register();
        $this->registerObservers();
        $this->registerViewComposers();
        $this->registerLivewireAliases();
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

        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            $user = $request->user();

            return $user?->isAdmin
                ? route('admin.dashboard')
                : route('home');
        });

        $this->configureRateLimiting();
    }

    /**
     * Configure API rate limiting.
     *
     * Global API throttle: 60 requests per minute per user (or IP for guests).
     * Per-endpoint throttles (login 5/min, clock-in 5/5min, etc.) override this
     * via more specific middleware declarations on individual routes.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        RateLimiter::for('wilayah', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('attendance-integrations', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }

    /**
     * Register Eloquent Model Observers.
     *
     * Observer untuk:
     * - BpjsConfig, Holiday — cache invalidation
     * - Employee — auto-assign default shift
     * - Attendance — cache invalidation
     */
    protected function registerObservers(): void
    {
        BpjsConfig::observe(BpjsConfigObserver::class);
        Holiday::observe(HolidayObserver::class);
        Employee::observe(EmployeeObserver::class);
        Attendance::observe(AttendanceObserver::class);
        CompanySetting::observe(CompanySettingObserver::class);
        Leave::observe(LeaveObserver::class);
        Payroll::observe(PayrollObserver::class);
        Role::observe(RoleObserver::class);
    }

    /**
     * Register explicit Livewire component aliases for routes that reference
     * kebab names that do not match class auto-discovery (e.g. the
     * import-export pages whose classes live in the ImportExport namespace).
     */
    protected function registerLivewireAliases(): void
    {
        app('livewire')->component('admin.import-export.user', UserImportExport::class);
        app('livewire')->component('admin.import-export.attendance', AttendanceImportExport::class);
    }

    /**
     * Register View Composers for shared data injection.
     *
     * Pattern from laravel-smarthr: View::composer injects menu items
     * built by a service that filters config/menu.php by user permissions.
     */
    protected function registerViewComposers(): void
    {
        View::composer('layouts.app.sidebar', function ($view) {
            $user = auth()->user();
            $menu = $user ? app(NavigationService::class)->build($user) : [];
            $view->with('sidebarMenu', $menu);
        });
    }
}
