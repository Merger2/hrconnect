<?php

use Illuminate\Support\Facades\Route;

Route::middleware('feature.lock:master_data,manageDivisions,admin.dashboard')->group(function () {

    Route::livewire('/masterdata/division', 'admin.master-data.division-component')->name('admin.masters.division')->can('manageDivisions');
    Route::livewire('/masterdata/job-title', 'admin.master-data.job-title-component')->name('admin.masters.job-title')->can('manageJobTitles');
    Route::livewire('/masterdata/education', 'admin.master-data.education-component')->name('admin.masters.education')->can('manageEducations');
    Route::livewire('/masterdata/shift', 'admin.master-data.shift-component')->name('admin.masters.shift')->can('manageShifts');
    Route::livewire('/masterdata/leave-types', 'admin.master-data.leave-type-manager')->name('admin.masters.leave-types')->can('manageLeaveTypes');
    Route::livewire('/masterdata/leave-entitlements', 'admin.leave-entitlement-manager')->name('admin.masters.leave-entitlements')->can('manageLeaveEntitlements');
    Route::livewire('/masterdata/admin', 'admin.master-data.admin')->name('admin.masters.admin')->can('viewAdminAccounts');
    Route::livewire('/holidays', 'admin.holiday-manager')->name('admin.holidays')->can('manageHolidays');

});
