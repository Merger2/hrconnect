<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function sseNotification(User $user, array $data = []): DatabaseNotification
{
    return DatabaseNotification::create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\\Notifications\\PayrollPublished',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => array_merge([
            'title' => 'Payroll Agustus',
            'message' => 'Slip gaji sudah terbit.',
            'type' => 'payroll',
        ], $data),
    ]);
}

test('sse notifications endpoint returns unread notifications after since timestamp', function () {
    $user = User::factory()->create();

    $unread = sseNotification($user);
    sseNotification($user); // second unread

    // Notification sudah dibaca — tidak boleh muncul.
    $read = sseNotification($user);
    $read->markAsRead();

    $this->actingAs($user)
        ->get(route('sse.notifications', ['since' => now()->subHour()->toIso8601String()]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $unread->id)
        ->assertJsonPath('data.0.title', 'Payroll Agustus')
        ->assertJsonPath('data.0.message', 'Slip gaji sudah terbit.');
});

test('sse notifications endpoint filters out notifications older than since', function () {
    $user = User::factory()->create();

    $old = sseNotification($user);
    // Simulasikan notifikasi lama (dibuat sebelum `since`).
    DB::table('notifications')
        ->where('id', $old->id)
        ->update(['created_at' => now()->subDay()]);

    $this->actingAs($user)
        ->get(route('sse.notifications', ['since' => now()->subMinutes(30)->toIso8601String()]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('sse notifications endpoint requires authentication', function () {
    $this->get(route('sse.notifications'))
        ->assertRedirect(route('login'));
});
