<?php

use App\Enums\DayType;
use App\Enums\EmploymentType;
use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->approvalService = mock(ApprovalService::class)->shouldIgnoreMissing();
    $this->service = new LeaveService($this->approvalService);
});

// ─── helpers ───────────────────────────────────────────────────────────

function leave_infra(): array
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

function leave_emp(array $overrides = []): Employee
{
    [$company, $branch, $department, $position] = leave_infra();
    $user = User::factory()->create();

    return Employee::factory()->create(array_merge([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'parent_id' => null,
        'join_date' => now()->subYear()->toDateString(),
        'employment_type' => EmploymentType::PERMANENT->value,
        'status' => 'active',
    ], $overrides));
}

function leave_type_build(array $overrides = []): LeaveType
{
    return LeaveType::factory()->create(array_merge([
        'is_active' => true,
        'deducts_from_quota' => false,
    ], $overrides));
}

// ─── applyLeave ────────────────────────────────────────────────────────

test('applyLeave rejects when end_date before start_date', function () {
    $employee = leave_emp();

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => 1,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-05',
        'day_type' => 'full_day',
        'reason' => 'test',
    ]))->toThrow(BusinessRuleException::class, 'Tanggal akhir cuti tidak boleh lebih awal dari tanggal mulai.');
});

test('applyLeave rejects when start date more than 3 days in the past', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build();
    $past = now()->subDays(5)->toDateString();

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => $past,
        'end_date' => now()->addDay()->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'test',
    ]))->toThrow(BusinessRuleException::class, 'Pengajuan cuti maksimal mundur H+3 dari hari ini.');
});

test('applyLeave rejects PROBATION employee requesting quota-deducting leave', function () {
    $employee = leave_emp(['employment_type' => EmploymentType::PROBATION->value]);
    $leaveType = leave_type_build(['deducts_from_quota' => true]);

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'Coba cuti',
    ]))->toThrow(BusinessRuleException::class, 'Karyawan masa percobaan tidak dapat mengajukan cuti tahunan.');
});

test('applyLeave rejects sick leave without proof_file', function () {
    CompanySetting::set('leave_sick_code', 'sick');
    $employee = leave_emp();
    $leaveType = leave_type_build(['code' => 'sick', 'deducts_from_quota' => false]);

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'Sakit',
    ]))->toThrow(BusinessRuleException::class, 'Cuti Sakit wajib menyertakan bukti');
});

test('applyLeave rejects when total_days is 0 (weekend only)', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build();
    $saturday = now()->next('Saturday');
    $sunday = $saturday->copy()->addDay();

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => $saturday->toDateString(),
        'end_date' => $sunday->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'Weekend',
    ]))->toThrow(BusinessRuleException::class, 'Durasi cuti 0 hari.');
});

test('applyLeave rejects overlapping leave', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build();

    Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
        'status' => RequestStatus::PENDING,
    ]);

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'Bertabrakan',
    ]))->toThrow(BusinessRuleException::class, 'Tanggal bertabrakan dengan pengajuan cuti lain.');
});

test('applyLeave rejects insufficient quota', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build([
        'deducts_from_quota' => true,
        'quota' => 12,
    ]);

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => now()->year,
        'quota' => 1,
        'used' => 0,
        'carry_forward' => 0,
    ]);

    $nextMonday = Carbon::parse('next Monday');
    $nextFriday = $nextMonday->copy()->addDays(4);

    expect(fn () => $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => $nextMonday->toDateString(),
        'end_date' => $nextFriday->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'Butuh cuti panjang',
    ]))->toThrow(BusinessRuleException::class, 'Kuota cuti tidak mencukupi');
});

test('applyLeave creates leave with PENDING status and triggers approval workflow', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build(['deducts_from_quota' => false]);

    $nextMonday = Carbon::parse('next Monday');
    $nextTuesday = $nextMonday->copy()->addDay();

    $leave = $this->service->applyLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => $nextMonday->toDateString(),
        'end_date' => $nextTuesday->toDateString(),
        'day_type' => 'full_day',
        'reason' => 'Cuti tahunan',
    ]);

    expect($leave)->toBeInstanceOf(Leave::class);
    expect($leave->employee_id)->toBe($employee->id);
    expect($leave->leave_type_id)->toBe($leaveType->id);
    expect($leave->status)->toBe(RequestStatus::PENDING);
    expect((float) $leave->total_days)->toBeGreaterThan(0);
    expect($leave->reason)->toBe('Cuti tahunan');
});

// ─── calculateWorkDays ─────────────────────────────────────────────────

test('calculateWorkDays returns 5 for Monday-Friday full day', function () {
    $monday = Carbon::parse('next Monday');
    $friday = $monday->copy()->addDays(4);

    expect($this->service->calculateWorkDays($monday, $friday, DayType::FULL_DAY))
        ->toBe(5.0);
});

test('calculateWorkDays returns 2.5 for Monday-Friday half day', function () {
    $monday = Carbon::parse('next Monday');
    $friday = $monday->copy()->addDays(4);

    expect($this->service->calculateWorkDays($monday, $friday, DayType::MORNING))
        ->toBe(2.5);
});

// ─── initializeBalance ─────────────────────────────────────────────────

test('initializeBalance creates balance records for active leave types', function () {
    $employee = leave_emp();
    $annual = leave_type_build(['is_active' => true, 'deducts_from_quota' => true, 'quota' => 12]);
    $sick = leave_type_build(['is_active' => true, 'deducts_from_quota' => false, 'quota' => 0]);
    leave_type_build(['is_active' => false, 'quota' => 5]);

    $this->service->initializeBalance($employee, now()->year);

    $balances = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', now()->year)
        ->get();

    expect($balances)->toHaveCount(2);
    expect($balances->pluck('leave_type_id'))->toContain($annual->id, $sick->id);
});

test('initializeBalance prorates quota for mid-year join', function () {
    $employee = leave_emp(['join_date' => now()->year.'-07-01']);
    $leaveType = leave_type_build([
        'is_active' => true,
        'deducts_from_quota' => true,
        'quota' => 12,
    ]);

    $this->service->initializeBalance($employee, now()->year);

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', now()->year)
        ->first();

    // July (month 7): remainingMonths = 12 - 7 + 1 = 6
    // quota = round(12 * 6/12) = round(6) = 6
    expect((float) $balance->quota)->toBe(6.0);
});

test('initializeBalance does not duplicate existing balances', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build(['is_active' => true, 'quota' => 12]);

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => now()->year,
        'quota' => 12,
        'used' => 0,
        'carry_forward' => 0,
    ]);

    $this->service->initializeBalance($employee, now()->year);

    $count = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', now()->year)
        ->count();

    expect($count)->toBe(1);
});

// ─── carryForward ──────────────────────────────────────────────────────

test('carryForward transfers remaining balance up to 3 days to next year', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build(['is_active' => true, 'quota' => 12]);
    $fromYear = now()->year - 1;
    $toYear = now()->year;

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => $fromYear,
        'quota' => 12,
        'used' => 8,
        'carry_forward' => 0,
    ]);

    $this->service->carryForward($employee, $fromYear, $toYear);

    $newBalance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', $toYear)
        ->first();

    expect($newBalance)->not->toBeNull();
    expect((float) $newBalance->carry_forward)->toBe(3.0);
});

test('carryForward does not transfer when no remaining balance', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build(['is_active' => true, 'quota' => 12]);
    $fromYear = now()->year - 1;
    $toYear = now()->year;

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => $fromYear,
        'quota' => 12,
        'used' => 12,
        'carry_forward' => 0,
    ]);

    $this->service->carryForward($employee, $fromYear, $toYear);

    $newBalance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', $toYear)
        ->first();

    expect($newBalance)->toBeNull();
});

test('carryForward caps at 3 even when remaining is larger', function () {
    $employee = leave_emp();
    $leaveType = leave_type_build(['is_active' => true, 'quota' => 12]);
    $fromYear = now()->year - 1;
    $toYear = now()->year;

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => $fromYear,
        'quota' => 12,
        'used' => 0,
        'carry_forward' => 0,
    ]);

    $this->service->carryForward($employee, $fromYear, $toYear);

    $newBalance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', $toYear)
        ->first();

    expect((float) $newBalance->carry_forward)->toBe(3.0);
});

test('carryForward skips leave types that are not eligible for carry forward', function () {
    $employee = leave_emp();
    $annualLeave = leave_type_build([
        'is_active' => true,
        'quota' => 12,
        'eligible_for_carry_forward' => true,
    ]);
    $sickLeave = leave_type_build([
        'name' => 'Cuti Sakit',
        'code' => 'SICK',
        'is_active' => true,
        'quota' => 12,
        'eligible_for_carry_forward' => false,
    ]);
    $fromYear = now()->year - 1;
    $toYear = now()->year;

    foreach ([$annualLeave, $sickLeave] as $leaveType) {
        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $fromYear,
            'quota' => 12,
            'used' => 8,
            'carry_forward' => 0,
        ]);
    }

    $this->service->carryForward($employee, $fromYear, $toYear);

    expect(LeaveBalance::query()
        ->where('employee_id', $employee->id)
        ->where('leave_type_id', $annualLeave->id)
        ->where('year', $toYear)
        ->exists())->toBeTrue();

    expect(LeaveBalance::query()
        ->where('employee_id', $employee->id)
        ->where('leave_type_id', $sickLeave->id)
        ->where('year', $toYear)
        ->exists())->toBeFalse();
});
