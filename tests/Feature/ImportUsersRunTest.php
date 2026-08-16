<?php

declare(strict_types=1);

use App\Jobs\ProcessUserImportRun;
use App\Models\Employee;
use App\Models\ImportExportRun;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function userImportFixtures(): array
{
    $admin = User::factory()->admin(true)->create();
    $role = Role::create([
        'name' => 'employee',
        'slug' => 'employee',
        'permission_keys' => [],
    ]);
    $user = User::factory()->create(['group' => 'user']);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'employee_number' => 'UMP001',
    ]);

    return [$admin, $employee, $role];
}

function userImportCsvFile(string $content): UploadedFile
{
    $temp = tempnam(sys_get_temp_dir(), 'impusr');
    file_put_contents($temp, $content);

    return new UploadedFile($temp, 'users.csv', 'text/csv', null, true);
}

// ─── CONTROLLER (upload) ───

test('admin can queue user import via upload controller', function () {
    Storage::fake('local');
    Queue::fake();

    [$admin] = userImportFixtures();

    $file = userImportCsvFile(
        "email,name,password,role,employee_number\nbudi.import@hrconnect.test,Budi,StrongPass123!,employee,UMP001\n"
    );

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertRedirect(route('admin.import-export.users'));

    Queue::assertPushed(ProcessUserImportRun::class);

    $run = ImportExportRun::query()
        ->where('operation', 'import')
        ->where('resource', 'users')
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('queued')
        ->and($run->source_path)->not->toBeNull();
});

test('user import upload rejects non-spreadsheet files', function () {
    Storage::fake('local');

    [$admin] = userImportFixtures();

    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(ImportExportRun::query()->where('operation', 'import')->count())->toBe(0);
});

test('user import upload requires importUsers permission', function () {
    Storage::fake('local');

    $plainUser = User::factory()->create(['group' => 'user']);

    $file = userImportCsvFile("email,name\nbudi.import@hrconnect.test,Budi\n");

    $this->actingAs($plainUser)
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertForbidden();
});

// ─── JOB (background processing) ───

test('user import run creates user with explicit password, role and employee link', function () {
    Storage::fake('local');

    [$admin, $employee, $role] = userImportFixtures();

    Storage::disk('local')->put(
        'imports/users-test.csv',
        "email,name,password,role,employee_number\n"
        .'budi.import@hrconnect.test,Budi Santoso,StrongPass123!,employee,UMP001'."\n"
    );

    $run = ImportExportRun::create([
        'resource' => 'users',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/users-test.csv',
    ]);

    (new ProcessUserImportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('completed')
        ->and($run->processed_rows)->toBe(1)
        ->and($run->total_rows)->toBe(1);

    $imported = User::where('email', 'budi.import@hrconnect.test')->first();

    expect($imported)->not->toBeNull()
        ->and($imported->name)->toBe('Budi Santoso')
        ->and(Hash::check('StrongPass123!', $imported->password))->toBeTrue()
        ->and($imported->password_changed_at)->not->toBeNull()
        ->and($imported->roles()->where('roles.id', $role->id)->exists())->toBeTrue();

    expect($employee->fresh()->user_id)->toBe($imported->id);
});

test('user import run with default password leaves password_changed_at null to force change', function () {
    Storage::fake('local');

    [$admin] = userImportFixtures();

    Storage::disk('local')->put(
        'imports/users-default-pass.csv',
        "email,name,password,role,employee_number\n"
        ."default.pass@hrconnect.test,Default Pass,,\n"
    );

    $run = ImportExportRun::create([
        'resource' => 'users',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/users-default-pass.csv',
    ]);

    (new ProcessUserImportRun($run->id))->handle();

    $imported = User::where('email', 'default.pass@hrconnect.test')->first();

    // regresi 2026-08-13: password_changed_at di-set now() menonaktifkan
    // force-change CheckPasswordExpired padahal password default 'password123'.
    expect($imported)->not->toBeNull()
        ->and(Hash::check('password123', $imported->password))->toBeTrue()
        ->and($imported->password_changed_at)->toBeNull();
});

test('user import run marks failed when source file missing', function () {
    Storage::fake('local');

    [$admin] = userImportFixtures();

    $run = ImportExportRun::create([
        'resource' => 'users',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/does-not-exist.csv',
    ]);

    (new ProcessUserImportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('failed')
        ->and($run->error_message)->toBe('Import file not found');
});

test('user import run marks failed when email already exists', function () {
    Storage::fake('local');

    [$admin] = userImportFixtures();
    User::factory()->create(['email' => 'duplicate.import@hrconnect.test']);

    Storage::disk('local')->put(
        'imports/users-dup.csv',
        "email,name,password,role,employee_number\n"
        ."duplicate.import@hrconnect.test,Dup,StrongPass123!,\n"
    );

    $run = ImportExportRun::create([
        'resource' => 'users',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/users-dup.csv',
    ]);

    (new ProcessUserImportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('failed')
        ->and($run->error_message)->toContain('Validation errors');
});
