<?php

use App\Http\Controllers\Admin\ImportExport\DownloadImportExportRunController;
use App\Http\Controllers\Admin\ImportExport\ExportActivityLogsController;
use App\Http\Controllers\Admin\ImportExport\ExportAttendancesController;
use App\Http\Controllers\Admin\ImportExport\ExportUsersController;
use App\Http\Controllers\Admin\ImportExport\ImportAttendancesController;
use App\Http\Controllers\Admin\ImportExport\ImportUsersController;
use Illuminate\Support\Facades\Route;

Route::livewire('/import-export/users', 'admin.import-export.user')->name('admin.import-export.users')->middleware('feature.lock:reporting,admin.import_export_users.view,admin.dashboard')->can('viewUserImportExport');
Route::livewire('/import-export/attendances', 'admin.import-export.attendance')->name('admin.import-export.attendances')->middleware('feature.lock:reporting,admin.import_export_attendances.view,admin.dashboard')->can('viewAttendanceImportExport');
Route::post('/users/import', ImportUsersController::class)->name('admin.users.import')->middleware('feature.lock:reporting,admin.import_export_users.import,admin.dashboard')->can('importUsers');
Route::post('/attendances/import', ImportAttendancesController::class)->name('admin.attendances.import')->middleware('feature.lock:reporting,admin.import_export_attendances.import,admin.dashboard')->can('importAttendances');
Route::get('/users/export', ExportUsersController::class)->name('admin.users.export')->middleware('feature.lock:reporting,admin.import_export_users.export,admin.dashboard')->can('exportUsers');
Route::get('/attendances/export', ExportAttendancesController::class)->name('admin.attendances.export')->middleware('feature.lock:reporting,admin.import_export_attendances.export,admin.dashboard')->can('exportAttendances');
Route::get('/activity-logs/export', ExportActivityLogsController::class)->name('admin.activity-logs.export')->middleware('feature.lock:audit,admin.activity_logs.export,admin.dashboard')->can('exportActivityLogs');
Route::get('/import-export/runs/{run}/download', DownloadImportExportRunController::class)->name('admin.import-export.runs.download')->can('download', 'run');
