<?php

use App\Livewire\Admin\AttendanceMatrix;
use App\Livewire\Admin\LeaveAdmin;
use App\Livewire\Admin\OvertimeAdmin;
use App\Livewire\Admin\PayrollManager;
use App\Livewire\Admin\ReimbursementAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/payroll', PayrollManager::class)->middleware('can:view_payrolls')->name('payroll.index');

    Route::get('/attendance', AttendanceMatrix::class)->middleware('can:view_attendances')->name('attendance.index');

    Route::get('/leaves', LeaveAdmin::class)->middleware('can:view_leaves')->name('leaves.index');

    Route::get('/overtimes', OvertimeAdmin::class)->middleware('can:view_overtimes')->name('overtimes.index');

    Route::get('/reimbursements', ReimbursementAdmin::class)->middleware('can:view_reimbursements')->name('reimbursements.index');
});
