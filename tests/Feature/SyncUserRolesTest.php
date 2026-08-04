<?php

use App\Actions\Hr\SyncUserRoles;
use App\Models\Role;
use App\Models\User;
use App\Support\RbacRegistry;
use Illuminate\Auth\Access\AuthorizationException;

function syncUserRoles(): SyncUserRoles
{
    return app(SyncUserRoles::class);
}

function makeAdminRole(string $slug = 'settings_only'): Role
{
    return Role::create([
        'name' => 'Settings Only_'.uniqid(),
        'slug' => $slug,
        'description' => 'Can only access admin settings.',
        'permission_keys' => ['admin.settings.view'],
    ]);
}

function cleanupTestRoles(): void
{
    // Remove the roles this file creates (settings_only/role_assigner/
    // full_admin). model_has_roles and role_has_permissions cascade on delete;
    // the seeded admin/super-admin roles are never touched.
    Role::query()
        ->whereIn('slug', ['settings_only', 'role_assigner', 'full_admin'])
        ->delete();
}

beforeEach(function () {
    // Tests run against the persistent dev DB (hris_payroll), so roles created
    // by previous runs survive and make Spatie's Role::create() throw
    // RoleAlreadyExists. Remove leftovers from prior runs.
    cleanupTestRoles();

    // RefreshDatabase-based files in this suite (e.g. ApprovalControllerFeatureTest)
    // run migrate:fresh on the shared dev DB, which wipes the seeded roles this
    // file depends on. Re-ensure the two default roles the implicit-default
    // tests require, idempotently, so this file is order-independent.
    Role::firstOrCreate(
        ['name' => 'admin', 'guard_name' => 'web'],
        ['slug' => 'admin', 'permission_keys' => RbacRegistry::permissionKeys()],
    );
    Role::firstOrCreate(
        ['name' => 'super-admin', 'guard_name' => 'web'],
        ['slug' => 'super-admin', 'permission_keys' => RbacRegistry::permissionKeys(), 'is_super_admin' => true],
    );
});

afterEach(function () {
    // Don't leak test roles into the shared dev DB for other test files that
    // run afterwards (Pest runs afterEach even when a test throws).
    cleanupTestRoles();
});

test('a superadmin can assign a role and the resolved state is returned', function () {
    $actor = User::factory()->admin(true)->create();
    $subject = User::factory()->admin()->create();
    $role = makeAdminRole();

    $result = syncUserRoles()->handle($subject, $actor, [$role->id], []);

    expect($result['changed'])->toBeTrue();
    expect($result['role_ids'])->toBe([$role->id]);
    expect($result['original_role_ids'])->toBe([$role->id]);
    expect($subject->fresh()->roles()->pluck('roles.id')->all())->toBe([$role->id]);
});

test('an unchanged role selection is a no-op', function () {
    $actor = User::factory()->admin(true)->create();
    $subject = User::factory()->admin()->create();
    $role = makeAdminRole();
    $subject->roles()->sync([$role->id]);

    $result = syncUserRoles()->handle($subject, $actor, [$role->id], [$role->id]);

    expect($result['changed'])->toBeFalse();
    expect($result['role_ids'])->toBe([$role->id]);
});

test('an invalid role id is rejected', function () {
    $actor = User::factory()->admin(true)->create();
    $subject = User::factory()->admin()->create();

    expect(fn () => syncUserRoles()->handle($subject, $actor, ['non-existent-id'], []))
        ->toThrow(AuthorizationException::class, __('One or more selected roles are invalid.'));
});

test('an actor without assign permission cannot assign roles', function () {
    $actor = User::factory()->create(); // plain employee, no admin permissions
    $subject = User::factory()->admin()->create();
    $role = makeAdminRole();

    expect(fn () => syncUserRoles()->handle($subject, $actor, [$role->id], []))
        ->toThrow(AuthorizationException::class, __('You do not have permission to assign roles.'));
});

test('an actor cannot change their own role assignment', function () {
    $actor = User::factory()->admin(true)->create();
    $role = makeAdminRole();

    expect(fn () => syncUserRoles()->handle($actor, $actor, [$role->id], []))
        ->toThrow(AuthorizationException::class, __('You cannot change your own role assignment.'));
});

test('assigning a full-admin role requires super admin management permission', function () {
    $assignerRole = Role::create([
        'name' => 'Role Assigner_'.uniqid(),
        'slug' => 'role_assigner_'.uniqid(),
        'description' => 'Can assign roles but not manage super admins.',
        // SyncUserRoles checks can('assignRoles') whose gate resolves to the
        // Permission enum value 'assign_roles' (not the RbacRegistry key
        // 'admin.rbac.assign').
        'permission_keys' => ['assign_roles'],
    ]);
    $actor = User::factory()->admin()->create();
    $actor->roles()->sync([$assignerRole->id]);

    $subject = User::factory()->admin()->create();
    $fullAdminRole = Role::create([
        'name' => 'Full Admin_'.uniqid(),
        'slug' => 'full_admin_'.uniqid(),
        'description' => 'Grants every permission.',
        'permission_keys' => RbacRegistry::permissionKeys(),
        'is_super_admin' => true,
    ]);

    expect(fn () => syncUserRoles()->handle($subject, $actor, [$fullAdminRole->id], []))
        ->toThrow(AuthorizationException::class, __('You do not have permission to assign the Super Admin role.'));
});

test('assigning a full-admin role promotes the subject group to superadmin', function () {
    $actor = User::factory()->admin(true)->create();
    $subject = User::factory()->admin()->create();
    $fullAdminRole = Role::create([
        'name' => 'Full Admin_'.uniqid(),
        'slug' => 'full_admin_'.uniqid(),
        'description' => 'Grants every permission.',
        'permission_keys' => RbacRegistry::permissionKeys(),
        'is_super_admin' => true,
    ]);

    $result = syncUserRoles()->handle($subject, $actor, [$fullAdminRole->id], []);

    expect($result['changed'])->toBeTrue();
    expect($result['group'])->toBe('superadmin');
    expect($subject->fresh()->group)->toBe('superadmin');
});

test('an admin with no explicit role receives the default admin role', function () {
    $actor = User::factory()->admin(true)->create();
    $subject = User::factory()->admin()->create();
    $defaultAdminRole = Role::query()->where('slug', 'admin')->firstOrFail();

    $result = syncUserRoles()->handle($subject, $actor, [], []);

    expect($result['changed'])->toBeTrue();
    expect($result['role_ids'])->toBe([$defaultAdminRole->id]);
    expect($subject->fresh()->roles()->pluck('roles.id')->all())->toBe([$defaultAdminRole->id]);
});
