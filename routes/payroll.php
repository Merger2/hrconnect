<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/', fn () => view('payroll.index'))->middleware('can:view_payslip')->name('index');
});
