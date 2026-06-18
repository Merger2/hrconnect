<?php

namespace Tests\Feature\Api;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

// ─── Shared setup ───────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->for($this->company)->create();
    $this->department = Department::factory()->for($this->branch)->create();
    $this->position = Position::factory()->for($this->department)->create([
        'basic_salary' => 7_000_000,
    ]);

    $this->hrUser = User::factory()->create();
    $this->hrUser->assignRole('hr-manager');
    Employee::factory()->create([
        'user_id' => $this->hrUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');

    $this->managerUser = User::factory()->create();
    $this->managerUser->assignRole('manager');

    $this->financeUser = User::factory()->create();
    $this->financeUser->assignRole('finance');
    Employee::factory()->create([
        'user_id' => $this->financeUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);
});

// ═══════════════════════════════════════════════════════════════════════
// 1. EMPLOYEE CONTROLLER CRUD
// ═══════════════════════════════════════════════════════════════════════

describe('EmployeeController CRUD', function () {

    it('stores a new employee with user account', function () {
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $responded = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/employees', [
                'name' => 'Budi Baru',
                'email' => 'budi@company.com',
                'password' => 'Secret123!',
                'phone' => '081234567890',
                'nik' => '3276010101990001',
                'employee_number' => 'EMP-X1',
                'full_name' => 'Budi Santoso Baru',
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'gender' => 'L',
                'marital_status' => 'single',
                'employment_type' => 'permanent',
                'birth_date' => '1990-01-15',
                'join_date' => '2024-01-01',
                'salary_type' => 'monthly',
                'education_level' => 'bachelor',
                'institution_name' => 'Universitas Indonesia',
                'graduation_year' => 2015,
            ]);

        $responded->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'message', 'data' => ['id', 'full_name', 'employee_number', 'email'],
            ]);

        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-X1']);
        $this->assertDatabaseHas('users', ['email' => 'budi@company.com']);
    });

    it('returns validation errors when storing employee with missing fields', function () {
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/employees', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'email', 'password', 'employee_number', 'full_name',
                'company_id', 'branch_id', 'department_id', 'position_id',
                'gender', 'marital_status', 'employment_type', 'birth_date',
                'join_date', 'salary_type',
                'phone', 'nik', 'education_level', 'institution_name', 'graduation_year',
            ]);
    });

    it('lists employees paginated', function () {
        Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/employees')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('validates employee list query parameters', function () {
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/employees?search=a&per_page=101&page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search', 'per_page', 'page']);
    });

    it('shows employee detail', function () {
        $employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'nik' => '3276010101990001',
            'npwp' => '12.345.678.9-012.345',
            'phone' => '081234567890',
            'bank_account_number' => '1234567890',
        ]);
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.full_name', $employee->full_name)
            ->assertJsonMissingPath('data.nik')
            ->assertJsonMissingPath('data.npwp')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.bank_account_number');
    });

    it('reveals employee PII only through dedicated audited endpoint', function () {
        $employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'nik' => '3276010101990001',
            'npwp' => '12.345.678.9-012.345',
            'phone' => '081234567890',
            'bank_account_number' => '1234567890',
        ]);
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/employees/{$employee->id}/pii")
            ->assertOk()
            ->assertJsonPath('data.nik', '3276010101990001')
            ->assertJsonPath('data.npwp', '12.345.678.9-012.345')
            ->assertJsonPath('data.phone', '081234567890')
            ->assertJsonPath('data.bank_account_number', '1234567890');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'causer_id' => $this->hrUser->id,
        ]);
    });

    it('forbids employee PII endpoint for view-only employee role', function () {
        $employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/employees/{$employee->id}/pii")
            ->assertStatus(403);
    });

    it('updates employee fields', function () {
        $employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/employees/{$employee->id}", [
                'full_name' => 'Nama Updated',
                'employment_type' => 'contract',
            ])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Nama Updated');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Nama Updated',
            'employment_type' => 'contract',
        ]);
    });

    it('forbids employee update for view-only manager role', function () {
        $employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'full_name' => 'Nama Awal',
        ]);
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/employees/{$employee->id}", [
                'full_name' => 'Nama Tidak Boleh Update',
            ])
            ->assertStatus(403);

        expect($employee->fresh()->full_name)->toBe('Nama Awal');
    });

    it('soft-deletes employee', function () {
        $employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted($employee);
    });

    it('rejects employee CRUD for non-HR role', function () {
        Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/employees')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/employees', [])
            ->assertStatus(403);
    });
});

// ═══════════════════════════════════════════════════════════════════════
// 2. APPROVAL CONTROLLER FLOW
// ═══════════════════════════════════════════════════════════════════════

describe('ApprovalController flow', function () {

    beforeEach(function () {
        $this->managerEmp = Employee::factory()->create([
            'user_id' => $this->managerUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $this->submitterEmp = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'parent_id' => $this->managerEmp->id,
        ]);

        $leaveType = LeaveType::factory()->create(['deducts_from_quota' => true]);

        LeaveBalance::factory()->create([
            'employee_id' => $this->submitterEmp->id,
            'leave_type_id' => $leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $this->leave = Leave::factory()->create([
            'employee_id' => $this->submitterEmp->id,
            'leave_type_id' => $leaveType->id,
            'status' => RequestStatus::PENDING,
        ]);

        $this->approval = $this->leave->approvals()->create([
            'approver_id' => $this->managerEmp->id,
            'level' => ApprovalLevel::L1_SUPERVISOR,
            'status' => ApprovalStatus::PENDING,
        ]);
    });

    it('lists pending approvals for manager', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/approvals/pending')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'data', 'meta',
            ]);
    });

    it('approves a pending approval', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/approve", [
                'notes' => 'Setuju, silakan lanjut',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('approvals', [
            'id' => $this->approval->id,
            'status' => ApprovalStatus::APPROVED,
        ]);
    });

    it('rejects a pending approval with reason', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/reject", [
                'rejection_reason' => 'Tidak bisa disetujui karena sedang tahap produksi',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    });

    it('returns 403 when non-approver tries to approve', function () {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('employee');
        $otherToken = $otherUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/approve", [
                'notes' => 'Mencoba approve',
            ])
            ->assertStatus(403);
    });

    it('returns 409 when approving already processed approval', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->approval->update(['status' => ApprovalStatus::APPROVED]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/approve", [
                'notes' => 'Coba approve lagi',
            ])
            ->assertStatus(409);
    });

    it('rejects L2 approval before L1 is approved', function () {
        $hrEmp = $this->hrUser->employee;

        $l2 = $this->leave->approvals()->create([
            'approver_id' => $hrEmp->id,
            'level' => ApprovalLevel::L2_MANAGER,
            'status' => ApprovalStatus::PENDING,
        ]);

        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$l2->id}/approve", [
                'notes' => 'Mencoba bypass L1',
            ])
            ->assertStatus(422);

        expect($l2->fresh()->status)->toBe(ApprovalStatus::PENDING);
    });

    it('returns 404 when employee has no user link for pending approvals', function () {
        $noLinkUser = User::factory()->create();
        $noLinkUser->assignRole('manager');
        $token = $noLinkUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/approvals/pending')
            ->assertStatus(404);
    });
});

// ═══════════════════════════════════════════════════════════════════════
// 3. REIMBURSEMENT CONTROLLER
// ═══════════════════════════════════════════════════════════════════════

describe('ReimbursementController', function () {

    beforeEach(function () {
        Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $this->category = ReimbursementCategory::factory()
            ->for($this->company)
            ->create();
    });

    it('stores reimbursement with receipt upload', function () {
        $token = $this->employeeUser->createToken('test')->plainTextToken;
        $fakeReceipt = UploadedFile::fake()->image('receipt.jpg', 100, 100);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/reimbursement', [
                'category_id' => $this->category->id,
                'amount' => 150_000,
                'description' => 'Biaya transportasi meeting klien',
                'expense_date' => now()->subDay()->toDateString(),
                'receipt' => $fakeReceipt,
            ])
            ->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'message', 'data' => ['id', 'amount', 'status'],
            ]);
    });

    it('lists reimbursements for authenticated user', function () {
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/reimbursement')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'data', 'meta',
            ]);
    });

    it('shows reimbursement detail', function () {
        $employee = $this->employeeUser->employee;
        $reimbursement = Reimbursement::factory()->create([
            'employee_id' => $employee->id,
            'category_id' => $this->category->id,
        ]);
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/reimbursement/{$reimbursement->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $reimbursement->id);
    });

    it('soft-deletes own reimbursement', function () {
        $employee = $this->employeeUser->employee;
        $reimbursement = Reimbursement::factory()->create([
            'employee_id' => $employee->id,
            'category_id' => $this->category->id,
        ]);
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/reimbursement/{$reimbursement->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted($reimbursement);
    });

    it('returns validation errors for invalid reimbursement data', function () {
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/reimbursement', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id', 'amount', 'description', 'expense_date', 'receipt',
            ]);
    });
});

// ═══════════════════════════════════════════════════════════════════════
// 4. EMPLOYEE TERMINATION CONTROLLER
// ═══════════════════════════════════════════════════════════════════════

describe('EmployeeTerminationController', function () {

    beforeEach(function () {
        $this->terminateEmployee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'status' => EmployeeStatus::ACTIVE,
            'employment_type' => EmploymentType::PERMANENT,
            'join_date' => '2020-01-01',
        ]);
    });

    it('terminates active employee with resign type', function () {
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/employees/{$this->terminateEmployee->id}/terminate", [
                'type' => 'resign',
                'reason' => 'Mengundurkan diri karena pindah kota',
                'date' => '2026-06-30',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'message', 'data' => [
                    'id', 'full_name', 'status', 'termination_type',
                    'financial_summary',
                ],
            ]);
    });

    it('processes contract end batch', function () {
        $contractUser = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $contractUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'status' => EmployeeStatus::ACTIVE,
            'employment_type' => EmploymentType::CONTRACT,
            'contract_end_date' => '2026-05-15',
            'join_date' => '2025-01-01',
        ]);

        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/employees/terminate/contract-end', [
                'date' => '2026-06-30',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'message', 'data' => ['processed_count'],
            ]);
    });

    it('returns 403 for non-HR user trying to terminate', function () {
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/employees/{$this->terminateEmployee->id}/terminate", [
                'type' => 'resign',
                'reason' => 'Test',
                'date' => '2026-06-30',
            ])
            ->assertStatus(403);
    });

    it('returns 422 on invalid termination type', function () {
        $token = $this->hrUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/employees/{$this->terminateEmployee->id}/terminate", [
                'type' => 'invalid_type',
                'date' => '2026-06-30',
            ])
            ->assertStatus(422);
    });
});
