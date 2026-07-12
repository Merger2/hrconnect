<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings/security', '/settings/profile#security')->name('security.edit');
    Route::redirect('settings/2fa', '/settings/profile#security');

    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
});

// IT Support Monitoring Dashboard
Route::middleware(['auth', 'verified', 'can:view_dashboard'])->group(function () {
    Route::livewire('monitoring', 'it-support.monitoring-dashboard')->name('monitoring');
});
