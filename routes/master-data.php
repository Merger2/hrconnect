<?php

use App\Livewire\MasterData\BranchComponent;
use App\Livewire\MasterData\DepartmentComponent;
use App\Livewire\MasterData\PositionComponent;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->group(function () {
    Route::get('/master-data/branches', BranchComponent::class)
        ->middleware('can:view_branches')
        ->name('master-data.branches');

    Route::get('/master-data/departments', DepartmentComponent::class)
        ->middleware('can:view_departments')
        ->name('master-data.departments');

    Route::get('/master-data/positions', PositionComponent::class)
        ->middleware('can:view_positions')
        ->name('master-data.positions');
});
