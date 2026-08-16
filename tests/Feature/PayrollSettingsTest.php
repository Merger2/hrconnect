<?php

declare(strict_types=1);

use App\Livewire\Admin\PayrollSettings;
use App\Models\PayrollComponent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function payrollSettingsAdmin(): User
{
    $admin = User::factory()->admin()->create();

    $role = Role::create([
        'name' => 'Payroll Settings_'.uniqid(),
        'slug' => 'payroll_settings_'.uniqid(),
        'guard_name' => 'web',
        'permission_keys' => ['view_admin_dashboard', 'managePayrollSettings'],
    ]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

test('payroll settings page renders component list with filters', function () {
    $admin = payrollSettingsAdmin();

    PayrollComponent::create([
        'name' => 'Tunjangan Makan',
        'code' => 'TUJANGAN_MAKAN',
        'type' => 'allowance',
        'calculation_type' => 'fixed',
        'amount' => 100_000,
        'is_taxable' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->assertSee('Tunjangan Makan')
        ->set('search', 'Makan')
        ->assertSee('Tunjangan Makan');
});

test('admin can create a fixed payroll component', function () {
    $admin = payrollSettingsAdmin();

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->set('name', 'Tunjangan Transport')
        ->set('type', 'allowance')
        ->set('calculation_type', 'fixed')
        ->set('amount', 250_000)
        ->set('is_taxable', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertDispatched('notify');

    // Query by code — migration me-seed 'Tunjangan Transport' (amount null),
    // nama sama dengan input test → where('name') salah ambil row seed.
    $component = PayrollComponent::where('code', 'TUNJANGAN_TRANSPORT')->first();

    expect($component)->not->toBeNull()
        ->and($component->type)->toBe('allowance')
        ->and($component->calculation_type)->toBe('fixed')
        ->and((float) $component->amount)->toBe(250_000.0)
        ->and($component->is_taxable)->toBeTrue();
});

test('admin can create a percentage-based deduction', function () {
    $admin = payrollSettingsAdmin();

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('create')
        ->set('name', 'Potongan Koperasi')
        ->set('type', 'deduction')
        ->set('calculation_type', 'percentage_basic')
        ->set('percentage', 2.5)
        ->call('save')
        ->assertHasNoErrors();

    $component = PayrollComponent::where('name', 'Potongan Koperasi')->first();

    expect($component)->not->toBeNull()
        ->and((float) $component->percentage)->toBe(2.5);
});

test('percentage based component requires percentage value', function () {
    $admin = payrollSettingsAdmin();

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('create')
        ->set('name', 'Potongan Tanpa Persen')
        ->set('type', 'deduction')
        ->set('calculation_type', 'percentage_basic')
        ->call('save')
        ->assertHasErrors(['percentage']);
});

test('fixed component requires amount value', function () {
    $admin = payrollSettingsAdmin();

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('create')
        ->set('name', 'Tunjangan Tanpa Nominal')
        ->set('type', 'allowance')
        ->set('calculation_type', 'fixed')
        ->call('save')
        ->assertHasErrors(['amount']);
});

test('admin can edit an existing component', function () {
    $admin = payrollSettingsAdmin();

    $component = PayrollComponent::create([
        'name' => 'Tunjangan Lama',
        'code' => 'TUJANGAN_LAMA',
        'type' => 'allowance',
        'calculation_type' => 'fixed',
        'amount' => 50_000,
        'is_taxable' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('edit', $component->id)
        ->assertSet('name', 'Tunjangan Lama')
        ->set('name', 'Tunjangan Baru')
        ->set('amount', 75_000)
        ->call('save')
        ->assertHasNoErrors();

    expect($component->fresh()->name)->toBe('Tunjangan Baru')
        ->and((float) $component->fresh()->amount)->toBe(75_000.0);
});

test('admin can toggle component active state', function () {
    $admin = payrollSettingsAdmin();

    $component = PayrollComponent::create([
        'name' => 'Tunjangan Toggle',
        'code' => 'TUJANGAN_TOGGLE',
        'type' => 'allowance',
        'calculation_type' => 'fixed',
        'amount' => 10_000,
        'is_taxable' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('toggleActive', $component->id)
        ->assertDispatched('notify');

    expect($component->fresh()->is_active)->toBeFalse();

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('toggleActive', $component->id);

    expect($component->fresh()->is_active)->toBeTrue();
});

test('admin can delete a component after confirmation', function () {
    $admin = payrollSettingsAdmin();

    $component = PayrollComponent::create([
        'name' => 'Tunjangan Hapus',
        'code' => 'TUJANGAN_HAPUS',
        'type' => 'allowance',
        'calculation_type' => 'fixed',
        'amount' => 10_000,
        'is_taxable' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->call('confirmDelete', $component->id)
        ->assertSet('confirmingDeletion', true)
        ->call('delete')
        ->assertDispatched('notify');

    expect(PayrollComponent::find($component->id))->toBeNull();
});

test('payroll settings requires managePayrollSettings permission', function () {
    $admin = User::factory()->admin()->create();

    $role = Role::create([
        'name' => 'No Payroll Access_'.uniqid(),
        'slug' => 'no_payroll_access_'.uniqid(),
        'guard_name' => 'web',
        'permission_keys' => ['view_admin_dashboard'],
    ]);
    $admin->roles()->sync([$role->id]);

    Livewire::actingAs($admin)
        ->test(PayrollSettings::class)
        ->assertForbidden();
});
