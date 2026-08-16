<?php

use App\Enums\Permission;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;

/**
 * Acceptance — Modul 6: Reports & Import/Export (PRD §Modul 6)
 *
 * Cakupan checklist:
 * - [ ] Laporan kehadiran/cuti/payroll/karyawan tabel/printable → suite OperationalReportsTest
 * - [ ] Export Excel/CSV/PDF → suite AdminAttendanceExportQueueTest, PayrollExportServiceTest
 * - [ ] Import masal validasi error + rollback → suite ImportExport + UserImport
 * - [ ] Report permissions mengikuti RBAC → test ini (gate route)
 *
 * Happy path: user dengan permission exportUsers bisa akses export.
 * Negative path: user tanpa permission ditolak (403).
 */
function m6AdminWithPermission(string ...$permissions): User
{
    $company = Company::factory()->create();
    $admin = User::factory()->admin()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Report Manager_'.uniqid(),
        'slug' => 'report_manager_'.uniqid(),
        'description' => 'Can export reports.',
        'permission_keys' => $permissions,
    ]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

test('M6 acceptance: admin with exportUsers permission can queue a user export', function () {
    $admin = m6AdminWithPermission(Permission::EXPORT_USERS->value);

    $this->actingAs($admin);

    // Export users = async queue: redirect ke run tracking + record dibuat.
    $this->get(route('admin.users.export'))
        ->assertRedirect(route('admin.import-export.users'))
        ->assertSessionHas('flash.banner');

    $this->assertDatabaseHas('import_export_runs', [
        'requested_by_user_id' => $admin->id,
    ]);
});

test('M6 acceptance: admin without exportUsers permission is denied (RBAC)', function () {
    $admin = m6AdminWithPermission(Permission::VIEW_DASHBOARD->value);

    $this->actingAs($admin);

    $this->get(route('admin.users.export'))
        ->assertForbidden();
});

test('M6 acceptance: regular employee cannot access import export area', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('admin.users.export'))
        ->assertForbidden();
});
