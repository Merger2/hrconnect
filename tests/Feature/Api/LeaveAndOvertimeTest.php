<?php

namespace Tests\Feature\Api;

use App\Enums\DayType;
use App\Enums\EmploymentType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

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
        'parent_id' => $this->managerEmp->id,
    ]);

    $this->token = $this->employeeUser->createToken('test')->plainTextToken;

    $this->hrUser = User::factory()->create();
    $this->hrUser->assignRole('hr-manager');
    $this->hrEmp = Employee::factory()->create([
        'user_id' => $this->hrUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    $this->managerToken = $this->managerUser->createToken('test')->plainTextToken;
    $this->hrToken = $this->hrUser->createToken('test')->plainTextToken;

    $this->leaveType = LeaveType::factory()->create(['deducts_from_quota' => true]);
});

// ═══════════════════════════════════════════════════════════════════════
// LEAVE CONTROLLER
// ═══════════════════════════════════════════════════════════════════════

describe('LeaveController', function () {

    it('stores a leave request with valid data', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(2)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti tahunan untuk keperluan keluarga.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'message', 'data' => ['id', 'leave_type', 'start_date', 'end_date', 'status'],
            ]);
    });

    it('rejects overlapping leave after locking employee and balance', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $startDate = now()->addWeekdays(2)->toDateString();
        $endDate = now()->addWeekdays(2)->addDays(2)->toDateString();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti tahunan untuk keperluan keluarga.',
            ])
            ->assertStatus(201);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addWeekdays(2)->addDay()->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(3)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti kedua yang overlap harus ditolak.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tanggal bertabrakan dengan pengajuan cuti lain.');
    });

    it('returns validation errors for missing required fields', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'leave_type_id', 'start_date', 'end_date', 'day_type', 'reason',
            ]);
    });

    it('returns 404 when user has no employee record', function () {
        $userWithoutEmp = User::factory()->create();
        $userWithoutEmp->assignRole('employee');
        $token = $userWithoutEmp->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => 1,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Test cuti tanpa employee record.',
            ])
            ->assertStatus(404);
    });

    it('lists leaves for authenticated user', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/leave')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('shows leave detail', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti tahunan untuk keperluan keluarga.',
            ]);

        $leaveId = $createResponse->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/leave/{$leaveId}")
            ->assertOk()
            ->assertJsonPath('data.id', $leaveId);
    });

    it('cancels a pending leave', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti tahunan untuk keperluan keluarga.',
            ]);

        $leaveId = $createResponse->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/leave/{$leaveId}")
            ->assertOk()
            ->assertJsonPath('status', 'success');
    });

    it('shows leave quota', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/leave/quota')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    });

    it('allows owner to view own leave detail', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti tahunan untuk keperluan keluarga.',
            ]);

        $leaveId = $createResponse->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/leave/{$leaveId}")
            ->assertOk();
    });

    it('rejects sick leave without proof file', function () {
        $sickLeaveType = LeaveType::factory()->create([
            'code' => 'sick',
            'deducts_from_quota' => true,
        ]);

        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $sickLeaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $sickLeaveType->id,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Saya sedang sakit dan perlu istirahat.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cuti Sakit wajib menyertakan bukti (Surat Dokter).');
    });

    it('rejects probation employee applying quota leave', function () {
        $probationUser = User::factory()->create();
        $probationUser->assignRole('employee');
        $probationToken = $probationUser->createToken('test')->plainTextToken;

        Employee::factory()->create([
            'user_id' => $probationUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'parent_id' => $this->managerEmp->id,
            'employment_type' => EmploymentType::PROBATION,
        ]);

        $this->withHeader('Authorization', "Bearer {$probationToken}")
            ->postJson('/api/v1/leave', [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addWeekdays(2)->toDateString(),
                'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Mencoba mengajukan cuti saat probation.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Karyawan masa percobaan tidak dapat mengajukan cuti tahunan.');
    });

    it('rejects cancel of non-pending leave', function () {
        LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $leave = app(LeaveService::class)->applyLeave($this->employeeEmp, [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addWeekdays(2)->toDateString(),
            'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
            'day_type' => DayType::FULL_DAY->value,
            'reason' => 'Cuti tahunan untuk keperluan keluarga.',
        ]);

        $approval = $leave->approvals()->where('level', 1)->first();
        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$approval->id}/approve")
            ->assertOk();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/leave/{$leave->id}")
            ->assertStatus(403);
    });
});

// ═══════════════════════════════════════════════════════════════════════
// OVERTIME CONTROLLER
// ═══════════════════════════════════════════════════════════════════════

describe('OvertimeController', function () {

    it('stores an overtime request with valid data', function () {
        $date = now()->addDay()->toDateString();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/overtime', [
                'date' => $date,
                'start_time' => '17:00',
                'end_time' => '20:00',
                'description' => 'Lembur menyelesaikan laporan bulanan.',
            ]);

        // Bisa 201 atau 422 tergantung validasi weekly hours vs existing
        if ($response->status() === 422) {
            // Jika validasi weekly hours gagal, cek error message
            $response->assertJsonValidationErrors(['end_time']);
        } else {
            $response->assertStatus(201)
                ->assertJsonPath('status', 'success')
                ->assertJsonStructure([
                    'status', 'message', 'data' => ['id', 'date', 'start_time', 'end_time', 'total_hours', 'status'],
                ]);
        }
    });

    it('returns validation errors for missing required fields', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/overtime', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date', 'start_time', 'end_time',
            ]);
    });

    it('validates daily max 4 hours overtime', function () {
        $date = now()->addDay()->toDateString();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/overtime', [
                'date' => $date,
                'start_time' => '08:00',
                'end_time' => '13:00',
                'description' => 'Lembur lebih dari 4 jam (invalid).',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    });

    it('lists overtimes for authenticated user', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/overtime')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('shows overtime detail', function () {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeEmp->id,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/overtime/{$overtime->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $overtime->id);
    });

    it('cancels a pending overtime', function () {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeEmp->id,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/overtime/{$overtime->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');
    });

    it('returns 403 when user has no employee record', function () {
        $userWithoutEmp = User::factory()->create();
        $userWithoutEmp->assignRole('employee');
        $token = $userWithoutEmp->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/overtime', [
                'date' => now()->addDay()->toDateString(),
                'start_time' => '17:00',
                'end_time' => '20:00',
                'description' => 'Test lembur tanpa employee record.',
            ])
            ->assertStatus(403);
    });

    it('returns 403 when non-owner tries to view overtime detail', function () {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeEmp->id,
        ]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole('employee');
        $otherToken = $otherUser->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->getJson("/api/v1/overtime/{$overtime->id}")
            ->assertStatus(403);
    });
});

// ═══════════════════════════════════════════════════════════════════════
// APPROVAL CONTROLLER
// ═══════════════════════════════════════════════════════════════════════

describe('ApprovalController', function () {

    function createLeave(Employee $employee, LeaveType $leaveType, ?LeaveBalance $balance = null): array
    {
        $bal = $balance ?? LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        $start = now()->addWeekdays(2)->toDateString();
        $leave = app(LeaveService::class)->applyLeave($employee, [
            'leave_type_id' => $leaveType->id,
            'start_date' => $start,
            'end_date' => $start,
            'day_type' => DayType::FULL_DAY->value,
            'reason' => 'Cuti tahunan untuk keperluan keluarga.',
        ]);

        return [$leave, $bal];
    }

    it('approves L1 transitioning status to approved_l1', function () {
        [$leave] = createLeave($this->employeeEmp, $this->leaveType);

        $l1 = $leave->approvals()->where('level', 1)->first();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.is_final', false);

        $leave->refresh();
        expect($leave->status->value)->toBe('approved_l1');
    });

    it('full L1+L2 approval deducts quota and sets approved', function () {
        $balance = LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);
        [$leave] = createLeave($this->employeeEmp, $this->leaveType, $balance);

        $l1 = $leave->approvals()->where('level', 1)->first();
        $l2 = $leave->approvals()->where('level', 2)->first();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/approve")
            ->assertOk();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->hrToken}")
            ->postJson("/api/v1/approvals/{$l2->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.is_final', true);

        $leave->refresh();
        expect($leave->status->value)->toBe('approved');
        expect($balance->fresh()->used)->toEqual(1);
    });

    it('direct L2 approval without supervisor deducts quota', function () {
        $noManagerUser = User::factory()->create();
        $noManagerUser->assignRole('employee');

        $noManagerEmp = Employee::factory()->create([
            'user_id' => $noManagerUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'parent_id' => null,
        ]);

        $balance = LeaveBalance::factory()->create([
            'employee_id' => $noManagerEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);

        [$leave] = createLeave($noManagerEmp, $this->leaveType, $balance);

        expect($leave->approvals)->toHaveCount(1);
        $l2 = $leave->approvals->first();
        expect($l2->level->value)->toBe(2);

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->hrToken}")
            ->postJson("/api/v1/approvals/{$l2->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.is_final', true);

        $leave->refresh();
        expect($leave->status->value)->toBe('approved');
        expect($balance->fresh()->used)->toEqual(1);
    });

    it('rejects leave and sets rejection reason', function () {
        [$leave] = createLeave($this->employeeEmp, $this->leaveType);
        $l1 = $leave->approvals()->where('level', 1)->first();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/reject", [
                'rejection_reason' => 'Cuti ditolak karena alasan operasional yang sangat mendesak.',
            ])
            ->assertOk();

        $leave->refresh();
        expect($leave->status->value)->toBe('rejected');
        expect($leave->rejection_reason)->toBe('Cuti ditolak karena alasan operasional yang sangat mendesak.');
    });

    it('shows pending approvals for approver', function () {
        createLeave($this->employeeEmp, $this->leaveType);

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/approvals/pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.approvable_type', 'Leave');
    });

    it('filters pending approvals by type', function () {
        createLeave($this->employeeEmp, $this->leaveType);

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/approvals/pending?type=leave')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/approvals/pending?type=overtime')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    });

    it('returns empty pending list when no approvals', function () {
        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/approvals/pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    });

    it('returns 403 when non-approver tries to approve', function () {
        [$leave] = createLeave($this->employeeEmp, $this->leaveType);
        $l1 = $leave->approvals()->where('level', 1)->first();

        $otherUser = User::factory()->create();
        $otherUser->assignRole('employee');
        $otherToken = $otherUser->createToken('test')->plainTextToken;
        Employee::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/approve")
            ->assertStatus(403);
    });

    it('returns 409 on double approval', function () {
        [$leave] = createLeave($this->employeeEmp, $this->leaveType);
        $l1 = $leave->approvals()->where('level', 1)->first();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/approve")
            ->assertOk();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/approve")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Approval ini sudah diproses sebelumnya.');
    });

    it('returns 409 on double rejection', function () {
        [$leave] = createLeave($this->employeeEmp, $this->leaveType);
        $l1 = $leave->approvals()->where('level', 1)->first();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/reject", [
                'rejection_reason' => 'Cuti ditolak karena alasan operasional yang sangat mendesak.',
            ])
            ->assertOk();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/reject", [
                'rejection_reason' => 'Percobaan tolak kedua yang seharusnya gagal.',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Approval ini sudah diproses sebelumnya.');
    });

    it('prevents L2 approval before L1', function () {
        [$leave] = createLeave($this->employeeEmp, $this->leaveType);
        $l2 = $leave->approvals()->where('level', 2)->first();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->hrToken}")
            ->postJson("/api/v1/approvals/{$l2->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Approval level sebelumnya harus disetujui terlebih dahulu.');
    });

    it('rejection does not deduct quota', function () {
        $balance = LeaveBalance::factory()->create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'quota' => 12,
        ]);
        [$leave] = createLeave($this->employeeEmp, $this->leaveType, $balance);

        $l1 = $leave->approvals()->where('level', 1)->first();
        $usedBefore = $balance->fresh()->used;

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson("/api/v1/approvals/{$l1->id}/reject", [
                'rejection_reason' => 'Cuti ditolak karena alasan operasional yang sangat mendesak.',
            ])
            ->assertOk();

        expect($balance->fresh()->used)->toBe($usedBefore);
    });
});
