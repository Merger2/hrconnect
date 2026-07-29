<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/activity-logs', 'admin.activity-logs')->name('admin.activity-logs')->middleware('feature.lock:audit,admin.activity_logs.view,admin.dashboard')->can('viewActivityLogs');
Route::redirect('/activity-logs/viewer', '/admin/activity-logs', 301)->name('admin.activity-logs.viewer')->can('view_activity_logs');
Route::livewire('/announcements', 'admin.announcement-manager')->name('admin.announcements')->can('manageAnnouncements');

Route::livewire('/user-sessions', 'admin.user-session-manager')
    ->name('admin.user-sessions')
    ->middleware('feature.lock:security,gate:manageUserSessions,admin.dashboard')
    ->can('manageUserSessions');

Route::livewire('/roles-permissions', 'admin.role-permission-manager')->name('admin.roles.permissions')->can('manageRbac');
