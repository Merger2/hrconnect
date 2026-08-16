<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('users can log in on multiple devices simultaneously by default', function () {
    config()->set('session.driver', 'database');
    config()->set('auth.single_device', false); // default — keputusan Fikih 2026-08-16

    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Phone',
        'payload' => 'test',
        'last_activity' => now()->getTimestamp(),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // Login dari perangkat kedua (laptop) TETAP diterima — sesi lama tidak diblokir.
    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
    expect(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(2);
});

test('users are blocked from a second device only when AUTH_SINGLE_DEVICE is enabled', function () {
    config()->set('session.driver', 'database');
    config()->set('auth.single_device', true);

    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Existing Device',
        'payload' => 'test',
        'last_activity' => now()->getTimestamp(),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    expect(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(1);
});

test('expired sessions do not block a new login', function () {
    config()->set('session.driver', 'database');

    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Expired Device',
        'payload' => 'test',
        'last_activity' => now()->subMinutes(config('session.lifetime') + 5)->getTimestamp(),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
});
