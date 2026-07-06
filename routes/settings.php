<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::permanentRedirect('settings/security', 'settings/profile#security')->name('security.edit');
    Route::permanentRedirect('settings/2fa', 'settings/profile#security');

    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
});
