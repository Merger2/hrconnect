<?php

use App\Enums\Permission as PermissionEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
});

test('44 permissions di-seed sesuai SRS', function () {
    expect(PermissionEnum::count())->toBe(44);
    expect(Permission::count())->toBe(44);
});

test('5 roles di-seed sesuai SRS', function () {
    $roles = Role::pluck('name')->toArray();

    expect($roles)
        ->toContain('super-admin')
        ->toContain('hr-manager')
        ->toContain('finance')
        ->toContain('manager')
        ->toContain('employee');
    expect(count($roles))->toBe(5);
});

test('super-admin punya semua 44 permission', function () {
    $role = Role::where('name', 'super-admin')->first();

    expect($role->permissions->count())->toBe(44);
});

test('employee role hanya punya view permissions + dashboard', function () {
    $role = Role::where('name', 'employee')->first();
    $permissions = $role->permissions->pluck('name')->toArray();

    expect($permissions)->toContain('view_dashboard');
    expect($permissions)->toContain('view_attendances');
    expect($permissions)->toContain('view_payslip');
    // Tidak punya manage permissions
    expect($permissions)->not->toContain('manage_employees');
    expect($permissions)->not->toContain('process_payroll');
    expect($permissions)->not->toContain('approve_leaves_l1');
});

test('manager dapat approve L1 leaves dan WFA', function () {
    $role = Role::where('name', 'manager')->first();
    $permissions = $role->permissions->pluck('name')->toArray();

    expect($permissions)->toContain('approve_leaves_l1');
    expect($permissions)->toContain('approve_overtimes_l1');
    expect($permissions)->toContain('approve_reimbursements_l1');
    expect($permissions)->toContain('approve_wfa');
    // Tidak punya L2
    expect($permissions)->not->toContain('approve_leaves_l2');
});

test('hr-manager dapat approve L2 leaves & overtimes (bukan reimbursement)', function () {
    $role = Role::where('name', 'hr-manager')->first();
    $permissions = $role->permissions->pluck('name')->toArray();

    expect($permissions)->toContain('approve_leaves_l2');
    expect($permissions)->toContain('approve_overtimes_l2');
    expect($permissions)->toContain('manage_employees');
    expect($permissions)->toContain('manage_knowledgebase');
    // L2 reimbursement adalah Finance, bukan HR
    expect($permissions)->not->toContain('approve_reimbursements_l2');
    expect($permissions)->not->toContain('process_payroll');
});

test('finance dapat process payroll + approve L2 reimbursement + manage tax/bpjs', function () {
    $role = Role::where('name', 'finance')->first();
    $permissions = $role->permissions->pluck('name')->toArray();

    expect($permissions)->toContain('process_payroll');
    expect($permissions)->toContain('approve_reimbursements_l2');
    expect($permissions)->toContain('manage_tax_configs');
    expect($permissions)->toContain('manage_bpjs_configs');
    expect($permissions)->toContain('manage_loans');
    // Bukan urusan Finance
    expect($permissions)->not->toContain('manage_employees');
    expect($permissions)->not->toContain('approve_leaves_l2');
});

test('user can() check works after assigning role', function () {
    $user = User::factory()->create();
    $user->assignRole('hr-manager');

    expect($user->can('manage_employees'))->toBeTrue();
    expect($user->can('approve_leaves_l2'))->toBeTrue();
    expect($user->can('process_payroll'))->toBeFalse();
    expect($user->can('view_dashboard'))->toBeTrue();
});

test('seeder idempotent — jalan dua kali tidak duplicate', function () {
    $countBefore = Permission::count();
    $rolesBefore = Role::count();

    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    expect(Permission::count())->toBe($countBefore);
    expect(Role::count())->toBe($rolesBefore);
});
