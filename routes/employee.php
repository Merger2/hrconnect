<?php

use App\Models\Employee;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('admin/employees')->name('admin.employees.')->group(function () {
    Route::get('/', fn () => view('employee.index'))->name('index');
    Route::get('/{employee}', fn (Employee $employee) => view('employee.show', ['employee' => $employee]))
        ->middleware('can:view,employee')
        ->name('show');
});
