<?php

declare(strict_types=1);

use App\Jobs\ProcessActivityLogExportRun;
use App\Models\ActivityLog;
use App\Models\ImportExportRun;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function activityLogRun(array $meta = []): ImportExportRun
{
    $user = User::factory()->create();

    return ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'queued',
        'requested_by_user_id' => $user->id,
        'meta' => array_merge([
            'start_date' => null,
            'end_date' => null,
            'actor_group' => 'all',
            'search' => null,
        ], $meta),
    ]);
}

test('job exports activity logs into xlsx and marks run completed', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();
    ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'Login Successful',
        'description' => 'User logged in.',
        'ip_address' => '127.0.0.1',
    ]);

    $run = activityLogRun();

    (new ProcessActivityLogExportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('completed')
        ->and($run->total_rows)->toBe(1)
        ->and($run->processed_rows)->toBe(1)
        ->and($run->file_path)->not->toBeNull()
        ->and($run->completed_at)->not->toBeNull();

    Storage::disk('local')->assertExists($run->file_path);
});

test('job respects start and end date filters', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'Inside Range',
        'description' => 'Recent',
        'ip_address' => '127.0.0.1',
    ]);

    $oldLog = ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'Outside Range',
        'description' => 'Old',
        'ip_address' => '127.0.0.1',
    ]);
    // created_at bukan fillable — paksa via query builder (append-only guard
    // tidak berlaku untuk query builder).
    DB::table('activity_logs')
        ->where('id', $oldLog->id)
        ->update(['created_at' => now()->subMonths(3)]);

    $run = activityLogRun([
        'start_date' => now()->subWeek()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
    ]);

    (new ProcessActivityLogExportRun($run->id))->handle();

    expect($run->refresh()->total_rows)->toBe(1)
        ->and($run->refresh()->processed_rows)->toBe(1);
});

test('export route queues an activity log export run', function () {
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Audit Exporter_'.uniqid(),
        'slug' => 'audit_exporter_'.uniqid(),
        'guard_name' => 'web',
        'permission_keys' => ['view_admin_dashboard', 'view_activity_logs', 'exportActivityLogs'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.export', [
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertRedirect(route('admin.activity-logs'));

    Queue::assertPushed(ProcessActivityLogExportRun::class);

    expect(ImportExportRun::where('resource', 'activity_logs')->count())->toBe(1);
});
