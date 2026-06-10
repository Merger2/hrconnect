<?php

namespace Tests\Feature\Api;

use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
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
