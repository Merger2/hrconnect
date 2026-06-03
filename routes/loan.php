<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('loans')->name('loans.')->group(function () {
    Route::get('/', fn () => view('loans.index'))->name('index');
});
