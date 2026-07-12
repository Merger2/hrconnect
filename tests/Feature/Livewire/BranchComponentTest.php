<?php

use App\Livewire\MasterData\BranchComponent;
use App\Livewire\MasterData\BranchForm;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->user->assignRole('super-admin');
});

// ─── BranchComponent (List) ────────────────────────────────────

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

test('can delete a branch', function () {
    $branch = Branch::factory()->create(['company_id' => $this->company->id]);

    Livewire::actingAs($this->user)
        ->test(BranchComponent::class)
        ->call('confirmDeletion', $branch->id)
        ->assertSet('confirmingDeletion', true)
        ->call('delete')
        ->assertDispatched('toast');

    expect(Branch::find($branch->id))->toBeNull();
});

test('search filters branches', function () {
    Branch::factory()->create(['name' => 'Alpha Office', 'company_id' => $this->company->id]);
    Branch::factory()->create(['name' => 'Beta Office', 'company_id' => $this->company->id]);

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

// ─── BranchForm (Create) ────────────────────────────────────────

test('create page renders', function () {
    $this->actingAs($this->user)
        ->get('/master-data/branches/create')
        ->assertOk()
        ->assertSee('Tambah Cabang');
});

test('can create a branch via form', function () {
    Livewire::actingAs($this->user)
        ->test(BranchForm::class)
        ->set('companyId', (string) $this->company->id)
        ->set('name', 'Test Branch')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Branch::where('name', 'Test Branch')->exists())->toBeTrue();
});

test('create requires name', function () {
    Livewire::actingAs($this->user)
        ->test(BranchForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

// ─── BranchForm (Edit) ──────────────────────────────────────────

test('edit page renders', function () {
    $branch = Branch::factory()->create(['name' => 'Old Name', 'company_id' => $this->company->id]);

    $this->actingAs($this->user)
        ->get('/master-data/branches/'.$branch->id.'/edit')
        ->assertOk()
        ->assertSee('Edit Cabang');
});

test('can edit a branch via form', function () {
    $branch = Branch::factory()->create(['name' => 'Old Name', 'company_id' => $this->company->id]);

    Livewire::actingAs($this->user)
        ->test(BranchForm::class, ['branch' => $branch])
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Branch::find($branch->id)->name)->toBe('New Name');
});
