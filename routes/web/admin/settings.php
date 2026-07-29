<?php

use App\Http\Controllers\Admin\OperationalHealthController;
use App\Models\SystemBackupRun;
use Illuminate\Support\Facades\Route;

Route::livewire('/settings', 'admin.settings')->name('admin.settings')->can('viewAdminSettings');
Route::livewire('/settings/kpi', 'admin.settings.kpi-settings')->name('admin.settings.kpi')->middleware('feature.lock:appraisal,admin.kpi_settings.manage,admin.dashboard')->can('manageKpiSettings');
Route::livewire('/companies', 'admin.company-manager')->name('admin.companies')->can('manageCompanies');
Route::get('/operational-health', OperationalHealthController::class)->name('admin.operational-health')
    ->middleware('feature.lock:operational_health,admin.system_maintenance.view,admin.dashboard')
    ->can('viewAny', SystemBackupRun::class);
Route::view('/profile', 'profile.admin-show')->name('admin.profile.show');
