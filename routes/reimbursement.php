<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('reimbursements')->name('reimbursements.')->group(function () {
    Route::get('/', fn () => view('reimbursements.index'))->name('index');
    Route::get('/apply', fn () => view('reimbursements.apply'))->name('apply');
});
