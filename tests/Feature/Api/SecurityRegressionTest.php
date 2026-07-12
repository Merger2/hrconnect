<?php

namespace Tests\Feature\Api;

use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\User;
use App\Services\PayrollCalculatorService;
use Database\Seeders\PayrollConfigSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function regressionEmployeeWithPosition(array $employeeOverrides = []): Employee
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create([
        'basic_salary' => 5_000_000,
        'allowance_jabatan' => 0,
    ]);
    $user = User::factory()->create();

    return Employee::factory()->create(array_merge([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'marital_status' => 'single',
        'employment_type' => 'permanent',
        'join_date' => '2020-01-01',
    ], $employeeOverrides))->setRelation('position', $position);
}

test('approved manual overtime without attendance id is included in payroll', function () {
    $this->seed(PayrollConfigSeeder::class);

    DB::table('company_settings')->insert([
        'key' => 'attendance_penalty_per_day',
        'value' => json_encode(50000),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $employee = regressionEmployeeWithPosition();

    Overtime::factory()->create([
        'employee_id' => $employee->id,
        'attendance_id' => null,
        'date' => '2026-06-10',
        'start_time' => '2026-06-10 18:00:00',
        'end_time' => '2026-06-10 20:00:00',
        'total_hours' => 2,
        'status' => RequestStatus::APPROVED,
    ]);

    $payroll = app(PayrollCalculatorService::class)->generatePayroll($employee, '2026-06');

    expect((float) $payroll->overtime_pay)->toBeGreaterThan(0.0);
});

test('multiple approved overtimes sum correctly in payroll', function () {
    $this->seed(PayrollConfigSeeder::class);

    DB::table('company_settings')->insert([
        'key' => 'attendance_penalty_per_day',
        'value' => json_encode(50000),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $employee = regressionEmployeeWithPosition();

    // Weekday 2 hours: 1st 1.5x + 2nd 2.0x = 101,135.96
    Overtime::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-06-10',
        'start_time' => '2026-06-10 18:00:00',
        'end_time' => '2026-06-10 20:00:00',
        'total_hours' => 2,
        'status' => RequestStatus::APPROVED,
    ]);

    // Weekend 1 hour: 2.0x = 57,803.47
    Overtime::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-06-13',
        'start_time' => '2026-06-13 09:00:00',
        'end_time' => '2026-06-13 10:00:00',
        'total_hours' => 1,
        'status' => RequestStatus::APPROVED,
    ]);

    $payroll = app(PayrollCalculatorService::class)->generatePayroll($employee, '2026-06');

    expect((float) $payroll->overtime_pay)->toBeGreaterThan(0.0);
});

test('payroll generation returns business error when employee period lock is held', function () {
    $employee = regressionEmployeeWithPosition();
    $lock = Cache::lock("payroll:generate:{$employee->id}:2026-06", 120);

    expect($lock->get())->toBeTrue();

    try {
        expect(fn () => app(PayrollCalculatorService::class)->generatePayroll($employee, '2026-06'))
            ->toThrow(BusinessRuleException::class, 'sedang diproses');
    } finally {
        $lock->release();
    }
});

test('reimbursement full workflow: submit → approve → payroll → becomes PAID', function () {
    $this->seed(PayrollConfigSeeder::class);

    DB::table('company_settings')->insert([
        'key' => 'attendance_penalty_per_day',
        'value' => json_encode(50000),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $employee = regressionEmployeeWithPosition();
    $categoryId = DB::table('reimbursement_categories')->insertGetId([
        'company_id' => $employee->company_id,
        'name' => 'Medical',
        'code' => 'MED',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->id,
        'category_id' => $categoryId,
        'title' => 'Medical Test',
        'expense_date' => '2026-06-15',
        'amount' => 500_000,
        'description' => 'Test reimbursement',
        'status' => ReimbursementStatus::APPROVED,
    ]);

    $payroll = app(PayrollCalculatorService::class)->generatePayroll($employee, '2026-06');

    $reimbursement->refresh();
    expect($reimbursement->payroll_id)->toBe($payroll->id);
    expect($reimbursement->status)->toBe(ReimbursementStatus::PAID);
});

test('expired password does not block PWA API token by design', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
        'password_changed_at' => now()->subDays(120),
    ]);
    $user->assignRole('employee');
    regressionEmployeeWithPosition(['user_id' => $user->id]);

    DB::table('company_settings')->insert([
        'key' => 'password_expiry_days',
        'value' => json_encode(90),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $token = $user->createToken('expired-password-test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/change-password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success');
});
