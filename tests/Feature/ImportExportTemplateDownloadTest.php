<?php

use App\Enums\Permission;
use App\Livewire\Admin\ImportExport\AttendanceImportExport;
use App\Livewire\Admin\ImportExport\UserImportExport;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

/**
 * Mock-miss fix (2026-08-16): tombol "Download Template" sebelumnya hanya
 * toast info tanpa file. Kini mengunduh xlsx header sesuai kontrak import.
 */
function ieTemplateAdminWithPermission(string ...$permissions): User
{
    $company = Company::factory()->create();
    $admin = User::factory()->admin()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'IE Template_'.uniqid(),
        'slug' => 'ie_template_'.uniqid(),
        'description' => 'Can import.',
        'permission_keys' => $permissions,
    ]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

test('user import template download returns xlsx file', function () {
    $admin = ieTemplateAdminWithPermission(
        Permission::VIEW_USER_IMPORT_EXPORT->value,
        Permission::IMPORT_USERS->value,
    );

    Livewire::actingAs($admin)
        ->test(UserImportExport::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('user-import-template.xlsx');
});

test('attendance import template download returns xlsx file', function () {
    $admin = ieTemplateAdminWithPermission(
        Permission::VIEW_ATTENDANCE_IMPORT_EXPORT->value,
        Permission::IMPORT_ATTENDANCES->value,
    );

    Livewire::actingAs($admin)
        ->test(AttendanceImportExport::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('attendance-import-template.xlsx');
});
