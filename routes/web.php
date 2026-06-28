<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

// Semua route ber-auth dilindungi password expiry check (CAT-005, default 90 hari).
// Route 'security.edit' dan 'logout' otomatis di-skip oleh middleware.
Route::middleware(['auth', 'verified', 'password.expired'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
