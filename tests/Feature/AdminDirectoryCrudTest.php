<?php

use App\Enums\Gender;
use App\Livewire\Admin\MasterData\Admin as AdminDirectory;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Setup helper: buat company + main branch supaya UserForm::store() bisa
 * resolve default branch & company_id untuk Employee::create().
 */
function adminDirectoryMasterData(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create([
        'company_id' => $company->id,
        'is_main' => true,
    ]);

    return [$company, $branch];
}

function adminDirectorySuperadmin(Company $company): User
{
    // Production RoleAndPermissionSeeder creates these default roles; SyncUserRoles
    // resolves the implicit default role (slug 'super-admin' / 'admin') for admin
    // group users without explicit roles. Tests must seed them to mirror prod state.
    foreach (['admin', 'super-admin'] as $slug) {
        Role::firstOrCreate(['slug' => $slug], [
            'name' => $slug,
            'is_super_admin' => $slug === 'super-admin',
        ]);
    }

    return User::factory()->admin(true)->create(['company_id' => $company->id]);
}

test('superadmin can create admin account from admin directory', function () {
    [$company] = adminDirectoryMasterData();
    $superadmin = adminDirectorySuperadmin($company);

    $position = Position::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->set('form.name', 'Finance Admin')
        ->set('form.nip', '909090')
        ->set('form.email', 'finance-admin@example.com')
        ->set('form.phone', '09090909')
        ->set('credential', 'admin123')
        ->set('form.gender', 'male')
        ->set('form.address', 'Jl. Jend. Sudirman No. 1')
        ->set('form.group', 'admin')
        ->set('form.position_id', $position->id)
        ->set('form.birth_date', '1990-01-15')
        ->set('form.join_date', '2024-01-01')
        ->set('form.education_level', 'bachelor')
        ->set('form.institution_name', 'Universitas Indonesia')
        ->set('form.graduation_year', '2020')
        ->call('create')
        ->assertHasNoErrors();

    $created = User::where('email', 'finance-admin@example.com')->firstOrFail();

    expect($created->group)->toBe('admin');
    // gender/address kini kolom employees; proxy accessor User::gender
    // memetakan L → male, P → female.
    expect($created->gender)->toBe('male');
    expect($created->address)->toBe('Jl. Jend. Sudirman No. 1');
});

test('admin directory create validates required name and email before insert', function () {
    [$company] = adminDirectoryMasterData();
    $superadmin = adminDirectorySuperadmin($company);

    $this->actingAs($superadmin);

    // gender/phone/address kini nullable untuk group admin/superadmin
    // (UserForm: requiredOrNullable hanya untuk group user) — field yang
    // selalu wajib adalah name + email.
    Livewire::test(AdminDirectory::class)
        ->set('form.nip', '808080')
        ->set('form.phone', '08080808')
        ->set('credential', 'admin123')
        ->set('form.address', 'Jl. Veteran No. 2')
        ->set('form.group', 'superadmin')
        ->call('create')
        ->assertHasErrors(['form.name' => 'required', 'form.email' => 'required']);

    $this->assertDatabaseMissing('users', [
        'email' => 'ops-admin@example.com',
    ]);
});

test('superadmin can update admin account from admin directory', function () {
    [$company] = adminDirectoryMasterData();
    $superadmin = adminDirectorySuperadmin($company);

    $admin = User::factory()->admin()->create([
        'name' => 'Old Admin',
        'email' => 'old-admin@example.com',
        'company_id' => $company->id,
    ]);

    Employee::factory()->create([
        'user_id' => $admin->id,
        'gender' => Gender::LAKI_LAKI,
        'address_detail' => 'Jl. Lama',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('edit', $admin->id)
        ->set('form.name', 'Updated Admin')
        ->set('form.phone', '08123456789')
        ->set('form.gender', 'female')
        ->set('form.address', 'Jl. Baru No. 99')
        ->call('update')
        ->assertHasNoErrors();

    $admin->refresh();

    expect($admin->name)->toBe('Updated Admin');
    // gender/address kini kolom employees; proxy accessor User::gender
    // memetakan P → female.
    expect($admin->gender)->toBe('female');
    expect($admin->address)->toBe('Jl. Baru No. 99');
});

test('superadmin can reset own password from admin directory', function () {
    [$company] = adminDirectoryMasterData();
    $superadmin = adminDirectorySuperadmin($company);

    Employee::factory()->create([
        'user_id' => $superadmin->id,
        'gender' => Gender::LAKI_LAKI,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('edit', $superadmin->id)
        ->set('form.gender', 'male')
        ->set('credential', 'new-own-password')
        ->assertSet('credential', 'new-own-password')
        ->call('update')
        ->assertHasNoErrors();

    expect(Hash::check('new-own-password', $superadmin->fresh()->password))->toBeTrue();
});

test('admin directory assigns one selected access role', function () {
    [$company] = adminDirectoryMasterData();
    $superadmin = adminDirectorySuperadmin($company);

    $admin = User::factory()->admin()->create(['company_id' => $company->id]);

    Employee::factory()->create([
        'user_id' => $admin->id,
        'gender' => Gender::LAKI_LAKI,
    ]);

    $financeRole = Role::create([
        'name' => 'Finance Operator_'.uniqid(),
        'slug' => 'finance_operator_'.uniqid(),
        'description' => 'Finance access.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.payrolls.view',
        ],
    ]);

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('edit', $admin->id)
        ->set('form.gender', 'male')
        ->set('form.role_id', $financeRole->id)
        ->call('update')
        ->assertHasNoErrors();

    expect($admin->fresh()->roles()->pluck('roles.id')->all())->toBe([$financeRole->id]);
});

test('superadmin cannot change own group from admin directory', function () {
    [$company] = adminDirectoryMasterData();
    $superadmin = adminDirectorySuperadmin($company);

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('edit', $superadmin->id)
        ->set('form.group', 'admin')
        ->call('update')
        ->assertForbidden();

    expect($superadmin->fresh()->group)->toBe('superadmin');
});
