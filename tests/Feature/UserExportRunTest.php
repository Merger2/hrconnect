<?php

declare(strict_types=1);

use App\Jobs\ProcessUserExportRun;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('user export job persists processed and total row counts', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $run = ImportExportRun::create([
        'resource' => 'users',
        'operation' => 'export',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'meta' => ['groups' => ['user']],
    ]);

    (new ProcessUserExportRun($run->id))->handle();

    $run->refresh();

    // Regresi 2026-08-13: job menulis `row_count` — kolom tidak ada di tabel
    // import_export_runs (hanya total_rows + processed_rows) → silent drop,
    // UI menampilkan 0 baris. Sekarang harus 4 (admin + 3 user).
    expect($run->status)->toBe('completed')
        ->and($run->total_rows)->toBe(4)
        ->and($run->processed_rows)->toBe(4)
        ->and($run->file_path)->not->toBeNull();

    Storage::disk('local')->assertExists($run->file_path);
});
