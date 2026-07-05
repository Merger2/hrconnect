<?php

use App\Livewire\MasterData\BranchComponent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Company::factory()->create();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->user->assignRole('super-admin');
});

test('branch page renders successfully', function () {
    $this->actingAs($this->user)
        ->get('/master-data/branches')
        ->assertOk();
});

test('component mounts and shows empty state', function () {
    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->assertSee('Belum ada cabang');
});

test('can create a branch', function () {
    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->set('name', 'Test Branch')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Branch::where('name', 'Test Branch')->exists())->toBeTrue();
});

test('create requires name', function () {
    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->set('name', '')
        ->call('create')
        ->assertHasErrors(['name' => 'required']);
});

test('can edit a branch', function () {
    $branch = Branch::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->call('edit', $branch->id)
        ->assertSet('name', 'Old Name')
        ->assertSet('selectedId', $branch->id)
        ->assertSet('editing', true);
});

test('can update a branch', function () {
    $branch = Branch::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->call('edit', $branch->id)
        ->set('name', 'New Name')
        ->call('update')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Branch::find($branch->id)->name)->toBe('New Name');
});

test('can delete a branch', function () {
    $branch = Branch::factory()->create();

    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->call('confirmDeletion', $branch->id)
        ->assertSet('confirmingDeletion', true)
        ->call('delete')
        ->assertDispatched('toast');

    expect(Branch::find($branch->id))->toBeNull();
});

test('search filters branches', function () {
    Branch::factory()->create(['name' => 'Alpha Office']);
    Branch::factory()->create(['name' => 'Beta Office']);

    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Office')
        ->assertDontSee('Beta Office');
});

test('employee cannot manage branches', function () {
    $this->user->removeRole('super-admin');
    $this->user->assignRole('employee');

    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->assertDontSee('Tambah Cabang')
        ->assertDontSee('Edit')
        ->assertDontSee('Hapus');
});
