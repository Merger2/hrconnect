<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('Logout action logs out authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(Auth::check())->toBeTrue();

    $action = new Logout;
    $response = $action();

    expect(Auth::check())->toBeFalse()
        ->and($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())->toBe(url('/'));
});

test('Logout action invalidates session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    session(['key' => 'value']);
    expect(session()->has('key'))->toBeTrue();

    $action = new Logout;
    $action();

    expect(session()->has('key'))->toBeFalse();
});

test('Logout action regenerates CSRF token', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $oldToken = csrf_token();

    $action = new Logout;
    $action();

    expect(csrf_token())->not()->toBe($oldToken);
});
