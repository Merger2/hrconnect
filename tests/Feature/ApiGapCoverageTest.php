<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function apiEmployeeUser(): array
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    return [$user, $employee];
}

// ─── AUTH REQUIRED ───

test('profile leaves and knowledge-base api require authentication', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();
    $this->getJson('/api/v1/leaves')->assertUnauthorized();
    $this->getJson('/api/v1/knowledge-base')->assertUnauthorized();
});

// ─── PROFILE ───

test('employee can fetch own profile with masked phone', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.full_name', $user->employee->full_name);
});

test('employee can update own profile phone', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', ['phone' => '081200000000'])
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect($user->employee->fresh()->phone)->toBe('081200000000');
});

test('employee can change password with correct current password', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/profile/password', [
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertOk();

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

test('change password rejects wrong current password', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(422);
});

// ─── LEAVES ───

test('employee can list own leaves', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/leaves')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

test('leave store rejects empty payload with validation errors', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/leaves', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['leave_type_id', 'start_date', 'end_date', 'day_type', 'reason']);
});

// ─── KNOWLEDGE BASE ───

test('knowledge base index requires view_knowledgebase permission', function () {
    [$user] = apiEmployeeUser();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/knowledge-base')->assertForbidden();
});

test('superadmin can list knowledge base documents', function () {
    $admin = User::factory()->admin(true)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/knowledge-base')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

test('knowledge base destroy requires manage_knowledgebase permission', function () {
    [$user] = apiEmployeeUser();

    $kb = KnowledgeBase::factory()->create([
        'title' => 'Pedoman Cuti',
        'source_document' => 'pedoman-cuti.pdf',
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/knowledge-base/'.$kb->id)->assertForbidden();

    expect(KnowledgeBase::find($kb->id))->not->toBeNull();
});
