<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/', fn () => view('approvals.index'))->name('index');
});
