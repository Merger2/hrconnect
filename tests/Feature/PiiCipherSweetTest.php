<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FamilyDetail;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->for($this->company)->create();
    $this->department = Department::factory()->for($this->branch)->create();
    $this->position = Position::factory()->for($this->department)->create();
    $this->user = User::factory()->create();

    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);
});

// ─── CipherSweet Encryption Round-Trip ──────────────────────────────

test('CipherSweet encrypts employee NIK and phone in database', function () {
    $nik = '3276010101991111';
    $phone = '081299991111';

    $this->employee->update(['nik' => $nik, 'phone' => $phone]);

    $raw = DB::table('employees')->where('id', $this->employee->id)->first(['nik', 'phone']);

    expect($raw->nik)->not->toBe($nik)
        ->and($raw->phone)->not->toBe($phone)
        ->and($this->employee->fresh()->nik)->toBe($nik)
        ->and($this->employee->fresh()->phone)->toBe($phone);
});

test('CipherSweet blind index lookup works for employee NIK', function () {
    $nik = '3276010101992222';
    $this->employee->update(['nik' => $nik]);

    $found = Employee::whereBlind('nik', 'nik_hash', $nik)->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($this->employee->id);
});

test('CipherSweet blind index lookup works for employee phone', function () {
    $phone = '081299992222';
    $this->employee->update(['phone' => $phone]);

    $found = Employee::whereBlind('phone', 'phone_hash', $phone)->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($this->employee->id);
});

test('CipherSweet blind index lookup works for employee NPWP', function () {
    $npwp = '99.999.999.9-999.999';
    $this->employee->update(['npwp' => $npwp]);

    $found = Employee::whereBlind('npwp', 'npwp_hash', $npwp)->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($this->employee->id);
});

// ─── Null Encrypted Fields ─────────────────────────────────────────

test('CipherSweet handles update with blank bank_account_number', function () {
    $nik = '3276010101993333';
    $this->employee->update([
        'nik' => $nik,
        'bank_account_number' => '',
    ]);

    $this->employee->refresh();

    expect($this->employee->nik)->toBe($nik)
        ->and($this->employee->bank_account_number)->toBe('');
});

// ─── FamilyDetail CipherSweet ──────────────────────────────────────

test('CipherSweet encrypts FamilyDetail PII fields', function () {
    $nik = '3276010101994444';
    $phone = '081299994444';
    $address = 'Jl. Family No. 1';

    $family = FamilyDetail::create([
        'employee_id' => $this->employee->id,
        'name' => 'Family Member 1',
        'relationship' => 'spouse',
        'nik' => $nik,
        'phone' => $phone,
        'address' => $address,
    ]);

    $raw = DB::table('family_details')->where('id', $family->id)->first(['nik', 'phone', 'address']);

    expect($raw->nik)->not->toBe($nik)
        ->and($raw->phone)->not->toBe($phone)
        ->and($raw->address)->not->toBe($address)
        ->and($family->fresh()->nik)->toBe($nik)
        ->and($family->fresh()->phone)->toBe($phone)
        ->and($family->fresh()->address)->toBe($address);
});

test('FamilyDetail blind index lookup works', function () {
    $nik = '3276010101995555';
    $family = FamilyDetail::create([
        'employee_id' => $this->employee->id,
        'name' => 'Family Member 2',
        'relationship' => 'child',
        'nik' => $nik,
    ]);

    $found = FamilyDetail::whereBlind('nik', 'nik_hash', $nik)->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($family->id);
});

// ─── Company NPWP CipherSweet ──────────────────────────────────────

test('CipherSweet encrypts Company NPWP', function () {
    $npwp = '11.111.111.1-111.111';
    $this->company->update(['npwp' => $npwp]);

    $raw = DB::table('companies')->where('id', $this->company->id)->first(['npwp']);

    expect($raw->npwp)->not->toBe($npwp)
        ->and($this->company->fresh()->npwp)->toBe($npwp);
});

test('Company blind index lookup works for NPWP', function () {
    $npwp = '22.222.222.2-222.222';
    $this->company->update(['npwp' => $npwp]);

    $found = Company::whereBlind('npwp', 'npwp_hash', $npwp)->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($this->company->id);
});

// ─── ProfileResource Masking ───────────────────────────────────────

test('ProfileResource masks phone and bank account for authenticated user', function () {
    $rawPhone = '081298765432';
    $rawBankAccount = '123456789012';

    $this->employee->update([
        'phone' => $rawPhone,
        'bank_account_number' => $rawBankAccount,
    ]);

    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/profile');

    $response->assertOk()
        ->assertJsonPath('data.phone', '0812****5432')
        ->assertJsonPath('data.bank_account_number', '********9012');
});

test('ProfileResource returns masking for empty bank account', function () {
    $this->employee->update(['phone' => '081298765432']);

    $this->employee->bank_account_number = '';
    $this->employee->save();

    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/profile');

    $response->assertOk()
        ->assertJsonPath('data.phone', '0812****5432')
        ->assertJsonPath('data.bank_account_number', null);
});

// ─── Rule::encryptedUnique ────────────────────────────────────────

test('encryptedUnique prevents duplicate NIK via API', function () {
    $nik = '3276010101996666';

    $hrUser = User::factory()->create()->assignRole('hr-manager');
    $hrToken = $hrUser->createToken('test')->plainTextToken;

    $this->employee->update(['nik' => $nik]);

    $response = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->postJson('/api/v1/employees', [
            'nik' => $nik,
            'phone' => '081111111111',
            'full_name' => 'Duplicate NIK',
            'employee_number' => 'EMP-DUP',
            'gender' => 'L',
            'marital_status' => 'single',
            'blood_type' => 'O+',
            'education_level' => 'bachelor',
            'birth_date' => '1990-01-01',
            'join_date' => '2025-01-01',
            'salary_type' => 'monthly',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nik']);
});

// ─── Employee PII Audit Log ────────────────────────────────────────

test('PII endpoint records audit log with security log name', function () {
    $nik = '3276010101997777';
    $this->employee->update(['nik' => $nik]);

    $hrUser = User::factory()->create()->assignRole('hr-manager');
    $token = $hrUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->employee->id}/pii")
        ->assertOk();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'security',
        'subject_type' => Employee::class,
        'subject_id' => $this->employee->id,
        'causer_id' => $hrUser->id,
    ]);
});

test('PII endpoint returns forbidden for user without manage_employees', function () {
    $managerUser = User::factory()->create()->assignRole('manager');
    $token = $managerUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->employee->id}/pii")
        ->assertStatus(403);

    $this->assertDatabaseMissing('activity_log', [
        'log_name' => 'security',
        'subject_id' => $this->employee->id,
    ]);
});

test('PII endpoint audit log has correct description', function () {
    $hrUser = User::factory()->create()->assignRole('hr-manager');
    $token = $hrUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->employee->id}/pii")
        ->assertOk();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'security',
        'subject_type' => Employee::class,
        'subject_id' => $this->employee->id,
        'causer_id' => $hrUser->id,
        'description' => 'Mengakses data PII sensitif karyawan tanpa masking.',
    ]);
});

test('regular employee show does not create audit log', function () {
    $hrUser = User::factory()->create()->assignRole('hr-manager');
    $token = $hrUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->employee->id}")
        ->assertOk();

    $this->assertDatabaseMissing('activity_log', [
        'log_name' => 'security',
    ]);
});

test('multiple PII accesses create multiple audit entries', function () {
    $hrUser = User::factory()->create()->assignRole('hr-manager');
    $token = $hrUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->employee->id}/pii")
        ->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->employee->id}/pii")
        ->assertOk();

    $count = DB::table('activity_log')
        ->where('log_name', 'security')
        ->where('subject_id', $this->employee->id)
        ->count();

    expect($count)->toBe(2);
});
