<?php

use App\Livewire\Admin\EmployeeComponent;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

function createEmployeeWithUser(array $attributes = []): Employee
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(array_merge([
        'user_id' => $user->id,
    ], $attributes));

    return $employee;
}

test('employee page delete button renders valid livewire click binding', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = createEmployeeWithUser(['full_name' => 'Budi "Operator"']);
    // Tabel daftar karyawan me-render users.name (bukan employees.full_name)
    // — set nama user-nya supaya assertSee bermakna.
    $employee->user->forceFill(['name' => 'Budi "Operator"'])->save();

    $this->actingAs($superadmin);

    $response = $this->get(route('admin.employees'));

    // assertSee melakukan e() internal → "Budi &quot;Operator&quot;" cocok
    // dengan render blade {{ $user->name }}.
    $response->assertOk();
    $response->assertSee('Budi "Operator"');
});

test('employee component can open delete confirmation and delete user', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = createEmployeeWithUser();

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletion', $employee->user->id)
        ->assertSet('confirmingDeletion', true)
        ->assertSet('deleteName', $employee->user->name)
        ->call('delete')
        ->assertHasNoErrors();

    // User memakai SoftDeletes — delete() menghapus secara lunak (row tetap
    // ada dengan deleted_at terisi), jadi assert harus soft-delete.
    $this->assertSoftDeleted('users', ['id' => $employee->user->id]);
});

test('employee component can approve a pending account deletion request', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = createEmployeeWithUser([
        'employment_status' => 'deletion_requested',
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'I already resigned from the company.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletionApproval', $employee->user->id)
        ->assertSet('confirmingDeletionReview', true)
        ->assertSet('deletionReviewAction', 'approve')
        ->set('deletionReviewNotes', 'Approved by HR admin.')
        ->call('approveDeletionRequest')
        ->assertHasNoErrors();

    $employee->refresh();

    expect($employee->user->employment_status)->toBe('deleted')
        // account_deletion_reviewed_by tinggal di tabel employees (bukan
        // users) dan tidak diproxy oleh User — baca dari model Employee.
        ->and($employee->account_deletion_reviewed_by)->toBe($superadmin->id)
        ->and($employee->account_deletion_review_notes)->toBe('Approved by HR admin.');
});

test('employee component can reject a pending account deletion request', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = createEmployeeWithUser([
        'employment_status' => 'deletion_requested',
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'Please delete my account.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletionRejection', $employee->user->id)
        ->assertSet('confirmingDeletionReview', true)
        ->assertSet('deletionReviewAction', 'reject')
        ->set('deletionReviewNotes', 'Employee still needs access for handover.')
        ->call('rejectDeletionRequest')
        ->assertHasNoErrors();

    $employee->refresh();

    expect($employee->user->employment_status)->toBe('active')
        // rejectAccountDeletion mengembalikan status ke active dan mencatat
        // reviewer, tetapi TIDAK menghapus timestamp/reason permintaan (riwayat
        // audit tetap tersimpan di tabel employees).
        ->and($employee->account_deletion_requested_at)->not->toBeNull()
        ->and($employee->account_deletion_reviewed_by)->toBe($superadmin->id)
        ->and($employee->account_deletion_review_notes)->toBe('Employee still needs access for handover.');
});

test('employee component can assign a direct manager', function () {
    $superadmin = User::factory()->admin(true)->create();
    $manager = createEmployeeWithUser();
    $employee = createEmployeeWithUser([
        'parent_id' => null,
        // Wilayah wajib untuk akun group=user (rule required) — tanpanya
        // validate() gagal di provinsi_kode sebelum update berjalan.
        'provinsi_kode' => '11',
        'kabupaten_kode' => '11.01',
        'kecamatan_kode' => '11.01.01',
        'kelurahan_kode' => '11.01.01.1001',
    ]);

    $this->actingAs($superadmin);

    // form.manager_id adalah USER id (Rule::exists users where group=user)
    // dan rule-nya 'string' — bukan Employee id, harus di-cast string.
    Livewire::test(EmployeeComponent::class)
        ->call('edit', $employee->user->id)
        ->set('form.manager_id', (string) $manager->user->id)
        ->call('update')
        ->assertHasNoErrors();

    // UserForm::update() menulis dua kolom: users.manager_id (user id) dan
    // employees.manager_id (employee id dari manager) — parent_id (org chart)
    // tidak disentuh.
    expect($employee->refresh()->manager_id)->toBe($manager->id)
        ->and($employee->user->fresh()->manager_id)->toBe($manager->user->id);
});

test('employee component prevents circular direct manager assignments', function () {
    $superadmin = User::factory()->admin(true)->create();
    $manager = createEmployeeWithUser([
        'provinsi_kode' => '11',
        'kabupaten_kode' => '11.01',
        'kecamatan_kode' => '11.01.01',
        'kelurahan_kode' => '11.01.01.1001',
    ]);
    $employee = createEmployeeWithUser([
        'provinsi_kode' => '11',
        'kabupaten_kode' => '11.01',
        'kecamatan_kode' => '11.01.01',
        'kelurahan_kode' => '11.01.01.1001',
    ]);

    // Hierarki nyata ada di users.manager_id — ManagerHierarchyGuard
    // menelusuri rantai manager user (bukan employees.parent_id).
    $employee->user->update(['manager_id' => $manager->user->id]);

    $this->actingAs($superadmin);

    // Field form adalah manager_id (bukan parent_id) dan nilainya USER id
    // berupa string. Menjadikan anak sebagai atasan atasannya = siklus →
    // ManagerHierarchyGuard melempar validasi error.
    Livewire::test(EmployeeComponent::class)
        ->call('edit', $manager->user->id)
        ->set('form.manager_id', (string) $employee->user->id)
        ->call('update')
        ->assertHasErrors(['form.manager_id']);

    // Perubahan tidak diterapkan.
    expect($manager->user->fresh()->manager_id)->toBeNull();
});
