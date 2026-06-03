<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('leaves')->name('leaves.')->group(function () {
    Route::get('/', fn () => view('leaves.index'))->name('index');
    Route::get('/apply', fn () => view('leaves.apply'))->name('apply');
});
