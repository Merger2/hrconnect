<?php

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Models\ApprovalMatrixRule;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use App\Support\ApprovalMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

/**
 * Approval Controller API Tests (Permission Enforcement)
 */
test('approvals pending endpoint requires authenticated user with employee', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/approvals')
        ->assertStatus(404); // Akun tanpa employee → error 404 dari controller
});

test('approvals pending returns pending approvals for approver', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);
    $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $response = $this->actingAs($hr)
        ->getJson('/api/v1/approvals');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data');
});

test('approvals pending filters by type', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);
    $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $overtime = Overtime::factory()->create(['employee_id' => $employee->id]);
    $overtime->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $response = $this->actingAs($hr)
        ->getJson('/api/v1/approvals?type=leave');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.approvable_type', 'Leave');
});

test('approve endpoint enforces approver ownership (403 for non-approver)', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $otherUser = User::factory()->create();
    Employee::factory()->create(['user_id' => $otherUser->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $this->actingAs($otherUser)
        ->putJson("/api/v1/approvals/{$approval->id}/approve", ['notes' => 'test'])
        ->assertForbidden();
});

test('approve endpoint rejects already-processed approval (409)', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::APPROVED,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::APPROVED,
        'approved_at' => now(),
    ]);

    $this->actingAs($hr)
        ->putJson("/api/v1/approvals/{$approval->id}/approve", ['notes' => 'test'])
        ->assertStatus(409);
});

test('approve endpoint successfully approves pending approval', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
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

    $response = $this->actingAs($hr)
        ->putJson("/api/v1/approvals/{$approval->id}/approve", ['notes' => 'Approved']);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'approved');
    expect($approval->fresh()->status)->toBe(ApprovalStatus::APPROVED);
});

test('reject endpoint enforces approver ownership (403 for non-approver)', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $otherUser = User::factory()->create();
    Employee::factory()->create(['user_id' => $otherUser->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $this->actingAs($otherUser)
        ->putJson("/api/v1/approvals/{$approval->id}/reject", ['rejection_reason' => 'Invalid reason test'])
        ->assertForbidden();
});

test('reject endpoint requires minimum 10 chars reason (422)', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);
    $approval = $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $this->actingAs($hr)
        ->putJson("/api/v1/approvals/{$approval->id}/reject", ['rejection_reason' => 'short'])
        ->assertStatus(422);
});

test('reject endpoint successfully rejects pending approval', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => false]);
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

    $response = $this->actingAs($hr)
        ->putJson("/api/v1/approvals/{$approval->id}/reject", ['rejection_reason' => 'Alasan penolakan yang cukup panjang']);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'rejected');
    expect($approval->fresh()->status)->toBe(ApprovalStatus::REJECTED);
    expect($leave->fresh()->status)->toBe(RequestStatus::REJECTED);
});

test('approvals history returns processed approvals only', function () {
    $hr = User::factory()->create();
    SpatieRole::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $hr->assignRole('admin');
    $hrEmployee = Employee::factory()->create(['user_id' => $hr->id]);

    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    // Approved approval
    $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::APPROVED,
        'approved_at' => now(),
    ]);

    // Pending approval (should NOT appear in history)
    $leave->approvals()->create([
        'approver_id' => $hrEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $response = $this->actingAs($hr)
        ->getJson('/api/v1/approvals/history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'approved');
});

/**
 * Approval Matrix Service Tests
 */
test('approval matrix routes high-value reimbursement through multiple steps', function () {
    $manager = User::factory()->create();
    Employee::factory()->create(['user_id' => $manager->id]);

    $employee = Employee::factory()->create();
    $category = ReimbursementCategory::factory()->create();
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $employee->id,
        'category_id' => $category->id,
        'amount' => 6000000,
        'status' => ReimbursementStatus::PENDING,
    ]);

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_REIMBURSEMENT,
        'condition_type' => 'min_amount',
        'condition_value' => '5000000',
        'approval_level' => 1,
        'approver_id' => null,
        'is_active' => true,
    ]);
    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_REIMBURSEMENT,
        'condition_type' => 'min_amount',
        'condition_value' => '5000000',
        'approval_level' => 2,
        'approver_id' => null,
        'is_active' => true,
    ]);

    $service = app(ApprovalMatrixService::class);
    $steps = $service->initializeSteps(ApprovalMatrixRule::MODULE_REIMBURSEMENT, $reimbursement);

    expect($steps)->toHaveCount(2);
    expect($steps[0]['key'])->toBe('1');
    expect($steps[1]['key'])->toBe('2');
});

test('approval matrix does not apply for low-value reimbursement', function () {
    $category = ReimbursementCategory::factory()->create();
    $employee = Employee::factory()->create();
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $employee->id,
        'category_id' => $category->id,
        'amount' => 100000,
        'status' => ReimbursementStatus::PENDING,
    ]);

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_REIMBURSEMENT,
        'condition_type' => 'min_amount',
        'condition_value' => '5000000',
        'approval_level' => 1,
        'is_active' => true,
    ]);

    $service = app(ApprovalMatrixService::class);
    $steps = $service->initializeSteps(ApprovalMatrixRule::MODULE_REIMBURSEMENT, $reimbursement);

    expect($steps)->toBe([]);
});

test('approval matrix currentStep returns first incomplete step', function () {
    $service = app(ApprovalMatrixService::class);
    $steps = [
        ['key' => '1', 'label' => 'Level 1', 'approver_id' => null],
        ['key' => '2', 'label' => 'Level 2', 'approver_id' => null],
    ];
    $completed = [['key' => '1', 'status' => 'approved']];

    $current = $service->currentStep($steps, $completed);
    expect($current['key'])->toBe('2');
});

test('approval matrix canActorApproveStep validates by approver_id', function () {
    $manager = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $reimbursement->setRelation('user', $employee->user);

    $service = app(ApprovalMatrixService::class);
    $step = ['key' => '1', 'approver_id' => $manager->id];

    expect($service->canActorApproveStep($manager, $reimbursement, $step))->toBeTrue();
});
