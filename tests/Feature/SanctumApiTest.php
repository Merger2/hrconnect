<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('User dapat create Sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    expect($token)->toBeString();
    expect(strlen($token))->toBeGreaterThan(40); // format: {id}|{plain}
    expect($token)->toContain('|');
});

test('GET /api/v1/user tanpa token return 401', function () {
    $this->getJson('/api/v1/user')->assertStatus(401);
});

test('GET /api/v1/user dengan Bearer token valid return 200 + user info', function () {
    $user = User::factory()->create([
        'name' => 'Sanctum Tester',
        'email' => 'tester@example.com',
    ]);
    $user->assignRole('employee');

    $token = $user->createToken('pwa-device')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ])->getJson('/api/v1/user');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => 'Sanctum Tester',
                'email' => 'tester@example.com',
            ],
        ])
        ->assertJsonStructure([
            'status',
            'data' => [
                'id',
                'name',
                'email',
                'roles',
                'permissions',
            ],
        ]);
});

test('GET /api/v1/user dengan token invalid return 401', function () {
    $this->withHeaders([
        'Authorization' => 'Bearer invalid-token-xxx',
        'Accept' => 'application/json',
    ])->getJson('/api/v1/user')
        ->assertStatus(401);
});

test('User token return roles dan permissions di response', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ])->getJson('/api/v1/user');

    $response->assertJson([
        'data' => [
            'roles' => ['manager'],
        ],
    ]);

    $permissions = $response->json('data.permissions');
    expect($permissions)->toContain('approve_leaves_l1');
    expect($permissions)->toContain('approve_wfa');
    expect($permissions)->not->toContain('process_payroll');
});

test('Sanctum config: token never expire (expiration null)', function () {
    expect(config('sanctum.expiration'))->toBeNull();
});

test('User dapat revoke specific token', function () {
    $user = User::factory()->create();
    $token1 = $user->createToken('device-1');
    $token2 = $user->createToken('device-2');

    expect($user->tokens()->count())->toBe(2);

    $token1->accessToken->delete();

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('device-2');
});

test('API 2FA challenge rejects correctly shaped but invalid TOTP code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['ABCDEFGH'])),
    ]);

    $challengeId = 'test-challenge-id';
    cache()->put("2fa_challenge:{$challengeId}", [
        'user_id' => $user->id,
        'device_name' => 'test-device',
    ], now()->addMinutes(5));

    $this->postJson('/api/v1/auth/2fa/challenge', [
        'challenge_id' => $challengeId,
        'code' => '123456',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['code']);

    expect($user->tokens()->count())->toBe(0);
});

test('API 2FA challenge accepts recovery code once and rotates it', function () {
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['ABCDEFGH'])),
    ]);

    $challengeId = 'test-recovery-challenge-id';
    cache()->put("2fa_challenge:{$challengeId}", [
        'user_id' => $user->id,
        'device_name' => 'test-device',
    ], now()->addMinutes(5));

    $this->postJson('/api/v1/auth/2fa/challenge', [
        'challenge_id' => $challengeId,
        'code' => 'ABCDEFGH',
    ])->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['token']]);

    expect($user->fresh()->recoveryCodes())->not->toContain('ABCDEFGH');
    expect($user->tokens()->count())->toBe(1);
});
