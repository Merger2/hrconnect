<?php

use App\Livewire\Admin\LeaveApproval;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\LeaveApprovalService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Setup helper: buat employee record untuk user (relasi user↔employee).
 * Attendance memakai employee_id, bukan user_id.
 */
function makeLeaveApprovalEmployee(string $name): array
{
    $user = User::factory()->create(['name' => $name]);

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'full_name' => $name,
    ]);

    return [$user, $employee];
}

/**
 * Admin dengan permission manageLeaveApprovals (render() & approve/reject
 * meng-authorize gate ini — roleless admin selalu 403).
 */
function makeLeaveApprovalAdmin(): User
{
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Leave Approval Admin_'.uniqid(),
        'slug' => 'leave_approval_admin_'.uniqid(),
        'permission_keys' => ['admin.leave_approvals.manage'],
    ]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

test('admin leave approvals show all request statuses by default', function () {
    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('Leave Request Employee');

    Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'late',
        'approval_status' => Attendance::STATUS_APPROVED,
        'note' => 'Approved family leave',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertSet('statusFilter', 'all')
        ->assertSee('Leave Request Employee')
        ->assertSee('Approved family leave');
});

test('admin leave approvals are not hidden by regional employee scope', function () {
    $admin = makeLeaveApprovalAdmin();

    [, $employee] = makeLeaveApprovalEmployee('Different Region Leave Employee');
    $employee->update([
        'provinsi_kode' => '12',
        'kabupaten_kode' => '12.01',
    ]);

    Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'late',
        'approval_status' => Attendance::STATUS_PENDING,
        'note' => 'Sick leave from another region',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertSee('Different Region Leave Employee')
        ->assertSee('Sick leave from another region');
});

test('rejecting leave keeps request type visible under rejected approval filter', function () {
    Notification::fake();

    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('Rejected Leave Employee');

    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'late',
        'approval_status' => Attendance::STATUS_PENDING,
        'note' => 'Need leave for permit',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->call('confirmReject', [$attendance->id])
        ->set('rejectionNote', 'Permit quota is full')
        ->call('reject')
        ->assertDispatched('saved');

    $attendance->refresh();

    expect($attendance->status->value)->toBe('late')
        ->and($attendance->approval_status->value)->toBe(Attendance::STATUS_REJECTED)
        ->and($attendance->rejection_note)->toBe('Permit quota is full');

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->set('statusFilter', Attendance::STATUS_REJECTED)
        ->assertSee('Rejected Leave Employee')
        ->assertSee('Permit quota is full');
});

test('leave approval service only reviews pending requests', function () {
    $admin = User::factory()->admin()->create();
    [, $employee] = makeLeaveApprovalEmployee('Already Approved Employee');

    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'late',
        'approval_status' => Attendance::STATUS_APPROVED,
        'note' => 'Already approved leave',
    ]);

    expect(fn () => app(LeaveApprovalService::class)->approve([$attendance->id], $admin))
        ->toThrow(HttpException::class);

    expect($attendance->fresh()->approval_status->value)->toBe(Attendance::STATUS_APPROVED);
});
