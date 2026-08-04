<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('non-admin user prefers the home route', function () {
    $user = User::factory()->create();

    expect($user->preferredAdminRouteName())->toBeNull();
    expect($user->preferredHomeRouteName())->toBe('home');
});

test('superadmin prefers the admin dashboard route', function () {
    $superadmin = User::factory()->admin(true)->create();

    expect($superadmin->preferredAdminRouteName())->toBe('admin.dashboard');
    expect(Route::has($superadmin->preferredHomeRouteName()))->toBeTrue();
});

test('admin lands on the first route their abilities allow', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Settings Only_'.uniqid(),
        'slug' => 'settings_only_'.uniqid(),
        'description' => 'Can only access admin settings.',
        'permission_keys' => ['admin.settings.view'],
    ]);
    $admin->roles()->sync([$role->id]);

    expect($admin->preferredAdminRouteName())->toBe('admin.settings');
});
