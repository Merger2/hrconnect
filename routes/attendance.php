<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', fn () => view('attendance.index'))->name('index');
    Route::get('/clock-in', fn () => view('attendance.clock-in'))->name('clock-in');
});
