<?php

use App\Http\Controllers\Admin\AdminRootRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', AdminRootRedirectController::class)
    ->can('accessAdminPanel');

Route::livewire('/dashboard', 'admin.dashboard-component')->name('admin.dashboard')->middleware('can:viewAdminDashboard');

Route::livewire('/command-center', 'admin.command-center')->name('admin.command-center')->can('viewCommandCenter');
Route::livewire('/inbox', 'admin.manager-inbox')->name('admin.inbox')->can('accessAdminPanel');
Route::livewire('/notifications', 'admin.notifications-page')->name('admin.notifications')->can('manageAdminNotifications');
Route::livewire('/analytics', 'admin.analytics-dashboard')
    ->name('admin.analytics')
    ->middleware('feature.lock:analytics,admin.analytics.view,admin.dashboard')
    ->can('viewAnalyticsDashboard');
