<?php

use App\Livewire\MasterData\BranchComponent;
use App\Livewire\MasterData\BranchForm;
use App\Livewire\MasterData\DepartmentComponent;
use App\Livewire\MasterData\HolidayComponent;
use App\Livewire\MasterData\LeaveTypeComponent;
use App\Livewire\MasterData\PositionComponent;
use App\Livewire\MasterData\ShiftComponent;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->group(function () {
    Route::get('/master-data/branches', BranchComponent::class)
        ->middleware('can:view_branches')
        ->name('master-data.branches');

    Route::get('/master-data/branches/create', BranchForm::class)
        ->middleware('can:manage_branches')
        ->name('master-data.branches.create');

    Route::get('/master-data/branches/{branch}/edit', BranchForm::class)
        ->middleware('can:manage_branches')
        ->name('master-data.branches.edit');

    Route::get('/master-data/departments', DepartmentComponent::class)
        ->middleware('can:view_departments')
        ->name('master-data.departments');

    Route::get('/master-data/positions', PositionComponent::class)
        ->middleware('can:view_positions')
        ->name('master-data.positions');

    Route::get('/master-data/shifts', ShiftComponent::class)
        ->middleware('can:view_branches')
        ->name('master-data.shifts');

    Route::get('/master-data/holidays', HolidayComponent::class)
        ->middleware('can:view_branches')
        ->name('master-data.holidays');

    Route::get('/master-data/leave-types', LeaveTypeComponent::class)
        ->middleware('can:view_branches')
        ->name('master-data.leave-types');
});
