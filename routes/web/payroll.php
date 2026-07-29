<?php

use App\Models\Payroll;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::middleware('user')->group(function () {
        Route::livewire('/payroll', 'user.my-payslips')
            ->name('my-payslips')
            ->middleware('feature.lock:payroll,user,home')
            ->can('viewAny', Payroll::class);
    });

    Route::prefix('admin')->middleware(['admin', 'can:accessAdminPanel'])->group(function () {
        Route::livewire('/payrolls/settings', 'admin.payroll-settings')
            ->name('admin.payroll.settings')
            ->middleware('feature.lock:payroll,admin.payroll_settings.manage,admin.dashboard')
            ->can('managePayrollSettings');

        Route::livewire('/payrolls', 'admin.payroll-manager')
            ->name('admin.payrolls')
            ->middleware('feature.lock:payroll,admin.payroll.view,admin.dashboard')
            ->can('viewAdminAny', Payroll::class);
    });
});
