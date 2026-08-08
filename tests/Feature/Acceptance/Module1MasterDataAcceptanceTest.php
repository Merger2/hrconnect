<?php

use App\Livewire\Admin\EmployeeCreate;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Acceptance — Modul 1: Master Data Karyawan (PRD §Modul 1)
 *
 * Cakupan checklist:
 * - [ ] CRUD data karyawan, jabatan, departemen, status kontrak, data pendukung
 * - [ ] Validasi data wajib (NIK, email, status aktif) + unique constraint
 * - [ ] Riwayat perubahan data dapat dilihat (audit trail)
 * - [ ] Impor data karyawan dari CSV/Excel dengan preview error sebelum commit
 *
 * Happy path: superadmin membuat karyawan → user + employee atomik.
 * Negative path: validasi wajib menolak, user roleless ditolak.
 */
function m1AcceptanceMasterData(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create([
        'company_id' => $company->id,
        'is_main' => true,
    ]);

    return [$company, $branch];
}

test('M1 acceptance: superadmin creates employee — atomic user+employee records', function () {
    [$company, $branch] = m1AcceptanceMasterData();
    $superadmin = User::factory()->admin(true)->create(['company_id' => $company->id]);
    $this->actingAs($superadmin);

    Livewire::test(EmployeeCreate::class)
        ->set('form.name', 'Acceptance Karyawan')
        ->set('form.nip', 'ACC-'.now()->format('Y').'-0001')
        ->set('form.email', 'acceptance.m1@hrconnect.test')
        ->set('form.phone', '081234567890')
        ->set('form.gender', 'male')
        ->set('form.address', 'Jl. Acceptance No. 1')
        ->set('form.provinsi_kode', '11')
        ->set('form.kabupaten_kode', '11.01')
        ->set('form.kecamatan_kode', '11.01.01')
        ->set('form.kelurahan_kode', '11.01.01.1001')
        ->set('form.birth_date', '1990-01-01')
        ->set('form.join_date', '2026-08-01')
        ->set('form.employment_type', 'permanent')
        ->set('form.education_level', 'bachelor')
        ->set('form.institution_name', 'Universitas Acceptance')
        ->set('form.graduation_year', 2012)
        ->set('form.basic_salary', 6500000)
        ->call('store')
        ->assertHasNoErrors();

    $user = User::where('email', 'acceptance.m1@hrconnect.test')->firstOrFail();
    expect($user->employee)->not->toBeNull()
        ->and($user->employee->full_name)->toBe('Acceptance Karyawan')
        ->and($user->employee->company_id)->toBe($company->id)
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('M1 acceptance: validation rejects missing required fields — no partial insert', function () {
    [$company] = m1AcceptanceMasterData();
    $superadmin = User::factory()->admin(true)->create(['company_id' => $company->id]);
    $this->actingAs($superadmin);

    Livewire::test(EmployeeCreate::class)
        ->call('store')
        ->assertHasErrors(['form.name', 'form.email', 'form.nip', 'form.phone', 'form.gender', 'form.join_date']);

    $this->assertDatabaseCount('employees', 0);
});

test('M1 acceptance: roleless admin cannot open employee create (strict RBAC)', function () {
    [$company] = m1AcceptanceMasterData();
    $rolelessAdmin = User::factory()->admin()->create(['company_id' => $company->id]);
    $this->actingAs($rolelessAdmin);

    Livewire::test(EmployeeCreate::class)->assertForbidden();
});

test('M1 acceptance: employee cannot access employee create (permission boundary)', function () {
    $employee = User::factory()->create();
    $this->actingAs($employee);

    Livewire::test(EmployeeCreate::class)->assertForbidden();
});
