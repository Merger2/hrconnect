<?php

use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        require __DIR__.'/admin/dashboard.php';
        require __DIR__.'/admin/attendance.php';
        require __DIR__.'/admin/hr-checklists.php';
        require __DIR__.'/admin/master-data.php';
        require __DIR__.'/admin/operations.php';
        require __DIR__.'/admin/import-export.php';
        require __DIR__.'/admin/reports.php';
        require __DIR__.'/admin/payroll.php';
        require __DIR__.'/admin/assets.php';
        require __DIR__.'/admin/settings.php';
        require __DIR__.'/admin/security.php';
    });
});
