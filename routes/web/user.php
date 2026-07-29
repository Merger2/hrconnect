<?php

use App\Http\Controllers\Admin\Collaboration\DownloadCloudFileController;
use App\Http\Controllers\User\AppraisalExportPdfController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\EmployeeDocumentDownloadController;
use App\Http\Controllers\User\HomeController;
use App\Models\Appraisal;
use App\Models\Attendance as AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistTask;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\WorkFromHomeRequest;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::livewire('/notifications', 'user.notifications-page')->name('notifications');

    // Route::middleware('user')->group(function () {
    Route::get('/home', HomeController::class)->name('home');

    Route::controller(AttendanceController::class)->group(function () {
        Route::get('/apply-leave', 'applyLeave')->name('apply-leave')->can('create', AttendanceRecord::class);
        Route::post('/apply-leave', 'storeLeaveRequest')->name('store-leave-request')->can('create', AttendanceRecord::class);
        Route::get('/attendance-history', 'history')->name('attendance-history')->can('viewAny', AttendanceRecord::class);
    });

    Route::livewire('/attendance-corrections', 'user.attendance-correction-page')
        ->name('attendance-corrections')
        ->can('viewAny', AttendanceCorrection::class);

    Route::livewire('/reimbursement', 'user.reimbursement-page')
        ->name('reimbursement')
        ->can('viewAny', Reimbursement::class);

    Route::livewire('/my-schedule', 'user.shift-schedule-page')->name('my-schedule');
    Route::livewire('/shift-swap-requests', 'user.shift-swap-request-page')
        ->name('shift-swap-requests')
        ->can('viewAny', ShiftSwapRequest::class);
    Route::livewire('/wfh-requests', 'user.work-from-home-request-page')
        ->name('wfh-requests')
        ->can('viewAny', WorkFromHomeRequest::class);
    Route::livewire('/document-requests', 'user.employee-document-request-page')
        ->name('document-requests')
        ->can('viewAny', EmployeeDocumentRequest::class);
    Route::get('/document-requests/{documentRequest}/download', [EmployeeDocumentDownloadController::class, 'generated'])
        ->name('document-requests.download')
        ->can('download', 'documentRequest');
    Route::get('/document-requests/{documentRequest}/uploaded', [EmployeeDocumentDownloadController::class, 'uploaded'])
        ->name('document-requests.uploaded')
        ->can('downloadUpload', 'documentRequest');
    Route::livewire('/hr-tasks', 'user.hr-tasks-page')
        ->name('hr-tasks')
        ->can('viewAny', HrChecklistTask::class);
    Route::livewire('/my-tasks', 'user.my-operational-tasks')->name('my-tasks');
    Route::livewire('/collaboration', 'user.collaboration-inbox')->name('collaboration');
    Route::get('/collaboration/files/{file}/download', DownloadCloudFileController::class)
        ->name('collaboration.files.download')
        ->can('download', 'file');
    Route::livewire('/forms', 'user.my-custom-forms')->name('my-forms');
    Route::livewire('/approvals', 'user.team-approvals')
        ->name('approvals')
        ->can('reviewSubordinateRequests');
    Route::livewire('/approvals/history', 'user.team-approvals-history')
        ->name('approvals.history')
        ->can('reviewSubordinateRequests');
    Route::livewire('/overtime', 'user.overtime-request')->name('overtime')->can('viewAny', Overtime::class);
    Route::livewire('/my-kasbon', 'user.finance.my-cash-advances')->name('my-kasbon')->middleware('feature.lock:cash_advance,user,home')->can('viewAny', CashAdvance::class);
    Route::livewire('/team-kasbon', 'user.finance.team-cash-advance-manager')
        ->name('team-kasbon')
        ->middleware('feature.lock:cash_advance,gate:reviewSubordinateRequests,home')
        ->can('reviewSubordinateRequests');
    Route::livewire('/face-enrollment', 'user.face-enrollment')->name('face.enrollment');
    Route::livewire('/my-assets', 'user.my-assets')->name('my-assets')->middleware('feature.lock:assets,user,home')->can('viewAny', CompanyAsset::class);
    Route::livewire('/my-performance', 'user.my-performance')->name('my-performance')->middleware('feature.lock:appraisal,user,home')->can('viewAny', Appraisal::class);
    Route::get('/appraisal/{appraisal}/export-pdf', AppraisalExportPdfController::class)
        ->name('appraisal.export-pdf')
        ->can('exportPdf', 'appraisal');
    // });
});
