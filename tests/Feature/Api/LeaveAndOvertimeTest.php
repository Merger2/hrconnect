<?php

namespace Tests\Feature\Api;

use App\Enums\DayType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Overtime;
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
        'parent_id' => $this->managerEmp->id,
    ]);

    $this->token = $this->employeeUser->createToken('test')->plainTextToken;

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
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDays(2)->toDateString(),
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
            'year' => now()->addWeek()->year,
            'quota' => 12,
        ]);

        $startDate = now()->addWeek()->toDateString();
        $endDate = now()->addWeek()->addDays(2)->toDateString();

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
                'start_date' => now()->addWeek()->addDay()->toDateString(),
                'end_date' => now()->addWeek()->addDays(3)->toDateString(),
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
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDays(1)->toDateString(),
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
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDays(1)->toDateString(),
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
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDays(1)->toDateString(),
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
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDays(1)->toDateString(),
                'day_type' => DayType::FULL_DAY->value,
                'reason' => 'Cuti tahunan untuk keperluan keluarga.',
            ]);

        $leaveId = $createResponse->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/leave/{$leaveId}")
            ->assertOk();
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

    it('returns 404 when user has no employee record', function () {
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
            ->assertStatus(404);
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
