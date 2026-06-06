<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('SuperAdminSeeder creates super-admin user with default credentials', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SuperAdminSeeder::class);

    $admin = User::where('email', 'admin@hrconnect.local')->first();

    expect($admin)->not->toBeNull();
    expect($admin->hasRole('super-admin'))->toBeTrue();
    expect(Hash::check('ChangeMe!2026', $admin->password))->toBeTrue();
    expect($admin->email_verified_at)->not->toBeNull();
});

test('SuperAdminSeeder honor env credentials', function () {
    config(['app.test' => 'placeholder']); // tidak relevan, sekedar sentuh config
    putenv('SUPER_ADMIN_EMAIL=custom@example.com');
    putenv('SUPER_ADMIN_PASSWORD=SuperSecret123!');
    putenv('SUPER_ADMIN_NAME=Custom Boss');

    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SuperAdminSeeder::class);

    $admin = User::where('email', 'custom@example.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->name)->toBe('Custom Boss');
    expect(Hash::check('SuperSecret123!', $admin->password))->toBeTrue();
    expect($admin->hasRole('super-admin'))->toBeTrue();

    // Cleanup
    putenv('SUPER_ADMIN_EMAIL');
    putenv('SUPER_ADMIN_PASSWORD');
    putenv('SUPER_ADMIN_NAME');
});

test('SuperAdminSeeder idempotent', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SuperAdminSeeder::class);
    $this->seed(SuperAdminSeeder::class);

    expect(User::where('email', 'admin@hrconnect.local')->count())->toBe(1);
});

test('super-admin user can() check works (44 permissions)', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SuperAdminSeeder::class);

    $admin = User::where('email', 'admin@hrconnect.local')->first();

    expect($admin->can('manage_employees'))->toBeTrue();
    expect($admin->can('process_payroll'))->toBeTrue();
    expect($admin->can('manage_companies'))->toBeTrue();
    expect($admin->can('approve_wfa'))->toBeTrue();
    expect($admin->can('view_dashboard'))->toBeTrue();
});
