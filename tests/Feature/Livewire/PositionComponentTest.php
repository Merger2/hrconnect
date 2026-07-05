<?php

use App\Livewire\MasterData\PositionComponent;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
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

test('position page renders successfully', function () {
    $this->actingAs($this->user)
        ->get('/master-data/positions')
        ->assertOk();
});

test('component mounts and shows empty state', function () {
    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->assertSee('Belum ada jabatan');
});

test('can create a position', function () {
    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->set('name', 'Test Position')
        ->set('code', 'TP')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Position::where('name', 'Test Position')->exists())->toBeTrue();
});

test('create requires name and code', function () {
    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->set('name', '')
        ->set('code', '')
        ->call('create')
        ->assertHasErrors(['name' => 'required', 'code' => 'required']);
});

test('can edit a position', function () {
    $pos = Position::factory()->create(['name' => 'Old Position', 'code' => 'OP']);

    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->call('edit', $pos->id)
        ->assertSet('name', 'Old Position')
        ->assertSet('code', 'OP')
        ->assertSet('editing', true);
});

test('can update a position', function () {
    $pos = Position::factory()->create(['name' => 'Old Position', 'code' => 'OP']);

    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->call('edit', $pos->id)
        ->set('name', 'New Position')
        ->call('update')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Position::find($pos->id)->name)->toBe('New Position');
});

test('can delete a position', function () {
    $pos = Position::factory()->create();

    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->call('confirmDeletion', $pos->id)
        ->assertSet('confirmingDeletion', true)
        ->call('delete')
        ->assertDispatched('toast');

    expect(Position::find($pos->id))->toBeNull();
});

test('search filters positions', function () {
    Position::factory()->create(['name' => 'Alpha Position', 'code' => 'AP']);
    Position::factory()->create(['name' => 'Beta Position', 'code' => 'BP']);

    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Position')
        ->assertDontSee('Beta Position');
});

test('employee cannot manage positions', function () {
    $this->user->removeRole('super-admin');
    $this->user->assignRole('employee');

    Livewire::actingAs($this->user)
        ->test(PositionComponent::class)
        ->assertDontSee('Tambah Jabatan')
        ->assertDontSee('Edit')
        ->assertDontSee('Hapus');
});
