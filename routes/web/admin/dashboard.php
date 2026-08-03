<?php

use App\Http\Controllers\Admin\AdminRootRedirectController;
use Illuminate\Support\Facades\Route;

// The 'admin' middleware group already gates this route to admins. The
// controller itself falls back to the first page the signed-in admin is
// actually permitted to open, so role-scoped admins (e.g. notifications
// only) are redirected instead of landing on a 403.
Route::get('/', AdminRootRedirectController::class);

Route::livewire('/dashboard', 'admin.dashboard-component')->name('admin.dashboard')->middleware('can:viewAdminDashboard');

Route::livewire('/command-center', 'admin.command-center')->name('admin.command-center')->can('viewCommandCenter');
Route::livewire('/inbox', 'admin.manager-inbox')->name('admin.inbox')->can('accessAdminPanel');
Route::livewire('/notifications', 'admin.notifications-page')->name('admin.notifications')->can('manageAdminNotifications');
Route::livewire('/analytics', 'admin.analytics-dashboard')
    ->name('admin.analytics')
    ->middleware('feature.lock:analytics,admin.analytics.view,admin.dashboard')
    ->can('viewAnalyticsDashboard');
