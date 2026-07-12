<?php

declare(strict_types=1);

use App\Livewire\Admin\EmployeeCreate;
use App\Livewire\Admin\EmployeeEdit;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('admin/employees')->name('admin.employees.')->group(function () {
    Route::get('/', fn () => view('employee.index'))
        ->middleware('can:viewAny,'.Employee::class)
        ->name('index');
    Route::get('/create', EmployeeCreate::class)
        ->middleware('can:create,App\Models\Employee')
        ->name('create');
    Route::get('/{employee}/edit', EmployeeEdit::class)
        ->middleware('can:update,employee')
        ->name('edit');
    Route::get('/{employee}', fn (Employee $employee) => view('employee.show', ['employee' => $employee]))
        ->middleware('can:view,employee')
        ->name('show');
});
