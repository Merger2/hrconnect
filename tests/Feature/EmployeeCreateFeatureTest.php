<?php

use App\Livewire\Admin\EmployeeCreate;
use App\Livewire\Admin\EmployeeEdit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Setup helper: company + main branch supaya UserForm::store() bisa resolve
 * default branch & company_id untuk Employee::create().
 */
function employeeCreateMasterData(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create([
        'company_id' => $company->id,
        'is_main' => true,
    ]);

    return [$company, $branch];
}

test('superadmin can create an employee with atomic user and employee records', function () {
    [$company, $branch] = employeeCreateMasterData();
    $superadmin = User::factory()->admin(true)->create(['company_id' => $company->id]);

    $manager = User::factory()->create(['group' => 'user']);
    $managerEmployee = Employee::factory()->create([
        'user_id' => $manager->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeCreate::class)
        ->set('form.name', 'Budi Santoso')
        ->set('form.nip', '1990010120250001')
        ->set('form.email', 'budi.santoso@hrconnect.test')
        ->set('form.phone', '081234567890')
        ->set('form.password', 'Budi!2026pass')
        ->set('form.gender', 'male')
        ->set('form.address', 'Jl. Merdeka No. 1')
        ->set('form.provinsi_kode', '11')
        ->set('form.kabupaten_kode', '11.01')
        ->set('form.kecamatan_kode', '11.01.01')
        ->set('form.kelurahan_kode', '11.01.01.1001')
        // rule manager_id: nullable + string + exists users (group user)
        ->set('form.manager_id', (string) $manager->id)
        ->set('form.birth_date', '1990-01-01')
        ->set('form.join_date', '2026-08-01')
        ->set('form.employment_type', 'permanent')
        ->set('form.education_level', 'bachelor')
        ->set('form.institution_name', 'Universitas Indonesia')
        ->set('form.graduation_year', 2012)
        ->set('form.basic_salary', 6500000)
        ->call('store')
        ->assertHasNoErrors();

    $user = User::where('email', 'budi.santoso@hrconnect.test')->firstOrFail();

    // User record: group user, company scoped, password wajib diisi admin.
    expect($user->group)->toBe('user')
        ->and($user->company_id)->toBe($company->id)
        ->and(Hash::check('Budi!2026pass', $user->password))->toBeTrue()
        ->and($user->manager_id)->toBe($manager->id);

    // Employee record: ter-create atomik bersama user (bukan orphan).
    expect($user->employee)->not->toBeNull()
        ->and($user->employee->full_name)->toBe('Budi Santoso')
        ->and($user->employee->nip)->toBe('1990010120250001')
        ->and($user->employee->nik)->toBe('1990010120250001')
        ->and($user->employee->company_id)->toBe($company->id)
        ->and($user->employee->branch_id)->toBe($branch->id)
        ->and($user->employee->manager_id)->toBe($managerEmployee->id)
        // employee_number generator: EMP-{tahun}-0001 untuk record pertama.
        ->and($user->employee->employee_number)->toBe('EMP-'.now()->format('Y').'-0001');
});

test('employee create validates required fields before inserting', function () {
    [$company] = employeeCreateMasterData();
    $superadmin = User::factory()->admin(true)->create(['company_id' => $company->id]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeCreate::class)
        ->call('store')
        ->assertHasErrors(['form.name', 'form.email', 'form.nip', 'form.phone', 'form.gender', 'form.join_date']);

    // Tidak ada user/employee baru yang bocor masuk DB saat validasi gagal.
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('employees', 0);
});

test('employee create denies users without manageUserRecord permission', function () {
    [$company] = employeeCreateMasterData();

    // Roleless admin: strict RBAC → manage_user_record bukan read-only fallback.
    $rolelessAdmin = User::factory()->admin()->create(['company_id' => $company->id]);
    $this->actingAs($rolelessAdmin);
    Livewire::test(EmployeeCreate::class)->assertForbidden();

    // Employee biasa: tidak punya akses admin sama sekali.
    $employee = User::factory()->create();
    $this->actingAs($employee);
    Livewire::test(EmployeeCreate::class)->assertForbidden();
});

test('admin with manageUserRecord permission can open employee create', function () {
    [$company] = employeeCreateMasterData();
    $admin = User::factory()->admin()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'User Record Manager_'.uniqid(),
        'slug' => 'user_record_manager_'.uniqid(),
        'description' => 'Can manage user records.',
        'permission_keys' => ['manage_user_record'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin);

    Livewire::test(EmployeeCreate::class)
        ->assertStatus(200)
        ->assertSet('form.employment_status', Employee::EMPLOYMENT_STATUS_ACTIVE);
});

test('employee edit rejects a circular manager assignment', function () {
    [$company] = employeeCreateMasterData();
    $superadmin = User::factory()->admin(true)->create(['company_id' => $company->id]);

    // Hierarki: manager → subject → child (child.manager_id = subject).
    $manager = User::factory()->create();
    $managerEmployee = Employee::factory()->create(['user_id' => $manager->id]);

    $subject = User::factory()->create(['manager_id' => $manager->id]);
    $subjectEmployee = Employee::factory()->create([
        'user_id' => $subject->id,
        'parent_id' => $managerEmployee->id,
        // Wilayah wajib untuk group user — tanpanya validate() gagal di
        // provinsi_kode duluan sebelum cycle check berjalan.
        'provinsi_kode' => '11',
        'kabupaten_kode' => '11.01',
        'kecamatan_kode' => '11.01.01',
        'kelurahan_kode' => '11.01.01.1001',
    ]);

    $child = User::factory()->create(['manager_id' => $subject->id]);
    Employee::factory()->create([
        'user_id' => $child->id,
        'parent_id' => $subjectEmployee->id,
    ]);

    $this->actingAs($superadmin);

    // Menjadikan anak (child) sebagai manager subject = lingkaran atasan.
    // Cast ke string: rule manager_id = 'string' — tanpa cast, error tipe data
    // muncul duluan dan test lulus walau cycle guard tidak terpanggil.
    Livewire::test(EmployeeEdit::class, ['employee' => $subjectEmployee])
        ->set('form.manager_id', (string) $child->id)
        ->call('update')
        ->assertHasErrors(['form.manager_id']);

    // Perubahan tidak diterapkan.
    expect($subject->fresh()->manager_id)->toBe($manager->id);
});
