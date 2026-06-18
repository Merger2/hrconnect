<?php

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create([
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
    ]);
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create();
    $shift = Shift::factory()->create();

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');

    $this->employee = Employee::factory()->create([
        'user_id' => $this->employeeUser->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'shift_id' => $shift->id,
    ]);
    $this->employee->forceFill(['pin' => '123456'])->save();

    $this->managerUser = User::factory()->create();
    $this->managerUser->assignRole('manager');
    Employee::factory()->create([
        'user_id' => $this->managerUser->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'parent_id' => null,
    ]);

    $this->token = $this->employeeUser->createToken('test')->plainTextToken;
});

function gpsData(): array
{
    return [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'accuracy' => 10,
    ];
}

// ═══════════════════════════════════════════════════════════════════════
// CLOCK-IN
// ═══════════════════════════════════════════════════════════════════════

test('clock-in with PIN succeeds and returns 201', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]));

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status', 'message', 'data' => ['id', 'employee_id', 'date', 'clock_in', 'is_wfa', 'status', 'verification_method'],
        ]);
});

test('clock-in with invalid PIN returns 422', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '000000',
        ]));

    $response->assertStatus(422);
});

test('clock-in with fake GPS returns 422', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'is_mocked' => true,
            'pin' => '123456',
        ]));

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('duplicate clock-in returns 409', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]))->assertStatus(201);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]));

    $response->assertStatus(409);
});

test('clock-in WFA with valid note succeeds', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'Bekerja dari rumah karena banjir di sekitar kantor hari ini.',
            'pin' => '123456',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.is_wfa', true);
});

test('clock-in WFA with short note returns 422', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'Pendek',
            'pin' => '123456',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['wfa_note']);
});

test('clock-in without employee record returns 404', function () {
    $noEmpUser = User::factory()->create();
    $noEmpUser->assignRole('employee');
    $token = $noEmpUser->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]));

    $response->assertStatus(404);
});

test('clock-in unauthorised returns 401', function () {
    $this->postJson('/api/v1/attendance/clock-in', [])
        ->assertStatus(401);
});

// ═══════════════════════════════════════════════════════════════════════
// CLOCK-OUT
// ═══════════════════════════════════════════════════════════════════════

test('clock-out succeeds after clock-in', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]))->assertStatus(201);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-out', array_merge(gpsData(), [
            'pin' => '123456',
        ]));

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status', 'message', 'data' => ['id', 'clock_in', 'clock_out', 'work_duration_hours'],
        ]);
});

test('clock-out without clock-in returns 409', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-out', array_merge(gpsData(), [
            'pin' => '123456',
        ]));

    $response->assertStatus(409);
});

test('clock-out with fake GPS returns 422', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]))->assertStatus(201);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-out', array_merge(gpsData(), [
            'is_mocked' => true,
            'pin' => '123456',
        ]));

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

// ═══════════════════════════════════════════════════════════════════════
// TODAY
// ═══════════════════════════════════════════════════════════════════════

test('today returns has_clocked_in=false before clock-in', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/attendance/today');

    $response->assertOk()
        ->assertJsonPath('data.has_clocked_in', false)
        ->assertJsonPath('data.has_clocked_out', false)
        ->assertJsonPath('data.attendance', null);
});

test('today returns has_clocked_in=true after clock-in', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', array_merge(gpsData(), [
            'pin' => '123456',
        ]))->assertStatus(201);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/attendance/today');

    $response->assertOk()
        ->assertJsonPath('data.has_clocked_in', true)
        ->assertJsonPath('data.has_clocked_out', false)
        ->assertJsonPath('data.attendance.clock_in', fn ($v) => $v !== null);
});

// ═══════════════════════════════════════════════════════════════════════
// INDEX
// ═══════════════════════════════════════════════════════════════════════

test('index returns paginated attendance list', function () {
    Attendance::factory()
        ->count(3)
        ->sequence(
            ['date' => now()->subDays(2)->toDateString()],
            ['date' => now()->subDays(1)->toDateString()],
            ['date' => now()->toDateString()],
        )
        ->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->employee->shift_id,
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/attendance');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

test('index filters by period', function () {
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'shift_id' => $this->employee->shift_id,
        'date' => now()->format('Y-m-d'),
    ]);

    $period = now()->format('Y-m');
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/attendance?period={$period}");

    $response->assertOk()
        ->assertJsonPath('meta.total', 1);
});

// ═══════════════════════════════════════════════════════════════════════
// WFA APPROVAL
// ═══════════════════════════════════════════════════════════════════════

test('manager can approve pending WFA for team member', function () {
    $this->employee->update(['parent_id' => $this->managerUser->employee->id]);

    $attend = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'Bekerja dari rumah karena banjir di sekitar kantor hari ini.',
            'pin' => '123456',
        ]);
    $attendanceId = $attend->json('data.id');

    $managerToken = $this->managerUser->createToken('test')->plainTextToken;

    Auth::forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$managerToken}")
        ->postJson("/api/v1/attendance/{$attendanceId}/approve-wfa", [
            'decision' => 'approve',
            'notes' => 'Disetujui',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status_wfa', 'approved');
});

test('manager can reject pending WFA', function () {
    $this->employee->update(['parent_id' => $this->managerUser->employee->id]);

    $attend = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'Bekerja dari rumah karena banjir di sekitar kantor hari ini.',
            'pin' => '123456',
        ]);
    $attendanceId = $attend->json('data.id');

    $managerToken = $this->managerUser->createToken('test')->plainTextToken;

    Auth::forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$managerToken}")
        ->postJson("/api/v1/attendance/{$attendanceId}/approve-wfa", [
            'decision' => 'reject',
            'notes' => 'Harap hadir ke kantor',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status_wfa', 'rejected');
});

test('non-manager cannot approve WFA', function () {
    $attend = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'Bekerja dari rumah karena banjir di sekitar kantor hari ini.',
            'pin' => '123456',
        ]);
    $attendanceId = $attend->json('data.id');

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/attendance/{$attendanceId}/approve-wfa", [
            'decision' => 'approve',
        ]);

    $response->assertStatus(403);
});

test('today returns 404 when user has no employee record', function () {
    $noEmpUser = User::factory()->create();
    $noEmpUser->assignRole('employee');
    $token = $noEmpUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/attendance/today')
        ->assertStatus(404);
});

test('index validates per_page max 100', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/attendance?per_page=200');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});
