<?php

use App\Http\Controllers\Admin\Attendance\AttendanceController as AdminAttendanceController;
use App\Models\Attendance as AttendanceRecord;
use App\Models\AttendanceCorrection;
use Illuminate\Support\Facades\Route;

Route::middleware('feature.lock:attendance,admin.attendances.view,admin.dashboard')->group(function () {

    Route::livewire('/schedules', 'admin.schedule-component')->name('admin.schedules')->can('manageSchedules');
    Route::livewire('/attendances', 'admin.attendance-component')->name('admin.attendances')->can('viewAdminAny', AttendanceRecord::class);
    Route::get('/attendances/report', [AdminAttendanceController::class, 'report'])->name('admin.attendances.report')->can('viewAttendanceReports');
    Route::livewire('/attendance-corrections', 'admin.attendance-correction-manager')->name('admin.attendance-corrections')->can('viewAdminAny', AttendanceCorrection::class);
    Route::livewire('/leaves', 'admin.leave-approval')->name('admin.leaves')->can('manageLeaveApprovals');
    Route::redirect('/leaves/admin', '/admin/leaves', 301)->name('admin.leaves.admin')->can('view_leave_settings');
    Route::livewire('/shift-swaps', 'admin.shift-swap-approval-manager')->name('admin.shift-swaps')->can('manageShiftSwapApprovals');
    Route::livewire('/overtime', 'admin.overtime-manager')->name('admin.overtime')->can('manageOvertime');

});
