<?php

use App\Enums\PayrollStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\FaceRecognitionService;
use App\Services\PayrollCalculatorService;
use Database\Seeders\PayrollConfigSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

uses(RefreshDatabase::class);

test('PostgreSQL integration suite runs against pgsql', function () {
    expect(DB::getDriverName())->toBe('pgsql');
});

test('required PostgreSQL extensions are installed', function () {
    $extensions = collect(DB::select(<<<'SQL'
        SELECT extname
        FROM pg_extension
        WHERE extname IN ('vector', 'pg_trgm', 'pgcrypto')
        ORDER BY extname
    SQL))->pluck('extname')->all();

    expect($extensions)->toBe(['pg_trgm', 'pgcrypto', 'vector']);
});

function createPostgresEmployee(array $attributes = []): int
{
    $sequence = fake()->unique()->numberBetween(1000, 999999);

    $companyId = DB::table('companies')->insertGetId([
        'name' => $attributes['company_name'] ?? 'PT PostgreSQL Test',
        'code' => $attributes['company_code'] ?? "PG{$sequence}",
        'phone' => '021000000',
        'email' => fake()->unique()->safeEmail(),
        'npwp' => '00.000.000.0-000.000',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branchId = DB::table('branches')->insertGetId([
        'company_id' => $companyId,
        'name' => 'HQ',
        'address' => 'Jakarta',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 100,
        'is_main' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $departmentId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Engineering',
        'code' => "ENG{$sequence}",
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $positionId = DB::table('positions')->insertGetId([
        'department_id' => $departmentId,
        'name' => 'Staff',
        'code' => "STF{$sequence}",
        'grade' => 1,
        'basic_salary' => 5_000_000,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = DB::table('users')->insertGetId([
        'name' => 'Postgres User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $faceEmbedding = $attributes['face_embedding'] ?? null;

    $employee = Employee::query()->create([
        'user_id' => $userId,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $departmentId,
        'position_id' => $positionId,
        'employee_number' => $attributes['employee_number'] ?? fake()->unique()->bothify('PG-###'),
        'full_name' => $attributes['full_name'] ?? 'Postgres Employee',
        'nik' => $attributes['nik'] ?? fake()->unique()->numerify('327601010199####'),
        'phone' => $attributes['phone'] ?? fake()->unique()->numerify('08123456####'),
        'gender' => 'L',
        'marital_status' => 'single',
        'employment_type' => 'permanent',
        'salary_type' => 'monthly',
        'status' => 'active',
        'birth_date' => '1990-01-01',
        'join_date' => '2020-01-01',
        'education_level' => 'bachelor',
        'institution_name' => 'PostgreSQL University',
        'graduation_year' => 2012,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if ($faceEmbedding !== null) {
        $employee->forceFill(['face_embedding' => $faceEmbedding])->save();
    }

    return $employee->id;
}

function createPostgresLeaveType(): int
{
    return DB::table('leave_types')->insertGetId([
        'name' => 'Cuti Tahunan',
        'code' => fake()->unique()->lexify('ANN???'),
        'quota' => 12,
        'is_paid' => true,
        'deducts_from_quota' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('leave balance constraint allows carry forward usage beyond base quota', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-001']);
    $leaveTypeId = createPostgresLeaveType();

    DB::table('leave_balances')->insert([
        'employee_id' => $employeeId,
        'leave_type_id' => $leaveTypeId,
        'year' => 2026,
        'quota' => 12,
        'used' => 13,
        'carry_forward' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('leave_balances')->where('employee_id', $employeeId)->value('used'))
        ->toBe('13.0');
});

test('leave balance constraint rejects usage beyond quota plus carry forward', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-002']);
    $leaveTypeId = createPostgresLeaveType();

    $this->expectException(QueryException::class);

    DB::table('leave_balances')->insert([
        'employee_id' => $employeeId,
        'leave_type_id' => $leaveTypeId,
        'year' => 2026,
        'quota' => 12,
        'used' => 16,
        'carry_forward' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('pgvector stores 128 dimension face embeddings and supports nearest neighbor queries', function () {
    $baseEmbedding = array_fill(0, 128, 0.1);
    $nearEmbedding = array_fill(0, 128, 0.11);

    $firstEmployeeId = createPostgresEmployee([
        'employee_number' => 'PG-V01',
        'face_embedding' => new Vector($baseEmbedding),
    ]);

    createPostgresEmployee([
        'employee_number' => 'PG-V02',
        'face_embedding' => new Vector(array_fill(0, 128, 0.9)),
    ]);

    $nearest = Employee::query()
        ->nearestNeighbors('face_embedding', new Vector($nearEmbedding), Distance::Cosine)
        ->first();

    expect($nearest)->not->toBeNull()
        ->and($nearest->id)->toBe($firstEmployeeId)
        ->and($nearest->face_embedding)->toBeInstanceOf(Vector::class)
        ->and($nearest->face_embedding->toArray())->toHaveCount(128);
});

test('FaceRecognitionService verifies matching face embeddings and rejects distant embeddings', function () {
    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-F01',
        'face_embedding' => new Vector(array_fill(0, 128, 0.1)),
    ]);

    $employee = Employee::query()->findOrFail($employeeId);
    $service = app(FaceRecognitionService::class);

    expect($service->verifyFace($employee, array_fill(0, 128, 0.1)))
        ->toMatchArray(['valid' => true]);

    $service->verifyFace($employee, [1.0, ...array_fill(0, 127, 0.0)]);
})->throws(FaceNotRecognizedException::class);

test('CipherSweet encrypts employee PII and supports blind index lookups', function () {
    $nik = '3276010101999999';
    $phone = '081299999999';

    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-CS1',
        'full_name' => 'CipherSweet Employee',
        'nik' => $nik,
        'phone' => $phone,
    ]);

    $employee = Employee::query()->findOrFail($employeeId);

    $raw = DB::table('employees')->where('id', $employee->id)->first(['nik', 'phone']);

    expect($raw->nik)->not->toBe($nik)
        ->and($raw->phone)->not->toBe($phone)
        ->and($employee->refresh()->nik)->toBe($nik)
        ->and($employee->phone)->toBe($phone)
        ->and(Employee::whereBlind('nik', 'nik_hash', $nik)->first()?->is($employee))->toBeTrue()
        ->and(Employee::whereBlind('phone', 'phone_hash', $phone)->first()?->is($employee))->toBeTrue();
});

test('PostgreSQL rejects employees managed by themselves', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-SELF']);

    $this->expectException(QueryException::class);

    DB::table('employees')
        ->where('id', $employeeId)
        ->update(['parent_id' => $employeeId]);
});

test('PostgreSQL rejects future attendance dates', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-FUTURE']);

    $this->expectException(QueryException::class);

    DB::table('attendances')->insert([
        'employee_id' => $employeeId,
        'date' => now()->addDay()->toDateString(),
        'status' => 'on_time',
        'is_wfa' => false,
        'late_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('payroll generation updates existing draft row instead of creating duplicate on PostgreSQL', function () {
    $this->seed(PayrollConfigSeeder::class);
    Cache::forget('tax_configs');
    Cache::forget('bpjs_configs');

    $employeeId = createPostgresEmployee(['employee_number' => 'PG-PAY1']);
    $employee = Employee::query()->with('position')->findOrFail($employeeId);
    $service = app(PayrollCalculatorService::class);

    $first = $service->generatePayroll($employee, '2026-06');
    $first->update(['pdf_path' => 'payslips/original.pdf']);

    $employee->position->update(['basic_salary' => 6_000_000]);
    $employee->load('position');

    $second = $service->generatePayroll($employee, '2026-06');

    expect($second->id)->toBe($first->id)
        ->and((float) $second->basic_salary)->toBe(6_000_000.0)
        ->and($second->pdf_path)->toBe('payslips/original.pdf')
        ->and(Payroll::query()->where('employee_id', $employeeId)->where('period', '2026-06')->count())->toBe(1);
});

test('payroll generation rejects existing published payroll on PostgreSQL', function () {
    $this->seed(PayrollConfigSeeder::class);
    Cache::forget('tax_configs');
    Cache::forget('bpjs_configs');

    $employeeId = createPostgresEmployee(['employee_number' => 'PG-PAY2']);
    $employee = Employee::query()->with('position')->findOrFail($employeeId);

    Payroll::query()->create([
        'employee_id' => $employeeId,
        'period' => '2026-06',
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5_000_000,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    expect(fn () => app(PayrollCalculatorService::class)->generatePayroll($employee, '2026-06'))
        ->toThrow(BusinessRuleException::class, 'sudah dikunci permanen');
});
