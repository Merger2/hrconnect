<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\User;
use App\Notifications\AttendanceReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Coverage gap P2: 8 console command tanpa test sama sekali.
 * Smoke test: command jalan tanpa exception pada data kosong/minimal.
 * (Logika batch detail command lain di-cover tidak langsung via test service.)
 */
test('attendance:send-reminders dry-run succeeds without notifications', function () {
    $this->artisan('attendance:send-reminders', ['--dry-run' => true])
        ->assertSuccessful();
});

test('attendance:auto-approve-wfa succeeds without data', function () {
    $this->artisan('attendance:auto-approve-wfa', ['--date' => now()->toDateString()])
        ->assertSuccessful();
});

test('attendance:detect-alpha succeeds with empty attendance', function () {
    $this->artisan('attendance:detect-alpha', ['--date' => now()->toDateString()])
        ->assertSuccessful();
});

test('attendance:detect-chronic-late succeeds with empty attendance', function () {
    $this->artisan('attendance:detect-chronic-late', ['--month' => now()->format('Y-m')])
        ->assertSuccessful();
});

test('attendance:detect-missed-clock succeeds with empty attendance', function () {
    $this->artisan('attendance:detect-missed-clock')
        ->assertSuccessful();
});

test('knowledgebase:index without flags warns but succeeds', function () {
    $this->artisan('knowledgebase:index')
        ->expectsOutputToContain('--all')
        ->assertSuccessful();
});

test('knowledgebase:index with unknown kb id fails cleanly', function () {
    $this->artisan('knowledgebase:index', ['--kb-id' => 999999])
        ->assertFailed();
});

test('leave:reset-quota succeeds without data', function () {
    $this->artisan('leave:reset-quota')
        ->assertSuccessful();
});

test('cache:warm succeeds', function () {
    $this->artisan('cache:warm', ['--year' => now()->year])
        ->assertSuccessful();
});

test('attendance:send-reminders sends notification to active employee without clock-in', function () {
    Notification::fake();

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $this->artisan('attendance:send-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($user, AttendanceReminder::class);
});
