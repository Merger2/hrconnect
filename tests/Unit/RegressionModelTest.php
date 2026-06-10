<?php

use App\Enums\RequestStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function regressionModelEmployee(): Employee
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create();

    return Employee::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
}

test('overnight shift duration uses immutable assigned end date', function () {
    $shift = Shift::factory()->create([
        'start_time' => '23:00:00',
        'end_time' => '01:00:00',
    ]);

    expect($shift->duration)->toBe(2.0);
});

test('deleting approved l1 leave does not refund quota before full approval deduction', function () {
    $employee = regressionModelEmployee();
    $leaveType = LeaveType::factory()->create(['deducts_from_quota' => true]);
    $balance = LeaveBalance::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => now()->year,
        'quota' => 12,
        'used' => 0,
    ]);

    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'status' => RequestStatus::APPROVED_L1,
        'total_days' => 2,
    ]);

    $leave->delete();

    expect((float) $balance->fresh()->used)->toBe(0.0);
});
