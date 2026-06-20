<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ─── Password Change Resets Expiry ─────────────────────────────────

test('change password via API updates password_changed_at', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
        'password_changed_at' => now()->subDays(100),
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/change-password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertOk();

    $user->refresh();
    expect($user->password_changed_at->timestamp)->toBeGreaterThan(now()->subMinute()->timestamp);
});

test('change password clears expired status for dashboard access', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
        'password_changed_at' => now()->subDays(100),
    ]);
    $token = $user->createToken('test')->plainTextToken;

    // Before: expired → dashboard redirects
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('security.edit'));

    // Change password
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/change-password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertOk();

    // After: password reset → dashboard accessible
    $this->actingAs($user->fresh())
        ->get('/dashboard')
        ->assertOk();
});

// ─── Registration Sets password_changed_at (CreateNewUser fix) ────────

test('forgot-password reset sets password_changed_at', function () {
    // Test ResetUserPassword action directly
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
        'password_changed_at' => now()->subDays(100),
    ]);

    $action = app(ResetsUserPasswords::class);
    $action->reset($user, ['password' => 'NewPassword456!', 'password_confirmation' => 'NewPassword456!']);

    $user->refresh();
    expect($user->password_changed_at->timestamp)->toBeGreaterThan(now()->subMinute()->timestamp);
});
