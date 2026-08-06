<?php

use App\Livewire\Admin\LeaveEntitlementManager;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Support\MultiCompanyService;
use Livewire\Livewire;

test('superadmin can assign annual leave entitlement with expiry', function () {
    $superadmin = User::factory()->admin(true)->create();
    $company = app(MultiCompanyService::class)->createCompany('PT Leave Entitlement');
    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
    ]);
    LeaveType::factory()->create([
        'is_active' => true,
        'deducts_from_quota' => true,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(LeaveEntitlementManager::class)
        ->set('userId', $user->id)
        ->set('year', now()->year)
        ->set('allocatedDays', '12')
        ->set('carriedOverDays', '2')
        ->set('expiresAt', now()->endOfYear()->toDateString())
        ->set('notes', 'Annual entitlement')
        ->call('save')
        ->assertHasNoErrors();

    // M11 (2026-08-06): entitlement kini tersimpan di leave_balances
    // (single source of truth; leave_entitlements legacy sudah di-drop).
    $balance = LeaveBalance::query()
        ->where('employee_id', $employee->id)
        ->firstOrFail();

    expect((float) $balance->quota)->toBe(12.0)
        ->and((float) $balance->carry_forward)->toBe(2.0)
        ->and((float) $balance->quota + (float) $balance->carry_forward)->toBe(14.0)
        ->and($balance->carry_forward_deadline?->toDateString())->toBe(now()->endOfYear()->toDateString());
});

test('tenant scoped admin cannot assign entitlement to another company employee', function () {
    $admin = User::factory()->admin()->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Leave A', $admin);
    $companyB = app(MultiCompanyService::class)->createCompany('PT Leave B');
    $userB = User::factory()->create([
        'company_id' => $companyB->id,
    ]);
    $employeeB = Employee::factory()->create([
        'user_id' => $userB->id,
        'company_id' => $companyB->id,
    ]);

    $role = Role::query()->create([
        'name' => 'Leave Entitlement Manager',
        'slug' => 'leave_entitlement_manager',
        'permission_keys' => ['admin.leave_entitlements.manage'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh());

    Livewire::test(LeaveEntitlementManager::class)
        ->set('userId', $userB->id)
        ->set('year', now()->year)
        ->set('allocatedDays', '12')
        ->set('expiresAt', now()->endOfYear()->toDateString())
        ->call('save')
        ->assertForbidden();

    expect(LeaveBalance::query()->where('employee_id', $employeeB->id)->exists())->toBeFalse()
        ->and($admin->fresh()->company_id)->toBe($companyA->id);
});

test('leave entitlement route requires explicit permission', function () {
    $admin = User::factory()->admin()->create();
    $admin->roles()->detach();

    $this->actingAs($admin)
        ->get(route('admin.masters.leave-entitlements'))
        ->assertForbidden();

    $role = Role::query()->create([
        'name' => 'Leave Entitlement Viewer',
        'slug' => 'leave_entitlement_viewer',
        'permission_keys' => ['admin.leave_entitlements.manage'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.masters.leave-entitlements'))
        ->assertOk();
});
