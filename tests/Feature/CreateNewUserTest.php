<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Guard audit password-reset (2026-08-12): CreateNewUser wajib men-set
 * password_changed_at = now() — konsisten dengan ResetUserPassword/
 * UpdateUserPassword. Route publik /register mati (HRIS internal), jadi
 * action diuji langsung (dipakai juga oleh test/seed).
 */
it('creates a user with a fresh password_changed_at', function () {
    $user = app(CreateNewUser::class)->create([
        'name' => 'Internal User',
        'email' => 'internal-create@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->password_changed_at)->not->toBeNull()
        ->and($user->password_changed_at->diffInMinutes(now()))->toBeLessThan(5)
        ->and($user->group)->toBe('user');
});

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'dup-create@example.com']);

    app(CreateNewUser::class)->create([
        'name' => 'Dup User',
        'email' => 'dup-create@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
})->throws(ValidationException::class);
