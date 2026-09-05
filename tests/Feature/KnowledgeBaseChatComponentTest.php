<?php

declare(strict_types=1);

use App\Livewire\User\KnowledgeBaseChat;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * Coverage: halaman KB Chat kini SHELL — seluruh logika chat (kirim pesan,
 * streaming jawaban, sources) dijalankan client-side lewat Alpine + fetch ke
 * endpoint SSE `knowledge-base.chat.stream` (pola ship-ai-with-laravel),
 * BUKAN lewat action Livewire. Komponen hanya otorisasi + prefill ?q= +
 * welcome message. Alur stream di-cover oleh KnowledgeBaseServiceTest
 * (chatStream) + E2E kb-chat.spec.ts (API Gemini nyata) + feature test
 * endpoint SSE (fallback pg_trgm deterministic).
 */
test('chat shell renders for authorized user', function () {
    $admin = User::factory()->admin(true)->create();

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->assertOk()
        ->assertSet('welcomeMessage', fn ($value) => str_contains($value, 'asisten AI'))
        ->assertSet('initialQuestion', '');
});

test('chat shell denies user without view_knowledgebase', function () {
    // User tanpa role apa pun → tidak punya permission view_knowledgebase.
    // (Role employee JUST punya view_knowledgebase — lihat
    // RoleAndPermissionSeeder::employeePermissions.)
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KnowledgeBaseChat::class)
        ->assertForbidden();
});

test('chat shell prefills initial question from ?q=', function () {
    // Route /knowledge-base/chat berada di grup middleware `user` (butuh
    // group 'user') — pakai employee role (punya view_knowledgebase).
    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $this->actingAs($employee)
        ->get('/knowledge-base/chat?q=Apa+itu+cuti+tahunan')
        ->assertOk()
        ->assertSee('data-kb-initial="Apa itu cuti tahunan"', false);
});

test('chat shell renders welcome bubble markup for Alpine init', function () {
    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $html = $this->actingAs($employee)
        ->get('/knowledge-base/chat')
        ->getContent();

    expect($html)->toContain('x-data="kbChat()"');
    expect($html)->toContain('data-kb-welcome=');
    expect($html)->toContain('/knowledge-base/chat/stream');
});
