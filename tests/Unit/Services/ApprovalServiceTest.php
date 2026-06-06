<?php

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ApprovalService;
});

// ─── helpers ───────────────────────────────────────────────────────────

function approval_infra(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $department = Department::factory()->create([
        'branch_id' => $branch->id,
        'code' => 'DEPT_'.uniqid(),
    ]);
    $position = Position::factory()->create(['department_id' => $department->id]);

    return [$company, $branch, $department, $position];
}

function approval_emp(array $overrides = []): Employee
{
    [$company, $branch, $department, $position] = approval_infra();
    $user = User::factory()->create();

    return Employee::factory()->create(array_merge([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'parent_id' => null,
        'join_date' => now()->subYear()->toDateString(),
        'employment_type' => 'permanent',
        'status' => 'active',
    ], $overrides));
}

function approval_hr(): Employee
{
    $role = Role::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return approval_emp(['user_id' => $user->id]);
}

function approval_leave_with_single_approval(): array
{
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $employee = approval_emp();
    $hrEmployee = approval_hr();

    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::PENDING,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    return [$approval, $leave];
}

// ─── createApprovalWorkflow ────────────────────────────────────────────

test('createApprovalWorkflow creates L1 and L2 when employee has direct supervisor', function () {
    $hrEmployee = approval_hr();
    $supervisor = approval_emp();
    $employee = approval_emp(['parent_id' => $supervisor->id]);

    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $this->service->createApprovalWorkflow($leave);

    $approvals = $leave->approvals()->get();

    expect($approvals)->toHaveCount(2);
    expect($approvals[0]->level)->toBe(ApprovalLevel::L1_SUPERVISOR);
    expect($approvals[0]->approver_id)->toBe($supervisor->id);
    expect($approvals[0]->status)->toBe(ApprovalStatus::PENDING);
    expect($approvals[1]->level)->toBe(ApprovalLevel::L2_MANAGER);
    expect($approvals[1]->approver_id)->toBe($hrEmployee->id);
    expect($approvals[1]->status)->toBe(ApprovalStatus::PENDING);
});

test('createApprovalWorkflow creates only L2 when employee has no parent_id', function () {
    $hrEmployee = approval_hr();
    $employee = approval_emp();

    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $this->service->createApprovalWorkflow($leave);

    $approvals = $leave->approvals()->get();

    expect($approvals)->toHaveCount(1);
    expect($approvals[0]->level)->toBe(ApprovalLevel::L2_MANAGER);
    expect($approvals[0]->approver_id)->toBe($hrEmployee->id);
});

test('createApprovalWorkflow throws LogicException when no approver available', function () {
    // Role must exist for User::role() scope, but no user is assigned it
    Role::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $employee = approval_emp();

    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    expect(fn () => $this->service->createApprovalWorkflow($leave))
        ->toThrow(LogicException::class, 'Tidak ada Approver');
});

// ─── approve ───────────────────────────────────────────────────────────

test('approve marks single approval as APPROVED', function () {
    [$approval, $leave] = approval_leave_with_single_approval();

    $this->service->approve($approval);

    $approval->refresh();
    expect($approval->status)->toBe(ApprovalStatus::APPROVED);
    expect($approval->approved_at)->not->toBeNull();
});

test('approve sets approvable to APPROVED when isAllApproved returns true', function () {
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $employee = approval_emp();
    $hrEmployee = approval_hr();

    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::PENDING,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $this->service->approve($approval);

    $leave->refresh();
    expect($leave->status)->toBe(RequestStatus::APPROVED);
});

test('approve sets APPROVED_L1 when level is L1 and not fully approved', function () {
    $hrEmployee = approval_hr();
    $supervisor = approval_emp();
    $employee = approval_emp(['parent_id' => $supervisor->id]);
    $leaveType = LeaveType::factory()->create();

    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::PENDING,
    ]);
    $this->service->createApprovalWorkflow($leave);

    $l1 = $leave->approvals()->where('level', ApprovalLevel::L1_SUPERVISOR)->first();

    $this->service->approve($l1);

    $leave->refresh();
    expect($leave->status)->toBe(RequestStatus::APPROVED_L1);
});

test('approve deducts leave quota when fully approved and leave deducts quota', function () {
    $leaveType = LeaveType::factory()->create([
        'deducts_from_quota' => true,
        'quota' => 12,
    ]);
    $employee = approval_emp();
    $hrEmployee = approval_hr();

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => now()->year,
        'quota' => 12,
        'used' => 0,
        'carry_forward' => 0,
    ]);

    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'total_days' => 2,
        'status' => RequestStatus::PENDING,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $this->service->approve($approval);

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', now()->year)
        ->first();
    expect((float) $balance->used)->toBe(2.0);
});

// ─── reject ────────────────────────────────────────────────────────────

test('reject sets approval to REJECTED and approvable to REJECTED with rejection_reason', function () {
    [$approval, $leave] = approval_leave_with_single_approval();

    $this->service->reject($approval, 'Ditolak karena alasan tertentu');

    $approval->refresh();
    $leave->refresh();

    expect($approval->status)->toBe(ApprovalStatus::REJECTED);
    expect($approval->notes)->toBe('Ditolak karena alasan tertentu');
    expect($leave->status)->toBe(RequestStatus::REJECTED);
    expect($leave->rejection_reason)->toBe('Ditolak karena alasan tertentu');
});
