<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * API Smoke Tests — endpoint registered + auth-protected.
 *
 * Tujuan: pastikan setiap endpoint:
 * 1. Terdaftar di routes/api.php (no 404)
 * 2. Auth-protected (return 401 tanpa token)
 * 3. Return JSON dengan format `{status, message?, data?, errors?}`
 *
 * Tidak test business logic mendalam (sudah covered di service-level tests).
 */

// ─── Public endpoints ─────────────────────────────────────────────────

test('GET /api/v1/health public, return ok status', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'services' => ['database', 'cache', 'queue', 'storage'],
        ]);
});

test('POST /api/v1/auth/login public, validation works', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

test('POST /api/v1/auth/login dengan kredensial salah return 422', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        'device_name' => 'Test Device',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('POST /api/v1/auth/login sukses return token + user payload', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'Password123!', // 'hashed' cast otomatis hash
    ]);
    $user->assignRole('employee');

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'Password123!',
        'device_name' => 'iPhone 15',
    ])->assertOk()
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'token',
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
            ],
        ]);
});

test('POST /api/v1/auth/forgot-password selalu return success (anti-enumeration)', function () {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com'])
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

// ─── Authenticated endpoints — 401 tanpa token ────────────────────────

dataset('protected_get_endpoints', [
    '/api/v1/user',
    '/api/v1/profile',
    '/api/v1/attendance/today',
    '/api/v1/attendance',
    '/api/v1/leave',
    '/api/v1/leave/quota',
    '/api/v1/overtime',
    '/api/v1/reimbursement',
    '/api/v1/approvals/pending',
    '/api/v1/payroll',
    '/api/v1/employees',
]);

test('protected GET endpoints return 401 tanpa token', function (string $endpoint) {
    $this->getJson($endpoint)->assertStatus(401);
})->with('protected_get_endpoints');

dataset('protected_post_endpoints', [
    '/api/v1/auth/logout',
    '/api/v1/auth/logout-all',
    '/api/v1/profile/change-password',
    '/api/v1/face/register',
    '/api/v1/face/verify',
    '/api/v1/attendance/clock-in',
    '/api/v1/attendance/clock-out',
    '/api/v1/leave',
    '/api/v1/overtime',
    '/api/v1/reimbursement',
]);

test('protected POST endpoints return 401 tanpa token', function (string $endpoint) {
    $this->postJson($endpoint, [])->assertStatus(401);
})->with('protected_post_endpoints');

dataset('protected_post_endpoints_more', [
    '/api/v1/knowledgebase',
    '/api/v1/knowledgebase/chat',
    '/api/v1/payroll/generate',
    '/api/v1/payroll/export/monthly',
    '/api/v1/payroll/export/1721-a1',
    '/api/v1/payroll/export/bpjs',
    '/api/v1/employees',
    '/api/v1/employees/terminate/contract-end',
]);

test('protected POST endpoints (more) return 401 tanpa token', function (string $endpoint) {
    $this->postJson($endpoint, [])->assertStatus(401);
})->with('protected_post_endpoints_more');

dataset('protected_put_endpoints', [
    '/api/v1/profile',
    '/api/v1/employees/0',
]);

test('protected PUT endpoints return 401 tanpa token', function (string $endpoint) {
    $this->putJson($endpoint, [])->assertStatus(401);
})->with('protected_put_endpoints');

dataset('protected_delete_endpoints', [
    '/api/v1/leave/0',
    '/api/v1/overtime/0',
    '/api/v1/reimbursement/0',
    '/api/v1/employees/0',
    '/api/v1/knowledgebase/0',
]);

test('protected DELETE endpoints return 401 tanpa token', function (string $endpoint) {
    $this->deleteJson($endpoint)->assertStatus(401);
})->with('protected_delete_endpoints');

// ─── /user endpoint dengan token ──────────────────────────────────────

test('GET /api/v1/user dengan token return user payload', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure([
            'status',
            'data' => [
                'id', 'name', 'email', 'two_factor_enabled', 'roles', 'permissions',
            ],
        ]);
});

// ─── Logout flow ──────────────────────────────────────────────────────

test('POST /api/v1/auth/logout revoke current token', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect($user->tokens()->count())->toBe(0);
});

test('POST /api/v1/auth/logout-all revoke semua token user', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $user->createToken('device-1');
    $user->createToken('device-2');
    $token = $user->createToken('current')->plainTextToken;

    expect($user->tokens()->count())->toBe(3);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout-all')
        ->assertOk()
        ->assertJsonPath('data.revoked_count', 3);

    expect($user->tokens()->count())->toBe(0);
});

// ─── Permission gating ────────────────────────────────────────────────

test('Employee tanpa permission process_payroll dapat 403 di POST /payroll/generate', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/payroll/generate', ['period' => '2026-05'])
        ->assertStatus(403);
});

test('Employee tanpa permission view_employees dapat 403 di GET /employees', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/employees')
        ->assertStatus(403);
});

// ─── Profile endpoints ────────────────────────────────────────────────

test('GET /api/v1/profile return 404 kalau user belum punya Employee record', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/profile')
        ->assertStatus(404)
        ->assertJsonPath('status', 'error');
});

test('POST /profile/change-password reject kalau current_password salah', function () {
    $user = User::factory()->create([
        'password' => 'CurrentPass123!', // hashed cast otomatis
    ]);
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/change-password', [
            'current_password' => 'WrongPass',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

test('POST /profile/change-password sukses + update password_changed_at', function () {
    $user = User::factory()->create([
        'password' => 'OldPass123!', // hashed cast otomatis
        'password_changed_at' => now()->subDays(30),
    ]);
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $oldChangedAt = $user->password_changed_at->copy();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/change-password', [
            'current_password' => 'OldPass123!',
            'password' => 'NewSecure456!',
            'password_confirmation' => 'NewSecure456!',
        ])
        ->assertOk();

    $user->refresh();
    expect($user->password_changed_at->toIso8601String())
        ->not->toBe($oldChangedAt->toIso8601String());
});

// ─── Attendance endpoints ─────────────────────────────────────────────

test('GET /attendance/today return 404 kalau user belum Employee', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/attendance/today')
        ->assertStatus(404);
});

// ─── Validation tests ────────────────────────────────────────────────

test('POST /face/register validasi embedding harus 128D', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/face/register', [
            'embedding' => array_fill(0, 64, 0.1), // wrong size
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['embedding']);
});

test('POST /attendance/clock-in validasi WFA wajib note >= 20 char', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'pendek',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['wfa_note']);
});

test('POST /attendance/clock-in validasi embedding harus 128D', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/attendance/clock-in', [
            'is_wfa' => true,
            'wfa_note' => 'Melakukan pekerjaan dari rumah dengan koneksi stabil.',
            'embedding' => array_fill(0, 64, 0.1),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['embedding']);
});

test('POST /attendance/clock-out validasi embedding harus numerik', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $embedding = array_fill(0, 128, 0.1);
    $embedding[0] = 'invalid';

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/attendance/clock-out', [
            'embedding' => $embedding,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['embedding.0']);
});

test('POST /leave validasi field wajib', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'leave_type_id', 'start_date', 'end_date', 'day_type', 'reason',
        ]);
});

test('POST /overtime tanpa Employee record return 404', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');
    $token = $user->createToken('test')->plainTextToken;

    $tomorrow = now()->addDay()->toDateString();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/overtime', [
            'date' => $tomorrow,
            'start_time' => '17:00',
            'end_time' => '20:00',
            'description' => 'Test overtime',
        ])
        ->assertStatus(404);
});

test('POST /payroll/generate validasi format periode', function () {
    $user = User::factory()->create();
    $user->assignRole('finance');
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/payroll/generate', [
            'period' => 'invalid-format',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['period']);
});
