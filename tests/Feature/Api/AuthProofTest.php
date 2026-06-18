<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ─── Login with 2FA ──────────────────────────────────────────────

test('login returns challenge when user has 2FA enabled', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('secret123'),
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-1'])),
    ]);
    $user->assignRole('employee');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
        'device_name' => 'test-device',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.two_factor_required', true)
        ->assertJsonStructure(['data' => ['challenge_id']]);
});

// ─── 2FA Challenge ───────────────────────────────────────────────

test('2FA challenge succeeds with valid TOTP code', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('secret123'),
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-1'])),
    ]);
    $user->assignRole('employee');

    $challengeId = Str::random(40);
    Cache::put("2fa_challenge:{$challengeId}", [
        'user_id' => $user->id,
        'device_name' => 'test-device',
    ], now()->addMinutes(5));

    $validCode = app(Google2FA::class)->getCurrentOtp($secret);

    $response = $this->postJson('/api/v1/auth/2fa/challenge', [
        'challenge_id' => $challengeId,
        'code' => $validCode,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user']]);
});

test('2FA challenge returns 422 for expired challenge', function () {
    $response = $this->postJson('/api/v1/auth/2fa/challenge', [
        'challenge_id' => 'nonexistent-challenge',
        'code' => '123456',
    ]);

    $response->assertStatus(422);
});

test('2FA challenge validates required fields', function () {
    $response = $this->postJson('/api/v1/auth/2fa/challenge', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['challenge_id', 'code']);
});

test('2FA challenge returns 422 for invalid code', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-1'])),
    ]);

    $challengeId = Str::random(40);
    Cache::put("2fa_challenge:{$challengeId}", [
        'user_id' => $user->id,
        'device_name' => 'test-device',
    ], now()->addMinutes(5));

    $response = $this->postJson('/api/v1/auth/2fa/challenge', [
        'challenge_id' => $challengeId,
        'code' => '000000',
    ]);

    $response->assertStatus(422);
});

test('2FA challenge throttled at 5 requests per minute', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-1'])),
    ]);

    $challengeId = Str::random(40);
    Cache::put("2fa_challenge:{$challengeId}", [
        'user_id' => $user->id,
        'device_name' => 'test-device',
    ], now()->addMinutes(5));

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/2fa/challenge', [
            'challenge_id' => $challengeId,
            'code' => '000000',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/2fa/challenge', [
        'challenge_id' => $challengeId,
        'code' => '000000',
    ]);

    $response->assertStatus(429);
});

// ─── Forgot Password ─────────────────────────────────────────────

test('forgot-password validates email field', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('forgot-password validates email format', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
