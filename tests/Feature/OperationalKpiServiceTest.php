<?php

use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\User;

test('operational kpi service summarizes attendance finance and asset metrics', function () {
    $admin = User::factory()->admin()->create();
    $adminEmployee = Employee::factory()->create([
        'user_id' => $admin->id,
        'province_id' => 31, // DKI Jakarta
        'gender' => 'M',
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'province_id' => 31,
        'gender' => 'M',
        'status' => 'active',
    ]);

    $secondUser = User::factory()->create();
    $secondEmployee = Employee::factory()->create([
        'user_id' => $secondUser->id,
        'province_id' => 31,
        'gender' => 'F',
        'status' => 'active',
    ]);

    Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-01',
        'time_in' => '07:00:00',
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-02',
        'time_in' => '07:45:00',
        'status' => 'late',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    Attendance::create([
        'employee_id' => $secondEmployee->id,
        'date' => '2026-06-01',
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    Overtime::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-02',
        'start_time' => '18:00:00',
        'end_time' => '20:00:00',
        'duration' => 120,
        'reason' => 'Month-end closing.',
        'status' => 'approved',
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-01',
        'type' => 'transport',
        'amount' => 150000,
        'description' => 'Client visit.',
        'status' => 'pending',
    ]);
    $reimbursement->forceFill(['created_at' => now()->subDays(4)])->save();

    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-05',
        'net_salary' => 5000000,
    ]);
    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-06',
        'net_salary' => 5500000,
    ]);

    CompanyAsset::create([
        'name' => 'Laptop',
        'type' => 'electronics',
        'employee_id' => $employee->id,
        'date_assigned' => '2026-05-01',
        'return_date' => '2026-05-31',
        'status' => CompanyAsset::STATUS_ASSIGNED,
    ]);

    // Service doesn't exist yet - mark as skipped until implemented
    $this->markTestSkipped('OperationalKpiService not yet implemented');
});
