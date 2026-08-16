<?php

use App\Jobs\ProcessAttendanceExportRun;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

test('attendance export run uses attendance filters and completes', function () {

    Storage::fake('local');

    $user = User::factory()->create(['group' => 'user']);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Attendance::query()->create([
        'employee_id' => $employee->id,
        'date' => '2026-04-20',
        'clock_in' => Carbon::parse('2026-04-20 08:00:00'),
        'clock_out' => Carbon::parse('2026-04-20 17:00:00'),
        'status' => 'present',
    ]);

    $run = ImportExportRun::query()->create([
        'resource' => 'attendances',
        'operation' => 'export',
        'status' => 'queued',
        'requested_by_user_id' => $user->id,
        'meta' => [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ],
    ]);

    (new ProcessAttendanceExportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('completed')
        ->and($run->progress_percentage)->toBe(100.0)
        ->and($run->processed_rows)->toBe(1)
        ->and($run->file_path)->not->toBeNull();

    Storage::disk('local')->assertExists($run->file_path);
});
