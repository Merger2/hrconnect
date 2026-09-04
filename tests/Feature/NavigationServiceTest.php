<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use App\Services\Support\NavigationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function navRoleUser(string $roleName, array $permissionKeys): User
{
    $user = User::factory()->create();

    $role = Role::create([
        'name' => $roleName,
        'slug' => $roleName.'_'.uniqid(),
        'guard_name' => 'web',
        'permission_keys' => $permissionKeys,
    ]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('navigation service returns empty menu for unknown role', function () {
    $user = navRoleUser('unknown-role', []);

    expect(app(NavigationService::class)->build($user))->toBe([]);
});

test('navigation service filters items by user permissions', function () {
    $user = navRoleUser('employee', ['view_dashboard']);

    $menu = app(NavigationService::class)->build($user);

    // Employee punya akses Utama (Dashboard) tapi hanya item yang lolos `can`.
    expect($menu)->not->toBeEmpty()
        ->and($menu[0]['title'])->toBe('Utama')
        ->and($menu[0]['items'][0]['route'])->toBe('home');

    // Semua item lain (SDM/Absensi dst) tidak tampil tanpa permission.
    $routes = collect($menu)->flatMap(fn ($group) => array_column($group['items'], 'route'))->all();
    expect($routes)->toBe(['home']);
});

test('navigation service renders manager label_for override', function () {
    // Direktori Karyawan butuh lolos EmployeePolicy::viewAny (view_employees).
    $user = navRoleUser('manager', ['view_dashboard', 'view_attendances', 'view_employees']);

    $menu = app(NavigationService::class)->build($user);

    $sdm = collect($menu)->firstWhere('title', 'SDM');

    expect($sdm)->not->toBeNull();

    $labels = array_column($sdm['items'], 'label');
    expect($labels)->toContain('Anggota Tim') // label_for manager override
        ->and($labels)->toContain('Absensi');
});

test('navigation service includes group when at least one item is authorized', function () {
    $user = navRoleUser('admin', ['view_dashboard', 'view_attendances']);

    $menu = app(NavigationService::class)->build($user);

    $titles = array_column($menu, 'title');

    expect($titles)->toContain('Utama')
        ->and($titles)->toContain('SDM')
        // Keuangan butuh view_assets/view_payslip — tanpa itu tidak tampil.
        ->not->toContain('Keuangan');
});

test('navigation service does not depend on legacy hr role slug', function () {
    $admin = navRoleUser('admin', ['view_dashboard', 'view_leaves', 'manage_leave_approvals']);
    $legacyHr = navRoleUser('hr', ['view_dashboard', 'view_leaves', 'manage_leave_approvals']);

    $adminRoutes = collect(app(NavigationService::class)->build($admin))
        ->flatMap(fn ($group) => array_column($group['items'], 'route'))
        ->all();
    $legacyHrRoutes = collect(app(NavigationService::class)->build($legacyHr))
        ->flatMap(fn ($group) => array_column($group['items'], 'route'))
        ->all();

    expect($adminRoutes)->toContain('admin.leaves')
        ->and($adminRoutes)->toContain('approvals')
        ->and($legacyHrRoutes)->not->toContain('admin.leaves');
});

test('navigation service returns empty for non-user authenticatable', function () {
    $guest = $this->createMock(Authenticatable::class);

    expect(app(NavigationService::class)->build($guest))->toBe([]);
});
