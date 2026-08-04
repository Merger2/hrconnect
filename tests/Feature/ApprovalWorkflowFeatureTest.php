<?php

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

/**
 * Approval Service Unit Tests
 */
test('createApprovalWorkflow creates L1 and L2 when employee has supervisor', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $supervisor = Employee::factory()->create();
    $employee = Employee::factory()->create(['parent_id' => $supervisor->id]);

    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $service = new ApprovalService;
    $service->createApprovalWorkflow($leave);

    $approvals = $leave->approvals()->get();
    expect($approvals)->toHaveCount(2);
    expect($approvals[0]->level)->toBe(ApprovalLevel::L1_SUPERVISOR);
    expect($approvals[0]->approver_id)->toBe($supervisor->id);
    expect($approvals[1]->level)->toBe(ApprovalLevel::L2_MANAGER);
    expect($approvals[1]->approver_id)->toBe($hrEmployee->id);
});

test('createApprovalWorkflow creates only L2 when employee has no supervisor', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create(['parent_id' => null]);
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $service = new ApprovalService;
    $service->createApprovalWorkflow($leave);

    $approvals = $leave->approvals()->get();
    expect($approvals)->toHaveCount(1);
    expect($approvals[0]->level)->toBe(ApprovalLevel::L2_MANAGER);
});

test('approve marks approval as approved and sets approved_at', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $employee = Employee::factory()->create();
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

    $service = new ApprovalService;
    $service->approve($approval);

    $approval->refresh();
    expect($approval->status)->toBe(ApprovalStatus::APPROVED);
    expect($approval->approved_at)->not->toBeNull();
});

test('approve sets request to APPROVED when all approvals complete', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $employee = Employee::factory()->create();
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

    $service = new ApprovalService;
    $service->approve($approval);

    $leave->refresh();
    expect($leave->status)->toBe(RequestStatus::APPROVED);
});

test('approve sets APPROVED_L1 when L1 approved but L2 pending', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $supervisor = Employee::factory()->create();
    $employee = Employee::factory()->create(['parent_id' => $supervisor->id]);
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::PENDING,
    ]);

    $service = new ApprovalService;
    $service->createApprovalWorkflow($leave);

    $l1 = $leave->approvals()->where('level', ApprovalLevel::L1_SUPERVISOR)->first();
    $service->approve($l1);

    $leave->refresh();
    expect($leave->status)->toBe(RequestStatus::APPROVED_L1);
});

test('approve throws exception when trying to approve L2 before L1', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $supervisor = Employee::factory()->create();
    $employee = Employee::factory()->create(['parent_id' => $supervisor->id]);
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::PENDING,
    ]);
    $leave->approvals()->create([
        'approver_id' => $supervisor->id,
        'level' => ApprovalLevel::L1_SUPERVISOR,
        'status' => ApprovalStatus::PENDING,
    ]);
    $l2 = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $service = new ApprovalService;
    expect(fn () => $service->approve($l2))->toThrow(BusinessRuleException::class);
    expect($l2->fresh()->status)->toBe(ApprovalStatus::PENDING);
});

test('approve deducts leave quota when fully approved and leave deducts quota', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => true, 'quota' => 12]);
    $employee = Employee::factory()->create();
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

    $service = new ApprovalService;
    $service->approve($approval);

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', now()->year)
        ->first();
    expect((float) $balance->used)->toBe(2.0);
});

test('reject sets approval to REJECTED with notes and approvable to REJECTED', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $employee = Employee::factory()->create();
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

    $service = new ApprovalService;
    $service->reject($approval, 'Ditolak karena alasan tertentu');

    $approval->refresh();
    $leave->refresh();
    expect($approval->status)->toBe(ApprovalStatus::REJECTED);
    expect($approval->notes)->toBe('Ditolak karena alasan tertentu');
    expect($leave->status)->toBe(RequestStatus::REJECTED);
    expect($leave->rejection_reason)->toBe('Ditolak karena alasan tertentu');
});

test('reject prevents further approvals on same request', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
    $hr->assignRole('hr-manager');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $employee = Employee::factory()->create();
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

    $service = new ApprovalService;
    $service->reject($approval, 'Ditolak');

    // Try to approve after rejection
    expect(fn () => $service->approve($approval))->toThrow(BusinessRuleException::class);
});
