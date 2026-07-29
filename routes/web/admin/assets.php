<?php

use App\Models\Appraisal;
use App\Models\CompanyAsset;

Route::livewire('/assets', 'admin.asset-manager')->name('admin.assets')->middleware('feature.lock:assets,admin.assets.view,admin.dashboard')->can('viewAdminAny', CompanyAsset::class);
Route::livewire('/appraisals', 'admin.appraisal-manager')->name('admin.appraisals')->middleware('feature.lock:appraisal,admin.appraisals.view,admin.dashboard')->can('viewAdminAny', Appraisal::class);
