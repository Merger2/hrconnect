<?php

use App\Ai\Agents\HrKnowledgeBaseAgent;
use App\Models\KnowledgeBase;
use App\Models\User;
use App\Services\EmbeddingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Embeddings::fake();
    HrKnowledgeBaseAgent::fake();

    $this->hrUser = User::factory()->create();
    $this->hrUser->assignRole('hr-manager');
    $this->hrToken = $this->hrUser->createToken('test')->plainTextToken;

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');
    $this->employeeToken = $this->employeeUser->createToken('test')->plainTextToken;
});

function makeKb(array $overrides = []): KnowledgeBase
{
    return KnowledgeBase::create(array_merge([
        'knowledgeable_type' => User::class,
        'knowledgeable_id' => User::factory()->create()->id,
        'title' => 'Test Document',
        'content' => 'Test chunk content',
        'category' => 'general',
        'status' => 'ready',
        'source_document' => 'kb_test.pdf',
    ], $overrides));
}

// ─── Chat ─────────────────────────────────────────────────────────

test('chat rejects question over max length', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->postJson('/api/v1/knowledgebase/chat', [
            'question' => str_repeat('a', 501),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['question']);
});

test('chat returns structured response', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->postJson('/api/v1/knowledgebase/chat', [
            'question' => 'Apa itu HRConnect?',
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => ['answer', 'sources', 'confidence', 'fallback', 'model'],
        ])
        ->assertJsonPath('status', 'success');
});

test('chat returns 403 for employee without view_knowledgebase', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->postJson('/api/v1/knowledgebase/chat', [
            'question' => 'Apa itu HRConnect?',
        ]);

    $response->assertStatus(403);
});

test('chat requires authentication', function () {
    $response = $this->postJson('/api/v1/knowledgebase/chat', [
        'question' => 'Apa itu HRConnect?',
    ]);

    $response->assertStatus(401);
});

// ─── Upload ───────────────────────────────────────────────────────

test('upload creates knowledge base record', function () {
    Queue::fake();
    $this->partialMock(EmbeddingService::class, function ($mock) {
        $mock->shouldReceive('extractTextFromPdf')->andReturn('Sample PDF content for testing.');
        $mock->shouldReceive('chunkText')->andReturn(['Sample chunk 1', 'Sample chunk 2']);
    });

    $file = UploadedFile::fake()->create('test.pdf', 512, 'application/pdf');

    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->post('/api/v1/knowledgebase', [
            'title' => 'Kebijakan HR 2026',
            'file' => $file,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['id', 'title', 'category', 'status', 'source_document']])
        ->assertJsonPath('data.title', 'Kebijakan HR 2026');
});

test('upload accepts category parameter', function () {
    Queue::fake();
    $this->partialMock(EmbeddingService::class, function ($mock) {
        $mock->shouldReceive('extractTextFromPdf')->andReturn('Sample PDF content for testing.');
        $mock->shouldReceive('chunkText')->andReturn(['Sample chunk 1', 'Sample chunk 2']);
    });

    $file = UploadedFile::fake()->create('test.pdf', 512, 'application/pdf');

    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->post('/api/v1/knowledgebase', [
            'title' => 'IT Guide',
            'category' => 'it_guide',
            'file' => $file,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.category', 'it_guide');
});

test('upload rejects non-PDF file', function () {
    $file = UploadedFile::fake()->create('test.txt', 512, 'text/plain');

    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->post('/api/v1/knowledgebase', [
            'title' => 'Test',
            'file' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('upload validates required fields', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->post('/api/v1/knowledgebase', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'file']);
});

test('upload requires manage_knowledgebase permission', function () {
    $file = UploadedFile::fake()->create('test.pdf', 512, 'application/pdf');

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->post('/api/v1/knowledgebase', [
            'title' => 'Test',
            'file' => $file,
        ]);

    $response->assertStatus(403);
});

test('upload requires authentication', function () {
    $file = UploadedFile::fake()->create('test.pdf', 512, 'application/pdf');

    $response = $this->post('/api/v1/knowledgebase', [
        'title' => 'Test',
        'file' => $file,
    ]);

    $response->assertStatus(401);
});

// ─── Delete ───────────────────────────────────────────────────────

test('delete removes knowledge base record', function () {
    $kb = makeKb(['knowledgeable_id' => $this->hrUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->deleteJson("/api/v1/knowledgebase/{$kb->id}");

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['deleted_count']]);
});

test('delete returns 404 for non-existent record', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->hrToken}")
        ->deleteJson('/api/v1/knowledgebase/99999');

    $response->assertStatus(404);
});

test('delete requires manage_knowledgebase permission', function () {
    $kb = makeKb(['knowledgeable_id' => $this->hrUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->deleteJson("/api/v1/knowledgebase/{$kb->id}");

    $response->assertStatus(403);
});

test('delete requires authentication', function () {
    $kb = makeKb(['knowledgeable_id' => $this->hrUser->id]);

    $response = $this->deleteJson("/api/v1/knowledgebase/{$kb->id}");

    $response->assertStatus(401);
});
