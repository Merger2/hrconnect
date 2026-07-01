<?php

use App\Http\Controllers\Web\LeaveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('leaves')->name('leaves.')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->name('index');
    Route::get('/apply', [LeaveController::class, 'apply'])->name('apply');
});
