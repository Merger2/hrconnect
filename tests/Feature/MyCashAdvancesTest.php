<?php

use App\Livewire\User\Finance\MyCashAdvances;
use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Livewire\Livewire;

test('user can submit cash advance request within salary limit', function () {
    $user = User::factory()->create();

    $position = Position::factory()->create(['basic_salary' => 5000000]);

    Employee::factory()->create([
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MyCashAdvances::class)
        ->set('amount', '1250000')
        ->set('purpose', 'Laptop repair reimbursement bridge')
        ->set('payment_month', 5)
        ->set('payment_year', 2026)
        ->call('submit')
        ->assertSet('showCreateModal', false);

    $advance = CashAdvance::query()->firstOrFail();

    expect((int) $advance->amount)->toBe(1250000)
        ->and($advance->status)->toBe('pending')
        ->and($advance->user_id)->toBe($user->id);
});

test('cash advance summary includes pending finance in unpaid total', function () {
    $user = User::factory()->create();

    $position = Position::factory()->create(['basic_salary' => 5000000]);

    Employee::factory()->create([
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    CashAdvance::create([
        'user_id' => $user->id,
        'amount' => 1000000,
        'purpose' => 'Pending finance request',
        'payment_month' => 5,
        'payment_year' => 2026,
        'status' => 'pending_finance',
    ]);

    CashAdvance::create([
        'user_id' => $user->id,
        'amount' => 500000,
        'purpose' => 'Paid request',
        'payment_month' => 4,
        'payment_year' => 2026,
        'status' => 'paid',
    ]);

    $this->actingAs($user);

    Livewire::test(MyCashAdvances::class)
        ->assertViewHas('totalUnpaid', fn ($value) => (float) $value === 1000000.0)
        ->assertViewHas('totalPaid', fn ($value) => (float) $value === 500000.0);
});
