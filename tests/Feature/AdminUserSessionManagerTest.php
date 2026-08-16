<?php

use App\Livewire\Admin\UserSessionManager;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiTokenPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createDatabaseSessionFor(User $user, array $overrides = []): string
{
    $sessionId = (string) ($overrides['id'] ?? Str::uuid());

    DB::table('sessions')->insert(array_merge([
        'id' => $sessionId,
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Stuck Browser Session',
        'payload' => 'test',
        'last_activity' => now()->getTimestamp(),
    ], $overrides, ['id' => $sessionId]));

    return $sessionId;
}

test('superadmin can disconnect a stuck active user session', function () {
    config()->set('session.driver', 'database');

    $superadmin = User::factory()->admin(true)->create();
    $user = User::factory()->create([
        'name' => 'Blocked User',
        'email' => 'blocked-user@example.com',
    ]);
    $sessionId = createDatabaseSessionFor($user);

    Livewire::actingAs($superadmin)
        ->test(UserSessionManager::class)
        ->assertSee('Blocked User')
        ->call('selectUser', $user->id)
        ->assertSee('Stuck Browser Session')
        ->call('forgetSession', $sessionId)
        ->assertDispatched('banner-message');

    expect(DB::table('sessions')->where('id', $sessionId)->exists())->toBeFalse();

    auth()->logout();

    $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));
});

test('authorized admin can clear user sessions but cannot clear superadmin sessions', function () {
    config()->set('session.driver', 'database');

    $role = Role::query()->create([
        'name' => 'Session Admin',
        'slug' => 'session_admin',
        'permission_keys' => ['admin.user_sessions.manage'],
    ]);

    $admin = User::factory()->admin()->create();
    $admin->roles()->sync([$role->id]);

    $user = User::factory()->create();
    $superadmin = User::factory()->admin(true)->create();
    createDatabaseSessionFor($user);
    $superadminSessionId = createDatabaseSessionFor($superadmin);

    Livewire::actingAs($admin)
        ->test(UserSessionManager::class)
        ->call('selectUser', $user->id)
        ->call('forgetAllSessions')
        ->assertDispatched('banner-message');

    expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', $superadminSessionId)->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(UserSessionManager::class)
        ->call('selectUser', $superadmin->id)
        ->assertForbidden();
});

test('superadmin can inspect and revoke user api tokens from session management', function () {
    config()->set('session.driver', 'database');

    $superadmin = User::factory()->admin(true)->create();
    $user = User::factory()->create([
        'name' => 'Native Device User',
        'email' => 'native-user@example.com',
    ]);
    $token = $user->createToken('iOS Native Scanner', [
        ApiTokenPermission::DEVICE_OFFLINE_ATTENDANCE,
        ApiTokenPermission::DEVICE_PERMISSIONS,
    ])->accessToken;
    $token->forceFill(['last_used_at' => now()->subMinutes(5)])->save();

    Livewire::actingAs($superadmin)
        ->test(UserSessionManager::class)
        ->assertSee('Native Device User')
        ->assertSee('API: 1')
        ->call('selectUser', $user->id)
        ->assertSee('iOS Native Scanner')
        ->assertSee(ApiTokenPermission::DEVICE_OFFLINE_ATTENDANCE)
        ->call('revokeApiToken', (string) $token->id)
        ->assertDispatched('banner-message');

    expect($user->tokens()->whereKey($token->id)->exists())->toBeFalse();
});

test('authorized admin cannot inspect or revoke superadmin api tokens', function () {
    config()->set('session.driver', 'database');

    $role = Role::query()->create([
        'name' => 'Token Session Admin',
        'slug' => 'token_session_admin',
        'permission_keys' => ['admin.user_sessions.manage'],
    ]);

    $admin = User::factory()->admin()->create();
    $admin->roles()->sync([$role->id]);
    $superadmin = User::factory()->admin(true)->create();
    $token = $superadmin->createToken('Superadmin Native Token', [
        ApiTokenPermission::DEVICE_OFFLINE_ATTENDANCE,
    ])->accessToken;

    Livewire::actingAs($admin)
        ->test(UserSessionManager::class)
        ->call('selectUser', $superadmin->id)
        ->assertForbidden();

    expect($superadmin->tokens()->whereKey($token->id)->exists())->toBeTrue();
});

test('regular users cannot access admin user session management', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.user-sessions'))
        ->assertForbidden();
});
