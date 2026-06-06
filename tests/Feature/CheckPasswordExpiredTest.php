<?php

use App\Models\CompanySetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * CheckPasswordExpired middleware (CAT-005).
 *
 * Default 90 hari, configurable via CompanySetting key 'password_expiry_days'.
 * Middleware diaktifkan via alias 'password.expired' di route group dashboard.
 */
test('user dengan password baru (< 90 hari) bisa akses dashboard', function () {
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(30),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});

test('user dengan password kedaluwarsa (> 90 hari) di-redirect ke security.edit', function () {
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(91),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('security.edit'))
        ->assertSessionHas('warning');
});

test('user tepat di 89 hari (sebelum batas) masih bisa akses', function () {
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(89),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});

test('user tanpa password_changed_at tidak di-block (legacy account)', function () {
    $user = User::factory()->create([
        'password_changed_at' => null,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});

test('user kedaluwarsa tetap bisa akses security.edit (cegah redirect loop)', function () {
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(120),
    ]);

    // Note: security.edit punya middleware password.confirm dari Fortify,
    // jadi expect redirect ke confirm-password (BUKAN ke security.edit lagi).
    // Yang penting: BUKAN redirect ke route 'security.edit' karena middleware
    // password.expired sudah skip route name 'security.edit'.
    $response = $this->actingAs($user)->get(route('security.edit'));

    // Pastikan tidak terjadi redirect loop ke security.edit sendiri
    expect($response->headers->get('Location'))->not->toBe(route('security.edit'));
});

test('logout dapat dipanggil walaupun password expired', function () {
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(120),
    ]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect();

    expect(auth()->check())->toBeFalse();
});

test('expiry days configurable via CompanySetting', function () {
    CompanySetting::set('password_expiry_days', 30);

    // Password 60 hari, dengan setting 30 hari → expired
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(60),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('security.edit'));
});
