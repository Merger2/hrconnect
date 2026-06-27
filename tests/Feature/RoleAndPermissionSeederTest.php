<?php

use App\Enums\Permission as PermissionEnum;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
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

test('finance dapat process payroll + approve L2 reimbursement + manage reimbursements + tax/bpjs', function () {
    $role = Role::where('name', 'finance')->first();
    $permissions = $role->permissions->pluck('name')->toArray();

    expect($permissions)->toContain('process_payroll');
    expect($permissions)->toContain('approve_reimbursements_l2');
    expect($permissions)->toContain('manage_reimbursements');
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

    $this->seed(RoleAndPermissionSeeder::class);

    expect(Permission::count())->toBe($countBefore);
    expect(Role::count())->toBe($rolesBefore);
});

// ─── Full Permission × Role Matrix ─────────────────────────

function permissionMatrix(): Generator
{
    $roles = ['super-admin', 'hr-manager', 'finance', 'manager', 'employee'];

    // super-admin: ALL 44 permissions
    foreach (PermissionEnum::cases() as $perm) {
        yield "super-admin can {$perm->value}" => ['super-admin', $perm->value, true];
    }

    // hr-manager
    $hrCan = [
        'view_dashboard', 'view_branches', 'view_departments', 'view_positions',
        'view_employees', 'manage_employees', 'view_attendances', 'manage_attendances',
        'view_leaves', 'approve_leaves_l2', 'view_overtimes', 'approve_overtimes_l2',
        'view_reimbursements', 'view_loans', 'view_assets', 'manage_assets',
        'view_activity_logs', 'view_audit_logs',
        'manage_knowledgebase', 'view_knowledgebase', 'view_wfa_pending',
        'approve_wfa',
    ];
    $hrCannot = [
        'view_companies', 'manage_companies', 'manage_branches', 'manage_departments',
        'manage_positions', 'approve_leaves_l1', 'approve_overtimes_l1',
        'manage_reimbursements', 'approve_reimbursements_l1', 'approve_reimbursements_l2',
        'manage_loans', 'process_payroll', 'view_payslip', 'download_payslip', 'view_payrolls',
        'manage_tax_configs', 'manage_bpjs_configs', 'manage_settings',
        'manage_company_settings', 'manage_roles', 'manage_holidays', 'manage_shifts',
    ];
    foreach ($hrCan as $p) {
        yield "hr-manager can {$p}" => ['hr-manager', $p, true];
    }
    foreach ($hrCannot as $p) {
        yield "hr-manager cannot {$p}" => ['hr-manager', $p, false];
    }

    // finance
    $finCan = [
        'view_dashboard', 'view_employees', 'view_attendances',
        'view_reimbursements', 'manage_reimbursements',
        'approve_reimbursements_l2',
        'view_loans', 'manage_loans',
        'view_payslip', 'download_payslip', 'process_payroll', 'view_payrolls',
        'manage_tax_configs', 'manage_bpjs_configs',
    ];
    $finCannot = [
        'manage_companies', 'view_companies', 'manage_branches', 'view_branches',
        'manage_departments', 'view_departments', 'manage_positions', 'view_positions',
        'manage_employees', 'manage_attendances',
        'view_leaves', 'approve_leaves_l1', 'approve_leaves_l2',
        'view_overtimes', 'approve_overtimes_l1', 'approve_overtimes_l2',
        'view_assets', 'manage_assets',
        'view_activity_logs', 'view_audit_logs',
        'manage_settings', 'manage_company_settings', 'manage_roles',
        'manage_holidays', 'manage_shifts', 'manage_knowledgebase', 'view_knowledgebase',
        'approve_wfa', 'view_wfa_pending',
    ];
    foreach ($finCan as $p) {
        yield "finance can {$p}" => ['finance', $p, true];
    }
    foreach ($finCannot as $p) {
        yield "finance cannot {$p}" => ['finance', $p, false];
    }

    // manager
    $mgrCan = [
        'view_dashboard', 'view_employees', 'view_attendances', 'view_leaves',
        'approve_leaves_l1', 'view_overtimes', 'approve_overtimes_l1',
        'view_reimbursements', 'approve_reimbursements_l1',
        'approve_wfa', 'view_wfa_pending',
    ];
    $mgrCannot = [
        'manage_companies', 'view_companies', 'manage_branches', 'view_branches',
        'manage_departments', 'view_departments', 'manage_positions', 'view_positions',
        'manage_employees', 'manage_attendances',
        'approve_leaves_l2', 'approve_overtimes_l2',
        'manage_reimbursements', 'approve_reimbursements_l2',
        'view_loans', 'manage_loans', 'view_assets', 'manage_assets',
        'view_payslip', 'download_payslip', 'process_payroll', 'view_payrolls',
        'manage_tax_configs', 'manage_bpjs_configs',
        'view_activity_logs', 'view_audit_logs',
        'manage_settings', 'manage_company_settings', 'manage_roles',
        'manage_holidays', 'manage_shifts', 'manage_knowledgebase', 'view_knowledgebase',
    ];
    foreach ($mgrCan as $p) {
        yield "manager can {$p}" => ['manager', $p, true];
    }
    foreach ($mgrCannot as $p) {
        yield "manager cannot {$p}" => ['manager', $p, false];
    }

    // employee
    $empCan = [
        'view_dashboard', 'view_attendances', 'view_leaves', 'view_overtimes',
        'view_reimbursements', 'view_loans', 'view_assets',
        'view_payslip', 'download_payslip', 'view_payrolls',
        'view_knowledgebase',
    ];
    $empCannot = [
        'view_companies', 'manage_companies', 'view_branches', 'manage_branches',
        'view_departments', 'manage_departments', 'view_positions', 'manage_positions',
        'view_employees', 'manage_employees', 'manage_attendances',
        'approve_leaves_l1', 'approve_leaves_l2', 'approve_overtimes_l1', 'approve_overtimes_l2',
        'manage_reimbursements', 'approve_reimbursements_l1', 'approve_reimbursements_l2',
        'manage_loans', 'manage_assets',
        'process_payroll', 'manage_tax_configs', 'manage_bpjs_configs',
        'view_activity_logs', 'view_audit_logs',
        'manage_settings', 'manage_company_settings', 'manage_roles',
        'manage_holidays', 'manage_shifts', 'manage_knowledgebase',
        'approve_wfa', 'view_wfa_pending',
    ];
    foreach ($empCan as $p) {
        yield "employee can {$p}" => ['employee', $p, true];
    }
    foreach ($empCannot as $p) {
        yield "employee cannot {$p}" => ['employee', $p, false];
    }
}

it('validates full permission x role matrix', function (string $roleName, string $permission, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($roleName);

    if ($expected) {
        expect($user->can($permission))->toBeTrue("Role {$roleName} should be able to {$permission}");
    } else {
        expect($user->can($permission))->toBeFalse("Role {$roleName} should NOT be able to {$permission}");
    }
})->with(permissionMatrix());
