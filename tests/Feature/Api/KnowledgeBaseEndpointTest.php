<?php

use App\Ai\Agents\HrKnowledgeBaseAgent;
use App\Models\KnowledgeBase;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Embeddings::fake();
    HrKnowledgeBaseAgent::fake();
});

/**
 * Smoke tests untuk KnowledgeBase API endpoints.
 *
 * Cover: 401 protection, 403 permission gating, 200 happy path mock mode.
 * Real RAG flow ter-cover via tinker manual dan Pest dengan API key real.
 */
test('POST /knowledgebase/chat tanpa auth return 401', function () {
    $this->postJson('/api/v1/knowledgebase/chat', [
        'question' => 'Berapa cuti tahunan saya?',
    ])->assertStatus(401);
});

test('POST /knowledgebase/chat employee tanpa permission view_knowledgebase return 403', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/knowledgebase/chat', [
            'question' => 'Berapa cuti tahunan saya?',
        ])
        ->assertStatus(403);
});

test('POST /knowledgebase/chat hr-manager dengan question valid return 200', function () {
    $user = User::factory()->create();
    $user->assignRole('hr-manager');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/knowledgebase/chat', [
            'question' => 'Berapa cuti tahunan karyawan tetap di PT 521?',
        ])
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => [
                'answer',
                'sources',
                'confidence',
                'fallback',
                'model',
            ],
        ]);
});

test('POST /knowledgebase/chat reject pertanyaan kurang dari 5 karakter', function () {
    $user = User::factory()->create();
    $user->assignRole('hr-manager');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/knowledgebase/chat', ['question' => 'hi'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['question']);
});

test('POST /knowledgebase tanpa permission manage_knowledgebase return 403', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/knowledgebase', [
            'title' => 'HR Handbook 2026',
        ])
        ->assertStatus(403);
});

test('POST /knowledgebase validasi title required + file PDF', function () {
    $user = User::factory()->create();
    $user->assignRole('hr-manager');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/knowledgebase', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'file']);
});

test('DELETE /knowledgebase/{id} tanpa permission return 403', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    // Buat KB record manual untuk delete target
    $kb = KnowledgeBase::create([
        'knowledgeable_type' => User::class,
        'knowledgeable_id' => $user->id,
        'title' => 'Test KB',
        'content' => 'Test content',
        'category' => 'general',
        'status' => 'ready',
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/knowledgebase/{$kb->id}")
        ->assertStatus(403);
});
