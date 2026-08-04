<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\User;
use Livewire\Livewire;

test('analytics dashboard reads month and year from query params', function () {
    $admin = User::factory()->admin(true)->create();
    $this->actingAs($admin);

    Livewire::withQueryParams(['month' => 3, 'year' => now()->subYear()->year])
        ->test(AnalyticsDashboard::class)
        ->assertSet('month', 3)
        ->assertSet('year', now()->subYear()->year);
});

test('analytics dashboard defaults to current month and year', function () {
    $admin = User::factory()->admin(true)->create();
    $this->actingAs($admin);

    Livewire::test(AnalyticsDashboard::class)
        ->assertSet('month', now()->month)
        ->assertSet('year', now()->year);
});
