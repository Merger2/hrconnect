<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('reimbursements')->name('reimbursements.')->group(function () {
    Route::get('/', fn () => view('reimbursements.index'))->middleware('can:view_reimbursements')->name('index');
    Route::get('/apply', fn () => view('reimbursements.apply'))->middleware('can:view_reimbursements')->name('apply');
});
