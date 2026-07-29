<?php

use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AttendancePhotoController;
use App\Http\Controllers\User\HrChecklistTaskAttachmentController;
use App\Http\Controllers\User\ProjectVisitEvidenceAttachmentController;
use App\Http\Controllers\User\ReimbursementAttachmentController;
use Illuminate\Support\Facades\Route;

Route::controller(AttendancePhotoController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/attendance/photo/{attendance}/{type}/{index?}', 'show')
        ->name('attendance.photo')
        ->can('view', 'attendance');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/secure-attachment/{attendance}', [AttendanceController::class, 'downloadAttachment'])
        ->name('attendance.attachment.download')
        ->can('view', 'attendance');

    Route::get('/reimbursement/attachment/{reimbursement}', [ReimbursementAttachmentController::class, 'show'])
        ->name('reimbursement.attachment.download')
        ->can('view', 'reimbursement');

    Route::get('/hr-checklist/task-attachment/{task}', [HrChecklistTaskAttachmentController::class, 'show'])
        ->name('hr-checklist.task-attachment.download')
        ->can('downloadAttachment', 'task');

    Route::get('/operations/visit-evidence/{evidence}/photo', [ProjectVisitEvidenceAttachmentController::class, 'show'])
        ->name('operations.visit-evidence.photo')
        ->can('downloadPhoto', 'evidence');
});
