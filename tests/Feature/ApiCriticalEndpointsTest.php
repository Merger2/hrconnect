<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\Security\FaceRecognitionService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Regression + coverage untuk 4 grup endpoint API kritis yang sebelumnya
 * 0% ter-cover (prioritas #4 dari docs/TEST-COVERAGE-GAP.md):
 * loans, face, employees, notifications.
 *
 * ⚠️ Menangkap 2 app-bug:
 *  - LoanPolicy hilang → /api/v1/loans* selalu 403 (sekarang sudah dibuat)
 *  - EmployeeController eager-load `department` (relasi tidak ada) →
 *    RelationNotFoundException 500 begitu ada ≥1 employee (sudah diganti `division`)
 */
function apiFaceVector(): array
{
    return array_fill(0, 128, 0.1);
}

/**
 * Buat role ala RoleAndPermissionSeeder (slug + permission_keys JSON)
 * dan attach ke user. Role inilah yang dipakai HasRolePermissions trait.
 */
function apiGrantRole(User $user, string $name, array $permissionKeys): Role
{
    $role = Role::create([
        'name' => $name,
        'guard_name' => 'web',
        'slug' => Str::slug($name),
        'permission_keys' => $permissionKeys,
    ]);

    $user->assignRole($name);

    return $role;
}

// ─────────────────────────────────────────────────────────────
//  FACE — register / verify
// ─────────────────────────────────────────────────────────────

test('face register endpoint enrolls 128d embedding', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/face/register', ['embedding' => apiFaceVector()])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.employee_id', $employee->id);

    $this->assertDatabaseHas('face_descriptors', [
        'employee_id' => $employee->id,
        'is_active' => true,
    ]);
});

test('face register rejects malformed embedding', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/face/register', ['embedding' => array_fill(0, 10, 0.1)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('embedding');
});

test('face verify requires enrolled face first', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/face/verify', ['embedding' => apiFaceVector()])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Face ID belum didaftarkan. Silakan daftarkan wajah terlebih dahulu.');
});

test('face verify returns valid when embedding matches enrolled', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    app(FaceRecognitionService::class)->saveFaceDescriptor($employee, apiFaceVector());

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/face/verify', ['embedding' => apiFaceVector()])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.valid', true);
});

// ─────────────────────────────────────────────────────────────
//  LOANS — /api/v1/loans* (regresi LoanPolicy)
// ─────────────────────────────────────────────────────────────

test('employee can list own loans via api', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Loan::factory()->create(['employee_id' => $employee->id]);

    $other = User::factory()->create();
    $otherEmployee = Employee::factory()->create(['user_id' => $other->id]);
    Loan::factory()->create(['employee_id' => $otherEmployee->id]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/loans')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee_id', $employee->id);
});

test('admin can list all loans via api (LoanPolicy regression)', function () {
    $admin = User::factory()->admin()->create();
    apiGrantRole($admin, 'admin', ['view_loans', 'manage_loans']);

    Loan::factory()->count(2)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/loans')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data');
});

test('employee can create loan via api with approval workflow', function () {
    // L1 approver: supervisor (parent_id) dengan status active.
    $supervisorUser = User::factory()->create();
    $supervisor = Employee::factory()->create(['user_id' => $supervisorUser->id]);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'parent_id' => $supervisor->id,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/loans', [
        'amount' => 5_000_000,
        'tenor_months' => 12,
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.amount', 5_000_000)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('loans', [
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('approvals', [
        'approver_id' => $supervisor->id,
    ]);
});

test('loan store validates required fields', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/loans', ['amount' => 1_000_000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tenor_months');
});

test('loan store returns 404 when account has no employee record', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/loans', [
        'amount' => 1_000_000,
        'tenor_months' => 6,
    ])
        ->assertNotFound()
        ->assertJsonPath('message', 'Akun Anda belum terhubung dengan data karyawan.');
});

test('employee cannot view another users loan', function () {
    $owner = User::factory()->create();
    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $loan = Loan::factory()->create(['employee_id' => $ownerEmployee->id]);

    $other = User::factory()->create();
    Employee::factory()->create(['user_id' => $other->id]);

    Sanctum::actingAs($other);

    $this->getJson("/api/v1/loans/{$loan->id}")
        ->assertForbidden();
});

test('unauthenticated request to loans api is rejected', function () {
    $this->getJson('/api/v1/loans')->assertUnauthorized();
});

// ─────────────────────────────────────────────────────────────
//  EMPLOYEES — /api/v1/employees* (regresi eager-load department)
// ─────────────────────────────────────────────────────────────

test('admin can list employees with relations loaded (department eager-load regression)', function () {
    $admin = User::factory()->admin()->create();
    apiGrantRole($admin, 'admin', ['view_employees', 'manage_employees']);

    Employee::factory()->count(3)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/employees')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [[
                'id', 'full_name', 'employee_number', 'division', 'branch', 'position',
            ]],
        ]);
});

test('employee list rejects users without view_employees', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/employees')->assertForbidden();
});

test('admin can create employee via api', function () {
    $admin = User::factory()->admin()->create();
    apiGrantRole($admin, 'admin', ['view_employees', 'manage_employees']);

    $company = Company::factory()->create();
    $branch = Branch::factory()->create();
    $division = Division::factory()->create();
    $position = Position::factory()->create();

    Sanctum::actingAs($admin);

    $payload = [
        'name' => 'Test User',
        'email' => 'test-create@hrconnect.test',
        'password' => 'password123',
        'employee_number' => 'EMP-TEST-001',
        'full_name' => 'Test Karyawan',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'division_id' => $division->id,
        'position_id' => $position->id,
        'gender' => 'L',
        'marital_status' => 'single',
        'employment_type' => 'permanent',
        'birth_date' => '1995-01-01',
        'join_date' => '2024-01-15',
        'salary_type' => 'monthly',
        'phone' => '081234567890',
        'education_level' => 'bachelor',
        'institution_name' => 'Universitas Indonesia',
        'graduation_year' => 2017,
        'basic_salary' => 5_000_000,
    ];

    $this->postJson('/api/v1/employees', $payload)
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.full_name', 'Test Karyawan');

    $this->assertDatabaseHas('users', ['email' => 'test-create@hrconnect.test']);
    $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-TEST-001']);
});

test('employee create validates required and unique fields', function () {
    $admin = User::factory()->admin()->create();
    apiGrantRole($admin, 'admin', ['view_employees', 'manage_employees']);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/employees', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name', 'email', 'password', 'employee_number', 'full_name',
            'company_id', 'branch_id', 'division_id', 'position_id',
            'gender', 'employment_type', 'birth_date', 'join_date', 'salary_type',
            'phone', 'education_level', 'institution_name', 'graduation_year',
        ]);
});

test('employee create requires manage_employees permission', function () {
    $admin = User::factory()->admin()->create();
    apiGrantRole($admin, 'admin', ['view_employees']); // tanpa manage_employees

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/employees', [
        'name' => 'Test',
        'email' => 'test-2@hrconnect.test',
        'password' => 'password123',
        'employee_number' => 'EMP-TEST-002',
        'full_name' => 'Test',
        'company_id' => Company::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'division_id' => Division::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'gender' => 'L',
        'marital_status' => 'single',
        'employment_type' => 'permanent',
        'birth_date' => '1995-01-01',
        'join_date' => '2024-01-15',
        'salary_type' => 'monthly',
        'phone' => '081234567890',
        'education_level' => 'bachelor',
        'institution_name' => 'Universitas Indonesia',
        'graduation_year' => 2017,
    ])->assertForbidden();
});

test('employee me endpoint returns own identity', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id, 'nip' => 'NIP-0001']);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/employees/me')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.nip', 'NIP-0001');
});

// ─────────────────────────────────────────────────────────────
//  NOTIFICATIONS — /api/v1/notifications*
// ─────────────────────────────────────────────────────────────

function apiCreateNotification(User $user, array $data = []): DatabaseNotification
{
    return DatabaseNotification::create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\\Notifications\\PayrollPublished',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => array_merge([
            'title' => 'Payroll Agustus',
            'body' => 'Slip gaji sudah terbit.',
            'type' => 'payroll',
        ], $data),
    ]);
}

test('user can list own notifications via api', function () {
    $user = User::factory()->create();
    apiCreateNotification($user);
    apiCreateNotification($user, ['title' => 'Cuti disetujui']);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.unread', 2)
        ->assertJsonCount(2, 'data.items');
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();
    apiCreateNotification($user);
    apiCreateNotification($user);

    Sanctum::actingAs($user);

    $this->putJson('/api/v1/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.updated', 2);

    $this->assertSame(0, $user->unreadNotifications()->count());
});

test('user can delete own notification', function () {
    $user = User::factory()->create();
    $notification = apiCreateNotification($user);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/notifications/{$notification->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('user cannot delete another users notification', function () {
    $owner = User::factory()->create();
    $notification = apiCreateNotification($owner);

    $other = User::factory()->create();

    Sanctum::actingAs($other);

    $this->deleteJson("/api/v1/notifications/{$notification->id}")
        ->assertNotFound();
});
