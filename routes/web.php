<?php

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::redirect('/offline', '/offline.html')->name('offline');
Route::get('/health', HealthCheckController::class)->name('health');

require __DIR__.'/web/system.php';
require __DIR__.'/web/files.php';
require __DIR__.'/web/user.php';
require __DIR__.'/web/payroll.php';
require __DIR__.'/web/admin.php';
