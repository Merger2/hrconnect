<?php

use App\Http\Controllers\Admin\ImportExport\ExportReportPdfController;
use App\Http\Controllers\Admin\Reports\ExportLeaveReportController;
use App\Http\Controllers\Admin\Reports\ExportOvertimeReportController;
use App\Http\Controllers\Admin\Reports\ExportPayrollReportController;
use App\Http\Controllers\Admin\Reports\ExportScheduleReportController;
use App\Http\Controllers\Admin\Reports\ReportCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/reports', ReportCenterController::class)->name('admin.reports.index')->can('viewOperationalReports');
Route::get('/reports/leaves/export', ExportLeaveReportController::class)->name('admin.reports.leaves.export')->can('manageLeaveApprovals');
Route::get('/reports/overtime/export', ExportOvertimeReportController::class)->name('admin.reports.overtime.export')->can('manageOvertime');
Route::get('/reports/schedules/export', ExportScheduleReportController::class)->name('admin.reports.schedules.export')->can('manageSchedules');
Route::get('/reports/payrolls/export', ExportPayrollReportController::class)->name('admin.reports.payrolls.export')->middleware('feature.lock:payroll,admin.payroll.view,admin.dashboard')->can('viewAdminPayroll');
Route::get('/reports/export-pdf', ExportReportPdfController::class)->name('admin.reports.export-pdf')->middleware('feature.lock:reporting,admin.attendances.export,admin.dashboard')->can('exportAdminReports');
