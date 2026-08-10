<?php

use App\Livewire\User\CollaborationInbox;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function collabUser(): array
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Employee::factory()->create(['user_id' => $user->id]);

    $thread = ChatThread::create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'type' => 'direct',
        'title' => 'General',
    ]);
    $thread->members()->attach($user->id);

    return [$user, $thread, $company];
}

test('collaboration inbox renders for employee user with visible thread', function () {
    [$user, $thread] = collabUser();

    Livewire::actingAs($user)
        ->test(CollaborationInbox::class)
        ->assertOk()
        ->assertSet('selectedThreadId', (string) $thread->id);
});

test('collaboration inbox forbids user without employee record', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CollaborationInbox::class)
        ->assertForbidden();
});

test('user can select a visible thread', function () {
    [$user, $thread, $company] = collabUser();

    $second = ChatThread::create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'type' => 'direct',
        'title' => 'Project',
    ]);
    $second->members()->attach($user->id);

    Livewire::actingAs($user)
        ->test(CollaborationInbox::class)
        ->call('selectThread', $second->id)
        ->assertSet('selectedThreadId', (string) $second->id);
});

test('selecting a non-visible thread returns 404', function () {
    $otherCompany = Company::factory()->create();
    $other = User::factory()->create(['company_id' => $otherCompany->id]);
    $otherEmployee = Employee::factory()->create(['user_id' => $other->id]);
    $hidden = ChatThread::create([
        'company_id' => $otherCompany->id,
        'created_by' => $other->id,
        'type' => 'direct',
        'title' => 'Private',
    ]);
    $hidden->members()->attach($other->id);

    [$user] = collabUser();

    Livewire::actingAs($user)
        ->test(CollaborationInbox::class)
        ->call('selectThread', $hidden->id)
        ->assertStatus(404);
});

test('user can post message to a visible thread', function () {
    [$user, $thread] = collabUser();

    Livewire::actingAs($user)
        ->test(CollaborationInbox::class)
        ->set('selectedThreadId', (string) $thread->id)
        ->set('messageBody', 'Halo tim, ada update terbaru')
        ->call('postMessage')
        ->assertHasNoErrors()
        ->assertSet('messageBody', '');

    $message = ChatMessage::query()
        ->where('chat_thread_id', $thread->id)
        ->where('user_id', $user->id)
        ->first();

    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('Halo tim, ada update terbaru');
});

test('posting empty message without file returns validation error', function () {
    [$user, $thread] = collabUser();

    Livewire::actingAs($user)
        ->test(CollaborationInbox::class)
        ->set('selectedThreadId', (string) $thread->id)
        ->set('messageBody', '')
        ->call('postMessage')
        ->assertHasErrors(['messageBody']);
});
