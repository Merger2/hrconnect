<?php

use App\Http\Controllers\Admin\Collaboration\DownloadCloudFileController;
use Illuminate\Support\Facades\Route;

Route::middleware('feature.lock:operations,viewOperationsWorkspace,admin.dashboard')->group(function () {

    Route::livewire('/operations', 'admin.operational-workspace')
        ->name('admin.operations')
        ->can('viewOperationsWorkspace');

    Route::livewire('/collaboration', 'admin.collaboration-workspace')
        ->name('admin.collaboration')
        ->can('viewCollaborationWorkspace');

    Route::get('/collaboration/files/{file}/download', DownloadCloudFileController::class)
        ->name('admin.collaboration.files.download')
        ->can('download', 'file');

    Route::livewire('/custom-forms', 'admin.custom-form-manager')
        ->name('admin.custom-forms')
        ->can('viewCustomForms');

    Route::livewire('/announcements', 'admin.announcement-manager')
        ->name('admin.announcements')
        ->can('manageAnnouncements');

});
