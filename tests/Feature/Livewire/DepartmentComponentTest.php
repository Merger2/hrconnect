<?php

use App\Livewire\MasterData\DepartmentComponent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Company::factory()->create();
    Branch::factory()->create();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->user->assignRole('super-admin');
});

test('department page renders successfully', function () {
    $this->actingAs($this->user)
        ->get('/master-data/departments')
        ->assertOk();
});

test('component mounts and shows empty state', function () {
    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->assertSee('Belum ada departemen');
});

test('can create a department', function () {
    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->set('name', 'Test Dept')
        ->set('code', 'TD')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Department::where('name', 'Test Dept')->exists())->toBeTrue();
});

test('create requires name and code', function () {
    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->set('name', '')
        ->set('code', '')
        ->call('create')
        ->assertHasErrors(['name' => 'required', 'code' => 'required']);
});

test('can edit a department', function () {
    $dept = Department::factory()->create(['name' => 'Old Dept', 'code' => 'OD']);

    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->call('edit', $dept->id)
        ->assertSet('name', 'Old Dept')
        ->assertSet('code', 'OD')
        ->assertSet('editing', true);
});

test('can update a department', function () {
    $dept = Department::factory()->create(['name' => 'Old Dept', 'code' => 'OD']);

    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->call('edit', $dept->id)
        ->set('name', 'New Dept')
        ->call('update')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Department::find($dept->id)->name)->toBe('New Dept');
});

test('can delete a department', function () {
    $dept = Department::factory()->create();

    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->call('confirmDeletion', $dept->id)
        ->assertSet('confirmingDeletion', true)
        ->call('delete')
        ->assertDispatched('toast');

    expect(Department::find($dept->id))->toBeNull();
});

test('search filters departments', function () {
    Department::factory()->create(['name' => 'Alpha Dept', 'code' => 'AD']);
    Department::factory()->create(['name' => 'Beta Dept', 'code' => 'BD']);

    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Dept')
        ->assertDontSee('Beta Dept');
});

test('employee cannot manage departments', function () {
    $this->user->removeRole('super-admin');
    $this->user->assignRole('employee');

    Livewire::actingAs($this->user)
        ->test(DepartmentComponent::class)
        ->assertDontSee('Tambah Departemen')
        ->assertDontSee('Edit')
        ->assertDontSee('Hapus');
});
