<?php

use App\Livewire\Profile\TwoFactorAuthenticationForm;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

/**
 * Membuat user dengan 2FA aktif (secret base32 valid + recovery codes + confirmed).
 */
function twoFactorEnabledUser(array $attributes = []): User
{
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $suffix = Str::random(8);

    return User::factory()->create(array_merge([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode([
            "recovery-{$suffix}-111111",
            "recovery-{$suffix}-222222",
            "recovery-{$suffix}-333333",
            "recovery-{$suffix}-444444",
            "recovery-{$suffix}-555555",
            "recovery-{$suffix}-666666",
            "recovery-{$suffix}-777777",
            "recovery-{$suffix}-888888",
        ])),
        'two_factor_confirmed_at' => now(),
    ], $attributes));
}

/**
 * OTP 6 digit yang valid untuk user (dari secret yang tersimpan).
 */
function currentOtpFor(User $user): string
{
    return (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
}

test('login redirects to two-factor challenge when 2FA is confirmed', function () {
    config()->set('session.driver', 'database');

    $user = twoFactorEnabledUser();

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
    expect(session('login.id'))->toBe($user->getKey());
});

test('challenge page redirects to login when no challenged user exists', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

test('a valid OTP code authenticates the challenged user', function () {
    $user = twoFactorEnabledUser();

    $response = $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->post(route('two-factor.login'), ['code' => currentOtpFor($user)]);

    $this->assertAuthenticatedAs($user);
    expect(session('login.id'))->toBeNull();
});

test('2FA login redirects non-admin user to home, not admin dashboard', function () {
    $user = twoFactorEnabledUser();

    $response = $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->post(route('two-factor.login'), ['code' => currentOtpFor($user)]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('2FA login redirects admin user to admin dashboard', function () {
    $user = twoFactorEnabledUser(['group' => 'admin']);

    $response = $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->post(route('two-factor.login'), ['code' => currentOtpFor($user)]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('an invalid OTP code is rejected', function () {
    $user = twoFactorEnabledUser();

    $response = $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->from(route('two-factor.login'))
        ->post(route('two-factor.login'), ['code' => '000000']);

    $this->assertGuest();
    $response->assertSessionHasErrors('code');
    // User tetap ter-challenge (bisa coba lagi tanpa login ulang).
    expect(session('login.id'))->toBe($user->getKey());
});

test('a recovery code authenticates and is rotated', function () {
    $user = twoFactorEnabledUser();
    $code = $user->recoveryCodes()[0];

    $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->post(route('two-factor.login'), ['recovery_code' => $code]);

    $this->assertAuthenticatedAs($user);

    $fresh = $user->fresh();
    expect($fresh->recoveryCodes())->toHaveCount(8)
        ->and($fresh->recoveryCodes())->not->toContain($code);
});

test('an invalid recovery code is rejected', function () {
    $user = twoFactorEnabledUser();

    $response = $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->from(route('two-factor.login'))
        ->post(route('two-factor.login'), ['recovery_code' => 'not-a-valid-code']);

    $this->assertGuest();
    $response->assertSessionHasErrors('recovery_code');
});

test('recovery code of another user is rejected', function () {
    $userA = twoFactorEnabledUser();
    $userB = twoFactorEnabledUser();

    $response = $this->withSession(['login.id' => $userA->getKey(), 'login.remember' => false])
        ->from(route('two-factor.login'))
        ->post(route('two-factor.login'), ['recovery_code' => $userB->recoveryCodes()[0]]);

    $this->assertGuest();
    $response->assertSessionHasErrors('recovery_code');
});

test('two-factor challenge is rate limited to 5 attempts per minute', function () {
    $user = twoFactorEnabledUser();

    foreach (range(1, 5) as $i) {
        $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
            ->from(route('two-factor.login'))
            ->post(route('two-factor.login'), ['code' => '000000']);
    }

    $response = $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->post(route('two-factor.login'), ['code' => '000000']);

    $response->assertStatus(429);
    $this->assertGuest();
});

test('2FA enabled user can be confirmed with OTP via Livewire form', function () {
    $this->actingAs($user = User::factory()->create());
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(TwoFactorAuthenticationForm::class)
        ->call('enableTwoFactorAuthentication')
        ->assertSet('showingQrCode', true)
        ->assertSet('showingConfirmation', true)
        ->set('code', currentOtpFor($user->fresh()))
        ->call('confirmTwoFactorAuthentication');

    $fresh = $user->fresh();
    expect($fresh->two_factor_secret)->not->toBeNull()
        ->and($fresh->two_factor_confirmed_at)->not->toBeNull()
        ->and($fresh->recoveryCodes())->toHaveCount(8);
});

test('wrong OTP during confirmation is rejected and 2FA stays unconfirmed', function () {
    $this->actingAs($user = User::factory()->create());
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(TwoFactorAuthenticationForm::class)
        ->call('enableTwoFactorAuthentication')
        ->set('code', '000000')
        ->call('confirmTwoFactorAuthentication')
        ->assertHasErrors('code');

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull()
        ->and($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('unconfirmed 2FA is auto-disabled on form mount (confirm required)', function () {
    $this->actingAs($user = User::factory()->create([
        'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-111111'])),
        'two_factor_confirmed_at' => null,
    ]));

    Livewire::test(TwoFactorAuthenticationForm::class);

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('json login returns two_factor flag instead of logging in', function () {
    $user = twoFactorEnabledUser();

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJson(['two_factor' => true]);
    $this->assertGuest();
});

test('json two-factor challenge with valid OTP returns success', function () {
    $user = twoFactorEnabledUser();

    $this->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->postJson(route('two-factor.login'), ['code' => currentOtpFor($user)]);

    $this->assertAuthenticatedAs($user);
});
