<?php

use App\Http\Controllers\Auth\VerifyEmailCodeController;
use App\Http\Controllers\SseNotificationController;
use App\Http\Controllers\System\AuthDebugController;
use App\Http\Controllers\System\BoostBrowserLogsController;
use App\Http\Controllers\System\E2eDocumentUploadController;
use App\Http\Controllers\System\E2eLoginController;
use App\Http\Controllers\System\LanguageController;
use App\Http\Controllers\System\LegacyRedirectController;
use App\Http\Controllers\System\ResetServiceWorkerController;
use App\Http\Controllers\System\RootRedirectController;
use App\Http\Controllers\System\TestErrorController;
use App\Http\Controllers\System\VercelMaintenanceController;
use App\Models\SystemBackupRun;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

// Boost MCP browser logger endpoint — required to prevent infinite error loop
Route::post('/_boost/browser-logs', BoostBrowserLogsController::class)->name('boost.browser-logs');

// Test Error Views. Keep this helper out of production so arbitrary users cannot
// trigger dedicated error responses on demand.
Route::get('/test-error/{code}', TestErrorController::class)->whereNumber('code');
Route::get('/reset-sw', ResetServiceWorkerController::class);

// config('jetstream.auth_session') is null in this app; Route::middleware() does
// not filter null entries (unlike RouteRegistrar groups), which would resolve
// the empty string to a non-existent middleware class. Filter them explicitly.
Route::get('/__auth-debug', AuthDebugController::class)->middleware(array_filter([
    'auth:sanctum',
    config('jetstream.auth_session'),
]));

Route::get('/__e2e-login', E2eLoginController::class);

Route::post('/__e2e-document-upload', E2eDocumentUploadController::class)->middleware(array_filter([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]));

Route::post('/email/verify-code', VerifyEmailCodeController::class)
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.code.verify');

Route::post('/__vercel-migrate', VercelMaintenanceController::class)
    ->middleware('throttle:3,1')
    ->withoutMiddleware([PreventRequestForgery::class]);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', RootRedirectController::class);

    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::livewire('/system-maintenance', 'admin.system-maintenance')
            ->name('admin.system-maintenance')
            ->middleware('feature.lock:system_maintenance,admin.system_maintenance.view,admin.dashboard')
            ->can('viewAny', SystemBackupRun::class);
    });

    Route::get('/sse/notifications', [SseNotificationController::class, 'stream'])
        ->name('sse.notifications');
});

Livewire::setUpdateRoute(function ($handle, string $path) {
    return Route::post(get_non_root_base_url_path().$path, $handle);
});

Livewire::setScriptRoute(function ($handle, string $path) {
    return Route::get(get_non_root_base_url_path().$path, $handle);
});

Route::controller(LanguageController::class)->group(function () {
    Route::post('/user/language', 'update')->name('user.language.update');
});

Route::controller(LegacyRedirectController::class)->group(function () {
    Route::get('/enterprise-support', 'enterpriseSupport')
        ->name('enterprise-support.whatsapp');

    Route::get('/admin/commercial', 'commercial')
        ->name('admin.commercial');
});
