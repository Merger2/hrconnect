<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->user->assignRole('employee');
});

// ─── Public Auth Pages ─────────────────────────────────────────────

test('welcome page redirects to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('login page returns 200', function () {
    $this->get('/login')->assertOk();
});

test('register page returns 404 when registration disabled', function () {
    $this->get('/register')->assertNotFound();
});

test('forgot password page returns 200', function () {
    $this->get('/forgot-password')->assertOk();
});

test('reset password page returns 200 with token', function () {
    $this->get('/reset-password/fake-token-123')->assertOk();
});

test('two-factor challenge redirects to login when no session', function () {
    $this->get('/two-factor-challenge')->assertRedirect();
});

// ─── Auth-Required Pages (redirect to login) ───────────────────────

test('dashboard redirects to login when unauthenticated', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('email verify redirects to login when unauthenticated', function () {
    $this->get('/email/verify')->assertRedirect('/login');
});

test('confirm password redirects to login when unauthenticated', function () {
    $this->get('/user/confirm-password')->assertRedirect('/login');
});

test('settings/profile redirects to login when unauthenticated', function () {
    $this->get('/settings/profile')->assertRedirect('/login');
});

test('settings/appearance redirects to login when unauthenticated', function () {
    $this->get('/settings/appearance')->assertRedirect('/login');
});

test('settings/security redirects to login when unauthenticated', function () {
    $this->get('/settings/security')->assertRedirect('/login');
});

// ─── Authenticated Pages ───────────────────────────────────────────

test('dashboard returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk();
});

test('email verify redirects to dashboard for already-verified user', function () {
    $this->actingAs($this->user)
        ->get('/email/verify')
        ->assertRedirect('/dashboard');
});

test('confirm password page returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/user/confirm-password')
        ->assertOk();
});

// ─── Settings Pages (Livewire) ─────────────────────────────────────

test('settings/profile returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/settings/profile')
        ->assertOk();
});

test('settings/appearance returns 200 for authenticated verified user', function () {
    $this->actingAs($this->user)
        ->get('/settings/appearance')
        ->assertOk();
});

test('settings/security requires password confirmation for 2FA', function () {
    $this->actingAs($this->user)
        ->get('/settings/security')
        ->assertRedirect('/user/confirm-password');
});

// ─── HR Module Pages (authenticated) ────────────────────────────────

test('attendance index returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/attendance')
        ->assertOk();
});

test('attendance clock-in returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/attendance/clock-in')
        ->assertOk();
});

test('leaves index returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/leaves')
        ->assertOk();
});

test('leaves apply returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/leaves/apply')
        ->assertOk();
});

test('overtimes index returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/overtimes')
        ->assertOk();
});

test('overtimes apply returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/overtimes/apply')
        ->assertOk();
});

test('reimbursements index returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/reimbursements')
        ->assertOk();
});

test('reimbursements apply returns 200 for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/reimbursements/apply')
        ->assertOk();
});
