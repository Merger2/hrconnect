<?php

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;

test('admin can open the operational report center', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee(__('Report Center'));

    $this->actingAs($employee)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

test('leave report export returns an excel download', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);

    Leave::factory()->create([
        'employee_id' => $employeeRecord->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.leaves.export', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'approval_status' => 'all',
            'request_type' => 'all',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('leave-report-');
});

test('overtime report export returns an excel download', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);

    Overtime::create([
        'employee_id' => $employeeRecord->id,
        'date' => now()->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '20:00:00',
        'total_hours' => 2,
        'description' => 'Month-end processing',
        'status' => 'approved',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.overtime.export', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'approved',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('overtime-report-');
});

test('schedule roster report export returns an excel download', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);
    $shift = Shift::create([
        'name' => 'Morning',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    ShiftSchedule::create([
        'employee_id' => $employeeRecord->id,
        'shift_id' => $shift->id,
        'date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.schedules.export', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'shift_id' => $shift->id,
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('schedule-roster-report-');
});

test('payroll summary report export returns an excel download', function () {
    $finance = User::factory()->admin(true)->create();
    $employee = User::factory()->create();

    Payroll::create([
        'employee_id' => Employee::factory()->create(['user_id' => $employee->id])->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'overtime_pay' => 250000,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 100000,
        'net_salary' => 5650000,
        'status' => 'paid',
    ]);

    $response = $this->actingAs($finance)
        ->get(route('admin.reports.payrolls.export', [
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'paid',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('payroll-summary-report-');
});
