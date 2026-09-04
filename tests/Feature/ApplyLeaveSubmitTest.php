<?php

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a user with employee record, a supervisor, and an admin HR user
 * so that LeaveService::applyLeave() → ApprovalService::createApprovalWorkflow()
 * can find both L1 (supervisor) and L2 (admin) approvers.
 */
function applyLeaveEmployee(): array
{
    // Admin HR (L2 approver — User::role('admin') queries by slug='admin')
    $hrUser = User::factory()->admin()->create();
    Employee::factory()->create(['user_id' => $hrUser->id, 'full_name' => $hrUser->name]);
    $hrRole = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'Admin', 'permission_keys' => ['admin.leave_approvals.manage']]
    );
    $hrUser->roles()->sync([$hrRole->id]);

    // Supervisor (L1 approver)
    $supervisor = User::factory()->create();
    $supervisorEmployee = Employee::factory()->create(['user_id' => $supervisor->id, 'full_name' => $supervisor->name]);
    $supervisorRole = Role::create([
        'name' => 'Supervisor_'.uniqid(),
        'slug' => 'supervisor_'.uniqid(),
        'permission_keys' => ['review_subordinate_requests'],
    ]);
    $supervisor->roles()->sync([$supervisorRole->id]);

    // Employee under the supervisor
    $employee = User::factory()->create();
    $employeeRec = Employee::factory()->create([
        'user_id' => $employee->id,
        'parent_id' => $supervisorEmployee->id,
    ]);

    return [$employee, $employeeRec, $supervisor, $supervisorEmployee, $hrUser];
}

function seedExcusedLeaveType(): LeaveType
{
    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '0', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache();

    return LeaveType::create([
        'code' => 'excused',
        'name' => 'Izin',
        'category' => LeaveType::CATEGORY_ANNUAL,
        'is_paid' => true,
        'deducts_from_quota' => false,
        'counts_against_quota' => false,
        'is_active' => true,
    ]);
}

test('employee can submit leave request via web', function () {
    [$employee] = applyLeaveEmployee();
    $leaveType = seedExcusedLeaveType();

    $this->actingAs($employee)
        ->post('/apply-leave', [
            'status' => 'excused',
            'note' => 'Izin sakit karena demam tinggi',
            'from' => now()->addDays(5)->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $leave = Leave::query()->where('employee_id', $employee->employee->id)->first();
    expect($leave)->not->toBeNull()
        ->and($leave->status->value)->toBe('pending');

    // Verify L1 + L2 approval records were created
    expect($leave->approvals()->where('level', ApprovalLevel::L1_SUPERVISOR)->where('status', ApprovalStatus::PENDING)->count())->toBe(1)
        ->and($leave->approvals()->where('level', ApprovalLevel::L2_MANAGER)->where('status', ApprovalStatus::PENDING)->count())->toBe(1);
});

test('leave submission requires note and from date', function () {
    [$employee] = applyLeaveEmployee();

    $this->actingAs($employee)
        ->post('/apply-leave', [
            'status' => 'excused',
        ])
        ->assertSessionHasErrors(['note', 'from']);
});

test('leave submission requires attachment when policy enforces it', function () {
    [$employee] = applyLeaveEmployee();
    seedExcusedLeaveType();

    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '1', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache();

    $this->actingAs($employee)
        ->post('/apply-leave', [
            'status' => 'excused',
            'note' => 'Izin sakit karena demam tinggi',
            'from' => now()->addDays(5)->toDateString(),
        ])
        ->assertSessionHasErrors(['attachment']);
});

test('guest is redirected to login when submitting leave', function () {
    $this->from('/apply-leave')
        ->post('/apply-leave', [
            'status' => 'excused',
            'note' => 'Izin sakit',
            'from' => now()->toDateString(),
        ])
        ->assertRedirect('/login');
});
