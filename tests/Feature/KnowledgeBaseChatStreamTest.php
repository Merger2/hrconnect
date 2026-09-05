<?php

declare(strict_types=1);

use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * Endpoint SSE `/knowledge-base/chat/stream` — transport streaming jawaban KB
 * chat ke browser (pola ship-ai-with-laravel). Jalur Gemini SUKSES diverifikasi
 * dengan API NYATA di E2E kb-chat.spec.ts; feature test ini memakai Http::fake
 * 401 sehingga stream mengambil jalur fallback pg_trgm — deterministic, tanpa
 * API key, tapi tetap mengeksekusi endpoint + frame SSE yang sama.
 */
test('SSE endpoint denies user without view_knowledgebase', function () {
    $user = User::factory()->create(); // tanpa role

    $this->actingAs($user)
        ->post('/knowledge-base/chat/stream', ['question' => 'Apa itu cuti tahunan?'])
        ->assertForbidden();
});

test('SSE endpoint validates question length', function () {
    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $this->actingAs($employee)
        ->post('/knowledge-base/chat/stream', ['question' => 'abc'])
        ->assertSessionHasErrors(['question']);
});

test('SSE endpoint streams fallback frames terminated by DONE', function () {
    // Gemini down (401) → chatStream fallback ke pg_trgm → frame text + meta + [DONE].
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 401)]);

    KnowledgeBase::create([
        'title' => 'Cuti Tahunan',
        'content' => 'Cuti tahunan diberikan 12 hari kerja per tahun. Pengajuan cuti dilakukan minimal 3 hari sebelum tanggal cuti melalui menu pengajuan cuti.',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\\Models\\User',
        'knowledgeable_id' => 0,
    ]);

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $response = $this->actingAs($employee)
        ->post('/knowledge-base/chat/stream', ['question' => 'Apa itu cuti tahunan?']);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');

    $body = $response->streamedContent();

    expect($body)->toContain('data: [DONE]');
    expect($body)->toContain('data: ');

    // Frame teks jawaban + meta fallback hadir (bukan silent empty stream).
    expect($body)->toContain('Cuti tahunan');
    expect($body)->toContain('"fallback":true');
    expect($body)->toContain('"sources"');
});
