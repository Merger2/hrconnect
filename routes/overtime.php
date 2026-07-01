<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('overtimes')->name('overtimes.')->group(function () {
    Route::get('/', fn () => view('overtimes.index'))->middleware('can:view_overtimes')->name('index');
    Route::get('/apply', fn () => view('overtimes.apply'))->middleware('can:view_overtimes')->name('apply');
});
