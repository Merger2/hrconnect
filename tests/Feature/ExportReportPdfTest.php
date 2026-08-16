<?php

declare(strict_types=1);

use App\Jobs\ProcessAttendanceReportExportRun;
use App\Models\ImportExportRun;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function reportPdfRoleUser(array $permissionKeys): User
{
    $admin = User::factory()->admin()->create();

    $role = Role::create([
        'name' => 'Report Exporter_'.uniqid(),
        'slug' => 'report_exporter_'.uniqid(),
        'guard_name' => 'web',
        'permission_keys' => $permissionKeys,
    ]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

test('report export pdf route queues monthly attendance report run', function () {
    Queue::fake();

    $admin = reportPdfRoleUser(['view_admin_dashboard', 'exportAdminReports']);

    $this->actingAs($admin)
        ->get(route('admin.reports.export-pdf', ['month' => 8, 'year' => 2026]))
        ->assertRedirect(route('admin.dashboard'));

    Queue::assertPushed(ProcessAttendanceReportExportRun::class);

    $run = ImportExportRun::where('resource', 'attendance_report')->first();

    expect($run)->not->toBeNull()
        ->and($run->operation)->toBe('export')
        ->and($run->status)->toBe('queued')
        ->and($run->meta)->toBe(['year' => 2026, 'month' => 8]);
});

test('report export pdf defaults to current month and year', function () {
    Queue::fake();

    $admin = reportPdfRoleUser(['view_admin_dashboard', 'exportAdminReports']);

    $this->travelTo(now()->startOfMonth());

    $this->actingAs($admin)
        ->get(route('admin.reports.export-pdf'))
        ->assertRedirect(route('admin.dashboard'));

    $run = ImportExportRun::where('resource', 'attendance_report')->first();

    expect($run->meta)->toBe([
        'year' => (int) now()->year,
        'month' => (int) now()->month,
    ]);
});

test('report export pdf rejects invalid month or year', function () {
    $admin = reportPdfRoleUser(['view_admin_dashboard', 'exportAdminReports']);

    $this->actingAs($admin)
        ->get(route('admin.reports.export-pdf', ['month' => 13, 'year' => 2026]))
        ->assertSessionHasErrors(['month']);
});

test('report export pdf requires exportAdminReports permission', function () {
    $admin = reportPdfRoleUser(['view_admin_dashboard']);

    $this->actingAs($admin)
        ->get(route('admin.reports.export-pdf'))
        ->assertForbidden();

    expect(ImportExportRun::count())->toBe(0);
});

test('report export pdf validates querystring via route middleware', function () {
    $admin = reportPdfRoleUser(['view_admin_dashboard', 'exportAdminReports']);

    $this->actingAs($admin)
        ->get(route('admin.reports.export-pdf', ['year' => 99]))
        ->assertSessionHasErrors(['year']);
});
