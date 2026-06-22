<?php

namespace Tests\Feature\Api;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->for($this->company)->create();
    $this->department = Department::factory()->for($this->branch)->create();
    $this->position = Position::factory()->for($this->department)->create([
        'basic_salary' => 7_000_000,
    ]);

    $this->managerUser = User::factory()->create();
    $this->managerUser->assignRole('manager');
    $this->managerEmp = Employee::factory()->create([
        'user_id' => $this->managerUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');
    $this->employeeEmp = Employee::factory()->create([
        'user_id' => $this->employeeUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);
});

// ─── Authentication gaps ─────────────────────────────────────────────

describe('authentication gaps', function () {

    it('pending without auth returns 401', function () {
        $this->getJson('/api/v1/approvals/pending')
            ->assertStatus(401);
    });

    it('approve without auth returns 401', function () {
        $this->postJson('/api/v1/approvals/1/approve', ['notes' => 'ok'])
            ->assertStatus(401);
    });

    it('reject without auth returns 401', function () {
        $this->postJson('/api/v1/approvals/1/reject', ['rejection_reason' => 'Alasan penolakan'])
            ->assertStatus(401);
    });
});

// ─── Authorization gaps ─────────────────────────────────────────────

describe('authorization gaps', function () {

    it('employee (non-manager) can view pending but gets empty list', function () {
        $token = $this->employeeUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/approvals/pending')
            ->assertOk()
            ->assertJsonPath('data', []);
    });
});

// ─── Resource & validation gaps ─────────────────────────────────────

describe('resource & validation gaps', function () {

    beforeEach(function () {
        $leaveType = LeaveType::factory()->create();

        $this->leave = Leave::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $leaveType->id,
            'status' => RequestStatus::PENDING,
            'start_date' => '2026-06-15',
        ]);

        LeaveBalance::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'quota' => 12,
            'used' => 0,
        ]);

        $this->approval = $this->leave->approvals()->create([
            'approver_id' => $this->managerEmp->id,
            'level' => ApprovalLevel::L1_SUPERVISOR,
            'status' => ApprovalStatus::PENDING,
        ]);
    });

    it('approve non-existent approval returns 404', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/approvals/99999/approve', ['notes' => 'Setuju'])
            ->assertStatus(404);
    });

    it('approve requires notes field validation returns 422', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/approve", [
                'notes' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    });

    it('reject requires reason field validation returns 422', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    });

    it('can successfully approve a pending approval', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/approve", [
                'notes' => 'Setuju, lanjutkan.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->approval->refresh();
        expect($this->approval->status)->toBe(ApprovalStatus::APPROVED);
    });

    it('rejects with valid reason and marks approval as rejected', function () {
        $token = $this->managerUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/approvals/{$this->approval->id}/reject", [
                'rejection_reason' => 'Dokumen pendukung tidak lengkap.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->approval->refresh();
        expect($this->approval->status)->toBe(ApprovalStatus::REJECTED);
    });
});
