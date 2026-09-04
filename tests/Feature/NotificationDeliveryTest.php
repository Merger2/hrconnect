<?php

declare(strict_types=1);

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\PayrollStatus;
use App\Enums\RequestStatus;
use App\Livewire\Admin\PayrollManager;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use App\Notifications\OvertimeStatusUpdated;
use App\Notifications\PayrollRejected as PayrollRejectedNotification;
use App\Notifications\PayrollSubmitted as PayrollSubmittedNotification;
use App\Notifications\PayrollVerified as PayrollVerifiedNotification;
use App\Support\LeaveApprovalService;
use App\Support\OvertimeApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Coverage gap P2: notification delivery — 15 class dipakai app tapi tanpa
 * assertSent. Di-cover di sini: payroll (submit/verify/reject), leave, overtime.
 */
function payrollRecord(Employee $employee): Payroll
{
    return Payroll::create([
        'employee_id' => $employee->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 5000000,
        'total_allowance' => 0,
        'gross_salary' => 5000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'payment_method' => 'transfer',
        'status' => PayrollStatus::DRAFT,
    ]);
}

// ─── PAYROLL ───

test('payroll submit notifies admin role users', function () {
    Notification::fake();

    $admin = User::factory()->admin(true)->create();
    $role = Role::create(['name' => 'admin', 'slug' => 'admin', 'permission_keys' => []]);
    $admin->roles()->sync([$role->id]);

    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);
    $payroll = payrollRecord($employee);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('submit', (string) $payroll->id)
        ->assertHasNoErrors();

    Notification::assertSentTo($admin, PayrollSubmittedNotification::class);
});

test('payroll verify notifies finance role users', function () {
    Notification::fake();

    $admin = User::factory()->admin(true)->create();
    $adminRole = Role::create(['name' => 'admin', 'slug' => 'admin', 'permission_keys' => []]);
    $admin->roles()->sync([$adminRole->id]);

    $finance = User::factory()->create();
    $financeRole = Role::create(['name' => 'finance', 'slug' => 'finance', 'permission_keys' => []]);
    $finance->roles()->sync([$financeRole->id]);

    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);
    $payroll = payrollRecord($employee);
    $payroll->update(['status' => PayrollStatus::SUBMITTED]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('verify', (string) $payroll->id)
        ->assertHasNoErrors();

    Notification::assertSentTo($finance, PayrollVerifiedNotification::class);
});

test('payroll reject notifies employee', function () {
    Notification::fake();

    $admin = User::factory()->admin(true)->create();
    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);
    $payroll = payrollRecord($employee);
    $payroll->update(['status' => PayrollStatus::SUBMITTED]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('confirmReject', (string) $payroll->id)
        ->set('rejectionReason', 'Periode salah')
        ->call('reject')
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe(PayrollStatus::DRAFT);

    Notification::assertSentTo($employeeUser, PayrollRejectedNotification::class);
});

// ─── LEAVE ───

test('leave approval notifies requesting employee with status update', function () {
    Notification::fake();

    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);

    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'status' => RequestStatus::APPROVED_L1,
        'reason' => 'Cuti sakit',
    ]);

    $admin = User::factory()->admin(true)->create();
    $adminEmployee = Employee::factory()->create(['user_id' => $admin->id]);
    $role = Role::create([
        'name' => 'Leave Approval Admin_'.uniqid(),
        'slug' => 'leave_approval_admin_'.uniqid(),
        'permission_keys' => ['admin.leave_approvals.manage'],
    ]);
    $admin->roles()->sync([$role->id]);
    $leave->approvals()->create([
        'approver_id' => $employee->id,
        'level' => ApprovalLevel::L1_SUPERVISOR,
        'status' => ApprovalStatus::APPROVED,
        'approved_at' => now(),
    ]);
    $leave->approvals()->create([
        'approver_id' => $adminEmployee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    app(LeaveApprovalService::class)->approve([$leave->id], $admin);

    expect($leave->refresh()->status)->toBe(RequestStatus::APPROVED);

    Notification::assertSentTo($employeeUser, LeaveStatusUpdated::class);
});

// ─── OVERTIME ───

test('overtime approval notifies employee with status update', function () {
    Notification::fake();

    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);

    $overtime = Overtime::factory()->create([
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);

    $actor = User::factory()->admin(true)->create();

    app(OvertimeApprovalService::class)->approve($overtime, $actor);

    expect($overtime->refresh()->status->value)->toBe('approved');

    Notification::assertSentTo($employeeUser, OvertimeStatusUpdated::class);
});
