<?php

use App\Livewire\Admin\CompanyManager;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

test('superadmin can create companies and assign user scope', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(CompanyManager::class)
        ->set('name', 'PT Cabang Bandung')
        ->set('segment', 'branch')
        ->call('save')
        ->assertHasNoErrors();

    $company = Company::query()->where('slug', 'pt-cabang-bandung')->firstOrFail();

    Livewire::test(CompanyManager::class)
        ->set('selectedCompanyId', $company->id)
        ->set('selectedUserId', $employee->id)
        ->call('assignUser')
        ->assertHasNoErrors();

    expect($employee->fresh()->company_id)->toBe($company->id);
});

test('company manager denies normal admins without company permission', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.companies'))
        ->assertForbidden();
});

test('admin with company permission can manage companies without global rbac', function () {
    $role = Role::query()->create([
        'name' => 'Company Scope Admin',
        'slug' => 'company_scope_admin',
        'description' => 'Can manage companies.',
        'permission_keys' => ['admin.companies.manage'],
    ]);

    $admin = User::factory()->admin()->create();
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.companies'))
        ->assertOk();
});
