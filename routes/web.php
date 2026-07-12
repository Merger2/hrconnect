<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

// IT Support Monitoring Dashboard
Route::middleware(['auth', 'verified', 'can:view_activity_logs'])->group(function () {
    Route::get('monitoring', \App\Livewire\ItSupport\MonitoringDashboard::class)->name('monitoring');
});

// Semua route ber-auth dilindungi password expiry check (CAT-005, default 90 hari).
// Route 'security.edit' dan 'logout' otomatis di-skip oleh middleware.
Route::middleware(['auth', 'verified', 'password.expired'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
