<?php

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;

use function Pest\Laravel\post;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    if (Features::enabled(Features::registration())) {
        $response->assertOk();

        return;
    }

    $response->assertNotFound();
});

test('registration route matches the current feature configuration', function () {
    $response = $this->get('/register');

    expect(Features::enabled(Features::registration()))->toBeTrue();
    $response->assertOk();
});

test('new users can register', function () {
    expect(Features::enabled(Features::registration()))->toBeTrue();

    Notification::fake();

    $response = post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, QueuedVerifyEmail::class);

    expect(Route::has('verification.notice'))->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('verification.notice'));
});

test('newly registered users can verify their email and then log in', function () {
    expect(Features::enabled(Features::registration()))->toBeTrue();

    Notification::fake();

    $registerResponse = post('/register', [
        'name' => 'Verify User',
        'email' => 'verify@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $user = User::where('email', 'verify@example.com')->firstOrFail();

    Notification::assertSentTo($user, QueuedVerifyEmail::class);
    $registerResponse->assertRedirect(route('verification.notice'));

    auth()->logout();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertRedirect(config('fortify.home').'?verified=1');

    auth()->logout();

    $loginResponse = post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user->fresh());
    $loginResponse->assertRedirect(route('home'));
});

test('registration rejects invalid email', function () {
    expect(Features::enabled(Features::registration()))->toBeTrue();

    $response = post('/register', [
        'name' => 'Invalid User',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});
