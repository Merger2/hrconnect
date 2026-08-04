<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\SecureUploadPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

test('idor matrix denies another user attendance media', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $attendance = Attendance::create([
        'employee_id' => $ownerEmployee->id,
        'date' => now()->toDateString(),
        'status' => 'on_time',
        'clock_in' => now()->setTime(8, 0),
        'clock_out' => now()->setTime(17, 0),
    ]);

    $this->actingAs($attacker)
        ->get(route('attendance.photo', [$attendance, 'in']))
        ->assertForbidden();
});

test('attachment matrix rejects unsafe names and denies cross user reimbursement downloads', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    Storage::disk('local')->put('reimbursements/private.pdf', 'private');

    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $reimbursement = Reimbursement::create([
        'employee_id' => $ownerEmployee->id,
        'title' => 'Medical',
        'expense_date' => now()->toDateString(),
        'amount' => 100000,
        'description' => 'Private claim',
        'attachment_path' => 'reimbursements/private.pdf',
        'status' => 'pending',
    ]);

    $file = UploadedFile::fake()->create('claim.php.pdf', 64, 'application/pdf');
    $rules = app(SecureUploadPolicy::class)->rules('document');

    expect(validator(['attachment' => $file], ['attachment' => ['required', ...$rules]])->fails())->toBeTrue();

    $this->actingAs($attacker)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertForbidden();
});

test('payslip and payroll privacy matrix denies other users and unauthorized admins', function () {

    $owner = User::factory()->create();
    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $attacker = User::factory()->create();
    $plainAdmin = User::factory()->admin()->create();

    $payroll = Payroll::create([
        'employee_id' => $ownerEmployee->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'total_allowance' => 0,
        'gross_salary' => 1000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);

    expect(Gate::forUser($owner)->allows('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($attacker)->denies('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($plainAdmin)->denies('view', $payroll))->toBeTrue();
});

test('backup access matrix requires explicit maintenance management', function () {

    $viewer = User::factory()->admin()->create();
    $manager = User::factory()->admin()->create();

    Role::create([
        'name' => 'Security Matrix Maintenance Viewer_'.uniqid(),
        'slug' => 'security_matrix_maintenance_viewer_'.uniqid(),
        'description' => 'Can view maintenance.',
        'permission_keys' => ['admin.system_maintenance.view'],
    ])->users()->attach($viewer);
    Role::create([
        'name' => 'Security Matrix Maintenance Manager_'.uniqid(),
        'slug' => 'security_matrix_maintenance_manager_'.uniqid(),
        'description' => 'Can manage maintenance.',
        'permission_keys' => ['admin.system_maintenance.manage'],
    ])->users()->attach($manager);

    $backup = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $manager->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);
    $backup->update([
        'status' => 'completed',
        'file_path' => 'backups/security-matrix.sql',
        'file_name' => 'security-matrix.sql',
        'completed_at' => now(),
    ]);

    expect(Gate::forUser($viewer)->denies('download', $backup))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('download', $backup))->toBeTrue();
});

test('debug route matrix keeps diagnostic endpoints hidden in production', function () {
    Config::set('app.debug', false);
    app()->detectEnvironment(fn () => 'production');

    $this->actingAs(User::factory()->create())
        ->get('/__auth-debug')
        ->assertNotFound();

    app()->detectEnvironment(fn () => 'testing');
});
