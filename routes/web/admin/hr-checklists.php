<?php

use App\Http\Controllers\User\EmployeeDocumentDownloadController;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistCase;
use Illuminate\Support\Facades\Route;

Route::middleware('feature.lock:hr_checklist,viewEmployees,admin.dashboard')->group(function () {

    Route::livewire('/employees', 'admin.employee-component')->name('admin.employees')->can('viewEmployees');

    Route::livewire('/employees/create', 'admin.employee-create')
        ->name('admin.employees.create')
        ->can('manage_employees');

    Route::livewire('/employees/{employee}/edit', 'admin.employee-edit')
        ->name('admin.employees.edit')
        ->can('manage_employees');

    Route::livewire('/hr-checklists', 'admin.hr-checklist-manager')
        ->name('admin.hr-checklists')
        ->can('viewAny', HrChecklistCase::class);

    Route::livewire('/document-requests', 'admin.employee-document-request-manager')->name('admin.document-requests')->can('viewAdminAny', EmployeeDocumentRequest::class);
    Route::livewire('/document-templates', 'admin.document-template-manager')->name('admin.document-templates')->can('manageDocumentTemplates');
    Route::redirect('/document-templates/library', '/admin/document-templates')
        ->name('admin.document-templates.library')
        ->middleware('can:manageDocumentTemplates');
    Route::get('/document-requests/{documentRequest}/download', [EmployeeDocumentDownloadController::class, 'generated'])
        ->name('admin.document-requests.download')
        ->can('download', 'documentRequest');
    Route::get('/document-requests/{documentRequest}/uploaded', [EmployeeDocumentDownloadController::class, 'uploaded'])
        ->name('admin.document-requests.uploaded')
        ->can('downloadUpload', 'documentRequest');

});
