<?php

use App\Http\Controllers\Web\LeaveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('leaves')->name('leaves.')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->middleware('can:view_leaves')->name('index');
    Route::get('/apply', [LeaveController::class, 'apply'])->middleware('can:view_leaves')->name('apply');
});
