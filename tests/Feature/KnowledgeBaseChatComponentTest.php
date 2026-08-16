<?php

declare(strict_types=1);

use App\Livewire\User\KnowledgeBaseChat;
use App\Models\User;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Coverage gap: Livewire KnowledgeBaseChat (kirim pertanyaan, citation,
 * error handling). KnowledgeBaseService sendiri sudah ter-cover tebal di
 * Unit/Services/KnowledgeBaseServiceTest + KbEvalDatasetTest + AiCostGuardTest
 * — komponen ini hanya menguji wiring-nya (service di-mock).
 */
test('chat renders welcome message for authorized user', function () {
    $admin = User::factory()->admin(true)->create();

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->assertOk()
        ->assertCount('messages', 1)
        ->assertSet('messages.0.role', 'assistant')
        ->assertSet('messages.0.is_welcome', true);
});

test('chat rejects question shorter than 5 characters', function () {
    $admin = User::factory()->admin(true)->create();

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->set('question', 'abc')
        ->call('sendMessage')
        ->assertHasErrors(['question']);
});

test('sendMessage appends user message and streaming placeholder', function () {
    $admin = User::factory()->admin(true)->create();

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->set('question', 'Apa itu jam kerja?')
        ->call('sendMessage')
        ->assertHasNoErrors()
        ->assertCount('messages', 3)
        ->assertSet('messages.1.role', 'user')
        ->assertSet('messages.1.text', 'Apa itu jam kerja?')
        ->assertSet('messages.2.is_streaming', true)
        ->assertSet('isLoading', true)
        ->assertSet('question', '');
});

test('ask (suggestion chip) kirim pertanyaan + jawaban + sources dalam satu request', function () {
    $admin = User::factory()->admin(true)->create();

    $this->mock(KnowledgeBaseService::class, function ($mock) {
        $mock->shouldReceive('chatStream')
            ->once()
            ->andReturnUsing(function () {
                yield ['text' => 'Cuti tahunan 12 hari per tahun.', 'sources' => [['title' => 'Cuti Tahunan']]];
            });
    });

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->call('ask', 'Apa itu cuti tahunan?')
        ->assertHasNoErrors()
        ->assertSet('isLoading', false)
        ->assertSet('question', '')
        ->assertCount('messages', 3)
        ->assertSet('messages.1.role', 'user')
        ->assertSet('messages.1.text', 'Apa itu cuti tahunan?')
        ->assertSet('messages.2.is_streaming', false)
        ->assertSet('messages.2.text', 'Cuti tahunan 12 hari per tahun.')
        ->assertSet('messages.2.sources.0.title', 'Cuti Tahunan');
});

test('ask menolak pertanyaan pendek (< 5 char)', function () {
    $admin = User::factory()->admin(true)->create();

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->call('ask', 'abc')
        ->assertHasErrors(['question']);
});

test('processAnswer fills assistant answer with sources', function () {
    $admin = User::factory()->admin(true)->create();

    $this->mock(KnowledgeBaseService::class, function ($mock) {
        $mock->shouldReceive('chatStream')
            ->once()
            ->andReturnUsing(function () {
                yield ['text' => 'Jam kerja standar 08:00-17:00 WIB.', 'sources' => [['title' => 'Peraturan Perusahaan']]];
            });
    });

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->set('question', 'Apa itu jam kerja?')
        ->call('sendMessage')
        ->call('processAnswer')
        ->assertSet('isLoading', false)
        ->assertSet('messages.2.is_streaming', false)
        ->assertSet('messages.2.text', 'Jam kerja standar 08:00-17:00 WIB.')
        ->assertSet('messages.2.sources.0.title', 'Peraturan Perusahaan');
});

test('processAnswer surfaces error state when service fails', function () {
    $admin = User::factory()->admin(true)->create();

    $this->mock(KnowledgeBaseService::class, function ($mock) {
        $mock->shouldReceive('chatStream')
            ->once()
            ->andThrow(new RuntimeException('AI provider down'));
    });

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->set('question', 'Apa itu jam kerja?')
        ->call('sendMessage')
        ->call('processAnswer')
        ->assertSet('isLoading', false)
        ->assertSet('messages.2.error', true)
        ->assertSet('messages.2.is_streaming', false);
});

test('startNewChat resets conversation to welcome message', function () {
    $admin = User::factory()->admin(true)->create();

    Livewire::actingAs($admin)
        ->test(KnowledgeBaseChat::class)
        ->set('question', 'Apa itu jam kerja?')
        ->call('sendMessage')
        ->call('startNewChat')
        ->assertCount('messages', 1)
        ->assertSet('conversationId', null)
        ->assertSet('question', '')
        ->assertSet('messages.0.is_welcome', true);
});
