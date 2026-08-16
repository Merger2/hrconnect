<?php

declare(strict_types=1);

use App\Jobs\ProcessAttendanceImportRun;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function attendanceImportFixtures(): array
{
    $admin = User::factory()->admin(true)->create();
    $user = User::factory()->create(['group' => 'user']);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'employee_number' => 'IMP001',
    ]);

    return [$admin, $employee];
}

/**
 * Buat UploadedFile dengan MIME eksplisit agar lolos SecureUploadPolicy
 * (upload policy memvalidasi extension + mime + double-extension).
 */
function attendanceImportCsvFile(string $content): UploadedFile
{
    $temp = tempnam(sys_get_temp_dir(), 'impatt');
    file_put_contents($temp, $content);

    return new UploadedFile($temp, 'attendance.csv', 'text/csv', null, true);
}

// ─── CONTROLLER (upload) ───

test('admin can queue attendance import via upload controller', function () {
    Storage::fake('local');
    Queue::fake();

    [$admin] = attendanceImportFixtures();

    $file = attendanceImportCsvFile(
        "employee_number,date,clock_in,clock_out,status\nIMP001,2026-08-01,08:00:00,17:00:00,present\n"
    );

    $this->actingAs($admin)
        ->post(route('admin.attendances.import'), ['file' => $file])
        ->assertRedirect(route('admin.import-export.attendances'));

    Queue::assertPushed(ProcessAttendanceImportRun::class);

    $run = ImportExportRun::query()
        ->where('operation', 'import')
        ->where('resource', 'attendances')
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('queued')
        ->and($run->source_path)->not->toBeNull()
        ->and($run->requested_by_user_id)->toBe($admin->id);
});

test('attendance import upload rejects non-spreadsheet files', function () {
    Storage::fake('local');

    [$admin] = attendanceImportFixtures();

    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

    $this->actingAs($admin)
        ->post(route('admin.attendances.import'), ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(ImportExportRun::query()->where('operation', 'import')->count())->toBe(0);
});

test('attendance import upload rejects dangerous double extension', function () {
    Storage::fake('local');

    [$admin] = attendanceImportFixtures();

    $temp = tempnam(sys_get_temp_dir(), 'impatt');
    file_put_contents($temp, 'x');
    $file = new UploadedFile($temp, 'attendance.php.csv', 'text/csv', null, true);

    $this->actingAs($admin)
        ->post(route('admin.attendances.import'), ['file' => $file])
        ->assertSessionHasErrors('file');
});

test('attendance import upload requires importAttendances permission', function () {
    Storage::fake('local');

    $plainUser = User::factory()->create(['group' => 'user']);

    $file = attendanceImportCsvFile("employee_number,date\nIMP001,2026-08-01\n");

    $this->actingAs($plainUser)
        ->post(route('admin.attendances.import'), ['file' => $file])
        ->assertForbidden();
});

// ─── JOB (background processing) ───

test('attendance import run processes csv rows into attendance records', function () {
    Storage::fake('local');

    [$admin, $employee] = attendanceImportFixtures();

    Storage::disk('local')->put(
        'imports/attendance-test.csv',
        "employee_number,date,clock_in,clock_out,status\n"
        ."IMP001,2026-08-01,08:00:00,17:00:00,present\n"
        ."IMP001,2026-08-02,08:05:00,17:10:00,late\n"
    );

    $run = ImportExportRun::create([
        'resource' => 'attendances',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/attendance-test.csv',
        'source_name' => 'attendance-test.csv',
    ]);

    (new ProcessAttendanceImportRun($run->id))->handle();

    $run->refresh();

    // regresi 2026-08-16: job menulis `row_count` (kolom tak ada, silent drop)
    // dan membaca `file_path` (null untuk import) — sekarang total/processed_rows.
    expect($run->status)->toBe('completed')
        ->and($run->processed_rows)->toBe(2)
        ->and($run->total_rows)->toBe(2)
        ->and($run->completed_at)->not->toBeNull();

    expect(Attendance::where('employee_id', $employee->id)->count())->toBe(2);

    $first = Attendance::where('employee_id', $employee->id)
        ->where('date', '2026-08-01')
        ->first();

    expect($first)->not->toBeNull()
        ->and($first->clock_in?->format('H:i'))->toBe('08:00')
        ->and($first->clock_out?->format('H:i'))->toBe('17:00')
        ->and($first->status->value)->toBe('present');
});

test('attendance import run re-import same date updates instead of duplicating', function () {
    Storage::fake('local');

    [$admin, $employee] = attendanceImportFixtures();

    Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-01',
        'clock_in' => '2026-08-01 07:30:00',
        'status' => 'present',
    ]);

    Storage::disk('local')->put(
        'imports/attendance-update.csv',
        "employee_number,date,clock_in,clock_out,status\nIMP001,2026-08-01,08:00:00,17:00:00,present\n"
    );

    $run = ImportExportRun::create([
        'resource' => 'attendances',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/attendance-update.csv',
    ]);

    (new ProcessAttendanceImportRun($run->id))->handle();

    expect(Attendance::where('employee_id', $employee->id)->count())->toBe(1);

    $updated = Attendance::where('employee_id', $employee->id)->first();

    expect($updated->clock_in?->format('H:i'))->toBe('08:00');
});

test('attendance import run marks failed when source file missing', function () {
    Storage::fake('local');

    [$admin] = attendanceImportFixtures();

    $run = ImportExportRun::create([
        'resource' => 'attendances',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/does-not-exist.csv',
    ]);

    (new ProcessAttendanceImportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('failed')
        ->and($run->error_message)->toBe('Import file not found');
});

test('attendance import run marks failed on invalid csv rows', function () {
    Storage::fake('local');

    [$admin] = attendanceImportFixtures();

    Storage::disk('local')->put(
        'imports/attendance-invalid.csv',
        "employee_number,date,clock_in,clock_out,status\nIMP001,2026-08-01,08:00:00,17:00:00,present\nIMP001,not-a-date,08:00:00,17:00:00,present\n"
    );

    $run = ImportExportRun::create([
        'resource' => 'attendances',
        'operation' => 'import',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'source_path' => 'imports/attendance-invalid.csv',
    ]);

    (new ProcessAttendanceImportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('failed')
        ->and($run->error_message)->toContain('Validation errors');
});
