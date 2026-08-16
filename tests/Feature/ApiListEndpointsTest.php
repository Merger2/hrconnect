<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Coverage gap P2: endpoint API list/master-data yang belum punya test:
 * /user, /reimbursements, /overtimes, /company/{hours,branches},
 * /branches, /divisions, /positions.
 */
function apiListUser(): array
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    return [$user, $employee];
}

// ─── /api/v1/user ───

test('authenticated user endpoint returns current user', function () {
    [$user] = apiListUser();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

test('authenticated user endpoint requires authentication', function () {
    $this->getJson('/api/v1/user')->assertUnauthorized();
});

// ─── /api/v1/reimbursements ───

test('reimbursements index requires authentication', function () {
    $this->getJson('/api/v1/reimbursements')->assertUnauthorized();
});

test('superadmin can list reimbursements', function () {
    $admin = User::factory()->admin(true)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/reimbursements')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data', 'meta' => ['total']]);
});

test('employee can list own reimbursements', function () {
    [$user] = apiListUser();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/reimbursements')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

// ─── /api/v1/overtimes ───

test('superadmin can list overtimes', function () {
    $admin = User::factory()->admin(true)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/overtimes')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data', 'meta' => ['total']]);
});

test('employee can submit overtime with supervisor approval chain', function () {
    $supervisorUser = User::factory()->create();
    $supervisor = Employee::factory()->create([
        'user_id' => $supervisorUser->id,
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'parent_id' => $supervisor->id,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/overtimes', [
        'date' => now()->toDateString(),
        'start_time' => '17:00',
        'end_time' => '20:00',
        'description' => 'Penyelesaian laporan bulanan pelanggan',
    ])->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'pending');
});

test('overtime store validates daily 4h limit', function () {
    [$user] = apiListUser();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/overtimes', [
        'date' => now()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '20:00',
        'description' => 'Lembur melebihi batas harian',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('end_time');
});

// ─── /api/v1/company ───

test('company hours returns working hours structure for employee company', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = Employee::factory()->create(['user_id' => $admin->id]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/company/hours')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.company_name', $employee->company->name)
        ->assertJsonStructure(['data' => ['working_hours' => ['start_time', 'end_time']]]);
});

test('company hours returns 404 when employee has no company', function () {
    // regresi 2026-08-16: sebelumnya HTTP selalu 200 + success:false karena
    // ApiResponse::format() return array mentah (status code tak diterapkan).
    // Contract fix: error path wajib 4xx (pola NotificationController).
    $admin = User::factory()->admin(true)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/company/hours')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Company not found for this user.');
});

test('company branches returns 404 when company has no branches', function () {
    $admin = User::factory()->admin(true)->create();
    Employee::factory()->create(['user_id' => $admin->id]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/company/branches')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'No branches found for this company.');
});

test('company branches returns geofence coordinates', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = Employee::factory()->create(['user_id' => $admin->id]);

    Branch::factory()->create([
        'company_id' => $employee->company_id,
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 50,
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/company/branches')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.branches')
        // json_encode((float) 50.0) default = 50 (tanpa JSON_PRESERVE_ZERO_FRACTION)
        ->assertJsonPath('data.branches.0.radius_meters', 50);
});

// ─── Master data ───

test('superadmin can list branches, divisions and positions', function () {
    $admin = User::factory()->admin(true)->create();

    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $division = Division::factory()->create(['branch_id' => $branch->id]);
    Position::factory()->create(['division_id' => $division->id]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/branches')->assertOk()->assertJsonPath('status', 'success');
    $this->getJson('/api/v1/divisions')->assertOk()->assertJsonPath('status', 'success');
    $this->getJson('/api/v1/positions')->assertOk()->assertJsonPath('status', 'success');
});

test('master data endpoints require view permissions', function () {
    [$user] = apiListUser();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/branches')->assertForbidden();
    $this->getJson('/api/v1/divisions')->assertForbidden();
    $this->getJson('/api/v1/positions')->assertForbidden();
});
