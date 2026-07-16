<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create();

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');

    $this->company = $company;
    $this->branch = $branch;
    $this->department = $department;
    $this->position = $position;

    $this->employee = Employee::factory()->create([
        'user_id' => $this->employeeUser->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
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

function validEmbedding(): array
{
    return array_fill(0, 128, 0.01);
}

// ═══════════════════════════════════════════════════════════════════════
// REGISTER
// ═══════════════════════════════════════════════════════════════════════

test('register face with valid embedding returns 200', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/face/register', [
            'embedding' => validEmbedding(),
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status', 'message', 'data' => ['employee_id', 'face_registered_at'],
        ]);
});

test('register face with wrong-size embedding returns 422', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/face/register', [
            'embedding' => array_fill(0, 64, 0.01),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['embedding']);
});

test('register face without auth returns 401', function () {
    $this->postJson('/api/v1/face/register', [
        'embedding' => validEmbedding(),
    ])->assertStatus(401);
});

test('register face with GPS coordinates succeeds', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/face/register', array_merge([
            'embedding' => validEmbedding(),
        ], [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]));

    $response->assertOk()
        ->assertJsonPath('status', 'success');
});

// ═══════════════════════════════════════════════════════════════════════
// VERIFY
// ═══════════════════════════════════════════════════════════════════════

test('verify face with valid embedding returns 200', function () {
    $this->employee->faceDescriptors()->create([
        'embedding' => '['.implode(',', validEmbedding()).']',
        'is_active' => true,
    ]);

    $this->mock(FaceRecognitionService::class)
        ->shouldReceive('hasFaceEnrolled')
        ->andReturn(true)
        ->shouldReceive('verifyFace')
        ->andReturn([
            'valid' => true,
            'similarity_percentage' => 98.5,
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/face/verify', [
            'embedding' => validEmbedding(),
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.valid', true)
        ->assertJsonStructure([
            'status', 'message', 'data' => ['valid', 'similarity_percentage'],
        ]);
});

test('verify face without prior registration returns 422', function () {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('employee');
    Employee::factory()->create([
        'user_id' => $otherUser->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);
    $otherToken = $otherUser->createToken('test')->plainTextToken;

    $this->mock(FaceRecognitionService::class)
        ->shouldReceive('hasFaceEnrolled')
        ->andReturn(false);

    $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->postJson('/api/v1/face/verify', [
            'embedding' => validEmbedding(),
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('verify face without auth returns 401', function () {
    $this->postJson('/api/v1/face/verify', [
        'embedding' => validEmbedding(),
    ])->assertStatus(401);
});

test('verify face when user has no employee record returns 404', function () {
    $noEmpUser = User::factory()->create();
    $noEmpUser->assignRole('employee');
    $token = $noEmpUser->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/face/verify', [
            'embedding' => validEmbedding(),
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('status', 'error');
});
