<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('assets')->name('assets.')->group(function () {
    Route::get('/', fn () => view('assets.index'))->name('index');
});
