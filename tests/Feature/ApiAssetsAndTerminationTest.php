<?php

declare(strict_types=1);

use App\Enums\EmployeeStatus;
use App\Enums\TerminationType;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Support\AssetService;
use Database\Seeders\PayrollConfigSeeder;
use Database\Seeders\TarifTerSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Coverage prioritas #4 (lanjutan): /api/v1/assets* + /api/v1/employee-terminations*.
 *
 * ⚠️ Menangkap 3 app-bug:
 *  - AssetPolicy hilang → /api/v1/assets* selalu 403 (dibuat, pola LoanPolicy)
 *  - Route handover/return asset TIDAK pernah didaftarkan di routes/api.php
 *    (controller + request + service sudah ada) → kini didaftarkan
 *  - EmployeeTerminationController::index filter 'dismissed' padahal enum
 *    menulis 'terminated' → karyawan PHK tidak pernah muncul di daftar
 */
function assetApiRole(User $user, string $name, array $permissionKeys): Role
{
    $role = Role::create([
        'name' => $name,
        'guard_name' => 'web',
        'slug' => $name,
        'permission_keys' => $permissionKeys,
    ]);

    $user->assignRole($name);

    return $role;
}

beforeEach(function () {
    // Perhitungan pesangon/uang penghargaan butuh konfigurasi payroll.
    (new TarifTerSeeder)->run();
    (new PayrollConfigSeeder)->run();
});

// ─────────────────────────────────────────────────────────────
//  ASSETS — /api/v1/assets* (regresi AssetPolicy + route handover/return)
// ─────────────────────────────────────────────────────────────

test('admin with view_assets can list assets via api', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-viewer', ['view_assets']);

    Asset::factory()->count(2)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/assets')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'serial_number', 'status', 'is_available']]]);
});

test('asset list rejects users without view_assets permission', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/assets')->assertForbidden();
});

test('admin with manage_assets can create asset via api', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager', ['view_assets', 'manage_assets']);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/assets', [
        'name' => 'Laptop Dell XPS',
        'serial_number' => 'SN-UNIQUE-001',
        'category' => 'elektronik',
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Laptop Dell XPS')
        ->assertJsonPath('data.status', 'available');

    $this->assertDatabaseHas('assets', ['serial_number' => 'SN-UNIQUE-001', 'status' => 'available']);
});

test('asset store validates required fields and unique serial', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-2', ['manage_assets']);
    Asset::factory()->create(['serial_number' => 'SN-DUP-001']);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/assets', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'serial_number']);

    $this->postJson('/api/v1/assets', [
        'name' => 'Duplicate',
        'serial_number' => 'SN-DUP-001',
    ])->assertUnprocessable()->assertJsonValidationErrors('serial_number');
});

test('asset create requires manage_assets permission', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-viewer-only', ['view_assets']);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/assets', [
        'name' => 'Tanpa izin',
        'serial_number' => 'SN-NO-PERM',
    ])->assertForbidden();
});

test('admin can show asset detail with handover history', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-viewer-2', ['view_assets']);

    $asset = Asset::factory()->create();

    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/assets/{$asset->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $asset->id)
        ->assertJsonStructure(['data' => ['id', 'name', 'handovers']]);
});

test('admin can update asset via api', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-3', ['manage_assets']);

    $asset = Asset::factory()->create(['name' => 'Lama']);

    Sanctum::actingAs($admin);

    $this->putJson("/api/v1/assets/{$asset->id}", ['name' => 'Baru'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Baru');
});

test('admin can delete available asset via api (soft delete)', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-4', ['manage_assets']);

    $asset = Asset::factory()->create();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/assets/{$asset->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Aset berhasil dihapus');

    $this->assertSoftDeleted('assets', ['id' => $asset->id]);
});

test('assigned asset cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-5', ['manage_assets']);

    $asset = Asset::factory()->assigned()->create();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/assets/{$asset->id}")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Aset yang sedang digunakan tidak dapat dihapus.');
});

test('admin can handover asset to employee via api', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-6', ['view_assets', 'manage_assets']);

    $asset = Asset::factory()->create();
    $employee = Employee::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/assets/{$asset->id}/handover", [
        'employee_id' => $employee->id,
        'handover_date' => now()->toDateString(),
        'condition' => 'Baik',
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.employee_id', $employee->id);

    $this->assertDatabaseHas('asset_handovers', [
        'asset_id' => $asset->id,
        'employee_id' => $employee->id,
    ]);
    $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'assigned', 'is_available' => false]);
});

test('handover rejects already assigned asset', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-7', ['manage_assets']);

    $asset = Asset::factory()->assigned()->create();
    $employee = Employee::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/assets/{$asset->id}/handover", [
        'employee_id' => $employee->id,
        'handover_date' => now()->toDateString(),
        'condition' => 'Baik',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Aset tidak tersedia untuk diserahkan.');
});

test('admin can return asset via api and asset becomes available', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'asset-manager-8', ['view_assets', 'manage_assets']);

    $asset = Asset::factory()->create();
    $employee = Employee::factory()->create();

    $handover = app(AssetService::class)->handover($asset, $employee, [
        'handover_date' => now()->toDateString(),
        'condition' => 'Baik',
    ]);

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/assets/{$handover->id}/return", [
        'return_date' => now()->toDateString(),
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'available', 'is_available' => true]);
});

test('unauthenticated asset request is rejected', function () {
    $this->getJson('/api/v1/assets')->assertUnauthorized();
});

// ─────────────────────────────────────────────────────────────
//  EMPLOYEE TERMINATIONS — /api/v1/employee-terminations*
// ─────────────────────────────────────────────────────────────

test('admin can list terminations including dismissed employees', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-viewer', ['view_employees', 'manage_employees']);

    $resigned = Employee::factory()->create(['status' => EmployeeStatus::RESIGNED]);
    $terminated = Employee::factory()->create(['status' => EmployeeStatus::TERMINATED]);
    Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/employee-terminations')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data');

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($resigned->id)
        ->and($ids)->toContain($terminated->id);
});

test('admin can terminate active employee with resign type via api', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-manager', ['view_employees', 'manage_employees']);

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/employee-terminations/{$employee->id}", [
        'type' => TerminationType::RESIGN->value,
        'reason' => 'Mengundurkan diri',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'resigned')
        ->assertJsonPath('data.termination_type', 'resign')
        ->assertJsonPath('data.face_cleared', true)
        ->assertJsonPath('data.resign_date', now()->toDateString());

    $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'resigned']);
    // User non-deceased ikut di-soft-delete.
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('termination store requires manage_employees permission', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-viewer-only', ['view_employees']);

    $employee = Employee::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/employee-terminations/{$employee->id}", [
        'type' => TerminationType::RESIGN->value,
    ])->assertForbidden();
});

test('termination store validates termination type', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-manager-2', ['manage_employees']);

    $employee = Employee::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/employee-terminations/{$employee->id}", [
        'type' => 'bogus-type',
    ])->assertUnprocessable()->assertJsonValidationErrors('type');
});

test('termination rejects already terminated employee', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-manager-3', ['manage_employees']);

    $employee = Employee::factory()->create(['status' => EmployeeStatus::RESIGNED]);

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/employee-terminations/{$employee->id}", [
        'type' => TerminationType::RESIGN->value,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Karyawan sudah tidak aktif. Tidak dapat melakukan terminasi ulang.');
});

test('deceased termination keeps user account intact', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-manager-4', ['manage_employees']);

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/employee-terminations/{$employee->id}", [
        'type' => TerminationType::DECEASED->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'deceased')
        ->assertJsonPath('data.deceased_date', now()->toDateString());

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('process contract end batch terminates expired contractors', function () {
    $admin = User::factory()->admin()->create();
    assetApiRole($admin, 'term-manager-5', ['manage_employees']);

    $expired = Employee::factory()->create([
        'employment_type' => 'contract',
        'contract_end_date' => now()->subDay()->toDateString(),
    ]);
    Employee::factory()->create([
        'employment_type' => 'permanent',
        'contract_end_date' => now()->subDay()->toDateString(),
    ]);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/employee-terminations/process-contract-end')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.processed_count', 1);

    $this->assertDatabaseHas('employees', ['id' => $expired->id, 'status' => 'resigned']);
});

test('process contract end requires manage_employees permission', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/employee-terminations/process-contract-end')
        ->assertForbidden();
});
