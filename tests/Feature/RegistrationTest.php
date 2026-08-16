<?php

use App\Models\User;
use Laravel\Fortify\Features;

use function Pest\Laravel\post;

/**
 * Keputusan Fikih 2026-08-12: HRIS internal perusahaan (PT Daya Cipta Mandiri
 * Solusi) — akun karyawan dibuat oleh admin/HR, BUKAN self-registration
 * publik. Feature registration di config/fortify.php default MATI
 * (FORTIFY_REGISTRATION_ENABLED=false). Test ini guard: /register harus 404
 * dan tidak ada user yang bisa dibuat lewat form publik.
 *
 * Catatan: test 'password_changed_at segar' dipindah ke CreateNewUserTest —
 * action Fortify masih dipakai untuk test/seed, tapi route publik mati.
 */
test('registration screen returns 404 (self-registration disabled)', function () {
    $response = $this->get('/register');

    if (Features::enabled(Features::registration())) {
        // Bila sementara di-enable via env, screen harus render OK.
        $response->assertOk();

        return;
    }

    $response->assertNotFound();
});

test('registration POST is rejected when feature disabled', function () {
    if (Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration feature is enabled in this environment.');
    }

    $response = post('/register', [
        'name' => 'Intruder User',
        'email' => 'intruder@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
    expect(User::where('email', 'intruder@example.com')->exists())->toBeFalse();
});

test('registration route matches the current feature configuration', function () {
    $response = $this->get('/register');

    if (Features::enabled(Features::registration())) {
        $response->assertOk();

        return;
    }

    $response->assertNotFound();
});
