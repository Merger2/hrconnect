<?php

use App\Enums\DayType;
use App\Enums\RequestStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function createLeave(array $overrides = []): Leave
{
    $start = now()->addWeekdays(2)->toDateString();

    return Leave::create(array_merge([
        'start_date' => $start,
        'end_date' => now()->addWeekdays(2)->addDays(2)->toDateString(),
        'day_type' => DayType::FULL_DAY->value,
        'total_days' => 3.0,
        'reason' => 'Test leave',
        'status' => RequestStatus::PENDING,
    ], $overrides));
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->for($this->company)->create();
    $this->department = Department::factory()->for($this->branch)->create();
    $this->position = Position::factory()->for($this->department)->create([
        'basic_salary' => 7_000_000,
    ]);
    Shift::factory()->create();

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

// ─── Auth Guards ────────────────────────────────────────────────────

test('store requires authentication', function () {
    $this->postJson('/api/v1/leave', [
        'leave_type_id' => 1,
        'start_date' => now()->addWeekdays(2)->toDateString(),
        'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
        'day_type' => DayType::FULL_DAY->value,
        'reason' => 'Test',
    ])->assertStatus(401);
});

test('index requires authentication', function () {
    $this->getJson('/api/v1/leave')
        ->assertStatus(401);
});

test('show requires authentication', function () {
    $leave = createLeave([
        'employee_id' => $this->employeeEmp->id,
        'leave_type_id' => $this->leaveType->id,
    ]);

    $this->getJson("/api/v1/leave/{$leave->id}")
        ->assertStatus(401);
});

test('destroy requires authentication', function () {
    $leave = createLeave([
        'employee_id' => $this->employeeEmp->id,
        'leave_type_id' => $this->leaveType->id,
    ]);

    $this->deleteJson("/api/v1/leave/{$leave->id}")
        ->assertStatus(401);
});

test('quota requires authentication', function () {
    $this->getJson('/api/v1/leave/quota')
        ->assertStatus(401);
});

// ─── Owner Access ───────────────────────────────────────────────────

test('employee can view own leave', function () {
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
            'reason' => 'Cuti tahunan.',
        ]);

    $leaveId = $createResponse->json('data.id');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/leave/{$leaveId}")
        ->assertOk()
        ->assertJsonPath('data.id', $leaveId);
});

test('employee cannot view another employee leave', function () {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('employee');
    $otherEmp = Employee::factory()->create([
        'user_id' => $otherUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    $leave = createLeave([
        'employee_id' => $otherEmp->id,
        'leave_type_id' => $this->leaveType->id,
    ]);

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/leave/{$leave->id}")
        ->assertStatus(403);
});

test('employee can delete own pending leave', function () {
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
            'reason' => 'Cuti tahunan.',
        ]);

    $leaveId = $createResponse->json('data.id');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/v1/leave/{$leaveId}")
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

test('employee index shows only own leaves', function () {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('employee');
    $otherEmp = Employee::factory()->create([
        'user_id' => $otherUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    createLeave([
        'employee_id' => $this->employeeEmp->id,
        'leave_type_id' => $this->leaveType->id,
    ]);
    createLeave([
        'employee_id' => $otherEmp->id,
        'leave_type_id' => $this->leaveType->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/leave');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1);
});

// ─── File Upload ─────────────────────────────────────────────────

test('leave store with proof file returns 201', function () {
    LeaveBalance::factory()->create([
        'employee_id' => $this->employeeEmp->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => now()->year,
        'quota' => 12,
    ]);

    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->post('/api/v1/leave', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addWeekdays(2)->toDateString(),
            'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
            'day_type' => DayType::FULL_DAY->value,
            'reason' => 'Cuti tahunan dengan bukti.',
            'proof_file' => $file,
        ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['proof_file']]);
});

test('leave store with invalid proof file type returns 422', function () {
    $file = UploadedFile::fake()->create('proof.txt', 100);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->post('/api/v1/leave', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addWeekdays(2)->toDateString(),
            'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
            'day_type' => DayType::FULL_DAY->value,
            'reason' => 'Cuti tahunan dengan bukti.',
            'proof_file' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['proof_file']);
});

test('leave store with proof file larger than 5MB returns 422', function () {
    $file = UploadedFile::fake()->image('proof.jpg')->size(6000);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->post('/api/v1/leave', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addWeekdays(2)->toDateString(),
            'end_date' => now()->addWeekdays(2)->addDays(1)->toDateString(),
            'day_type' => DayType::FULL_DAY->value,
            'reason' => 'Cuti tahunan dengan bukti.',
            'proof_file' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['proof_file']);
});
