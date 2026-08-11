<?php

use App\Http\Controllers\Payroll\PayslipDownloadController;
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

    // Download payslip (P1 fix 2026-08-11): route web + policy 'download'
    // (kepemilikan + status approved/paid). Di luar group 'user' karena
    // UserMiddleware abort 403 utk admin — route ini dipakai MyPayslips
    // (employee) DAN PayrollManager (admin). Pengganti dispatch
    // 'download-file' yang tidak punya listener di JS/blade.
    //
    // Keputusan Fikih 2026-08-11 (Minta PIN saat download — paling aman):
    // - GET -> pemilik lihat form PIN (PDF di-enkripsi dgn PIN plaintext),
    //   staff/admin terotorisasi stream langsung.
    // - POST -> verifikasi PIN (Hash::check) + throttle 6/menit anti brute-force.
    Route::get('/payroll/{payroll}/payslip', PayslipDownloadController::class)
        ->name('payslip.download')
        ->middleware('feature.lock:payroll,user,home');

    Route::post('/payroll/{payroll}/payslip', [PayslipDownloadController::class, 'store'])
        ->name('payslip.download')
        ->middleware(['feature.lock:payroll,user,home', 'throttle:6,1']);

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
