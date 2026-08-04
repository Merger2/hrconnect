<?php

use App\Models\ApprovalMatrixRule;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Support\ApprovalMatrixService;

test('approval matrix resolves role-based approval for overtime workflow', function () {
    $managerRole = Role::create([
        'name' => 'Overtime Approver_'.uniqid(),
        'slug' => 'overtime_approver__'.uniqid().uniqid(),
        'description' => 'Can approve overtime.',
        'permission_keys' => [],
    ]);
    $manager = User::factory()->create();
    $manager->roles()->sync([$managerRole->id]);
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);
    $overtime = Overtime::create([
        'employee_id' => $employeeRecord->id,
        'date' => now()->toDateString(),
        'start_time' => now()->setTime(18, 0),
        'end_time' => now()->setTime(21, 0),
        'duration' => 180,
        'reason' => 'Release support',
        'status' => 'pending',
    ]);

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_OVERTIME,
        'condition_type' => 'min_amount',
        'condition_value' => '120',
        'approval_level' => 1,
        'approver_role_id' => $managerRole->id,
        'is_active' => true,
    ]);

    $matrix = app(ApprovalMatrixService::class);

    expect($matrix->matchingRule(ApprovalMatrixRule::MODULE_OVERTIME, $overtime)?->condition_type)->toBe('min_amount')
        ->and($matrix->canActorApprove($manager, ApprovalMatrixRule::MODULE_OVERTIME, $overtime))->toBeTrue();
});

test('approval matrix resolves permission steps for payroll sensitive workflow', function () {
    $approverRole = Role::create([
        'name' => 'Payroll Sensitive Approver_'.uniqid(),
        'slug' => 'payroll_sensitive_approver__'.uniqid().uniqid(),
        'description' => 'Can approve sensitive payroll actions.',
        'permission_keys' => ['admin.payrolls.approve_sensitive'],
    ]);
    $payrollAdmin = User::factory()->admin()->create();
    $payrollAdmin->roles()->sync([$approverRole->id]);
    $plainAdmin = User::factory()->admin()->create();

    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);
    $payroll = Payroll::create([
        'employee_id' => $employeeRecord->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'total_allowance' => 0,
        'gross_salary' => 1000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 15000000,
        'status' => 'draft',
    ]);

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_PAYROLL_SENSITIVE_ACTION,
        'condition_type' => 'min_amount',
        'condition_value' => '10000000',
        'approval_level' => 1,
        'approver_role_id' => $approverRole->id,
        'is_active' => true,
    ]);

    $matrix = app(ApprovalMatrixService::class);

    expect($matrix->matchingRule(ApprovalMatrixRule::MODULE_PAYROLL_SENSITIVE_ACTION, $payroll)?->condition_type)
        ->toBe('min_amount')
        ->and($matrix->canActorApprove($payrollAdmin, ApprovalMatrixRule::MODULE_PAYROLL_SENSITIVE_ACTION, $payroll))->toBeTrue()
        ->and($matrix->canActorApprove($plainAdmin, ApprovalMatrixRule::MODULE_PAYROLL_SENSITIVE_ACTION, $payroll))->toBeFalse();
});
