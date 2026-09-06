<?php

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Livewire\Admin\LeaveApproval;
use App\Models\Approval;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
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
    Employee::factory()->create([
        'user_id' => $admin->id,
        'full_name' => $admin->name,
    ]);

    $role = Role::create([
        'name' => 'Leave Approval Admin_'.uniqid(),
        'slug' => 'leave_approval_admin_'.uniqid(),
        'permission_keys' => ['admin.leave_approvals.manage'],
    ]);
    $admin->roles()->sync([$role->id]);

    return $admin;
}

function makeLeaveApprovalRequest(Employee $employee, RequestStatus $status = RequestStatus::APPROVED_L1): Leave
{
    $leaveType = LeaveType::factory()->create(['name' => 'Annual Leave']);
    $leave = Leave::factory()->create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'total_days' => 2,
        'reason' => 'Family leave',
        'status' => $status,
    ]);

    $leave->approvals()->create([
        'approver_id' => $employee->id,
        'level' => ApprovalLevel::L1_SUPERVISOR,
        'status' => ApprovalStatus::APPROVED,
        'approved_at' => now(),
    ]);

    $leave->approvals()->create([
        'approver_id' => User::query()->where('group', 'admin')->first()->employee->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    return $leave;
}

test('admin leave approvals show leave model requests by default', function () {
    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('Leave Request Employee');

    makeLeaveApprovalRequest($employee, RequestStatus::APPROVED_L1);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertSet('statusFilter', 'all')
        ->assertSee('Leave Request Employee')
        ->assertSee('Family leave');
});

test('admin leave approvals ignore legacy attendance exception records', function () {
    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('Legacy Attendance Employee');

    Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'late',
        'approval_status' => Attendance::STATUS_PENDING,
        'note' => 'Legacy attendance leave exception',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertDontSee('Legacy Attendance Employee')
        ->assertDontSee('Legacy attendance leave exception');
});

test('admin leave approvals are not hidden by regional employee scope', function () {
    $admin = makeLeaveApprovalAdmin();

    [, $employee] = makeLeaveApprovalEmployee('Different Region Leave Employee');
    $employee->update([
        'provinsi_kode' => '12',
        'kabupaten_kode' => '12.01',
    ]);

    makeLeaveApprovalRequest($employee);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertSee('Different Region Leave Employee')
        ->assertSee('Family leave');
});

test('rejecting leave keeps request visible under rejected approval filter', function () {
    Notification::fake();

    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('Rejected Leave Employee');

    $leave = makeLeaveApprovalRequest($employee);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->call('confirmReject', [$leave->id])
        ->set('rejectionNote', 'Permit quota is full')
        ->call('reject')
        ->assertDispatched('saved');

    $leave->refresh();

    expect($leave->status)->toBe(RequestStatus::REJECTED)
        ->and($leave->rejection_reason)->toBe('Permit quota is full');

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->set('statusFilter', RequestStatus::REJECTED->value)
        ->assertSee('Rejected Leave Employee')
        ->assertSee('Permit quota is full');
});

test('leave approval service only reviews pending requests', function () {
    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('Already Approved Employee');

    $leave = Leave::factory()->approved()->create([
        'employee_id' => $employee->id,
        'reason' => 'Already approved leave',
    ]);

    expect(fn () => app(LeaveApprovalService::class)->approve([$leave->id], $admin))
        ->toThrow(HttpException::class);

    expect($leave->fresh()->status)->toBe(RequestStatus::APPROVED);
});

test('admin leave approvals finalize l2 leave workflow', function () {
    Notification::fake();

    $admin = makeLeaveApprovalAdmin();
    [, $employee] = makeLeaveApprovalEmployee('L2 Leave Employee');
    $leave = makeLeaveApprovalRequest($employee);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->call('approve', [$leave->id])
        ->assertDispatched('saved');

    expect($leave->fresh()->status)->toBe(RequestStatus::APPROVED)
        ->and(Approval::query()
            ->where('approvable_type', Leave::class)
            ->where('approvable_id', $leave->id)
            ->where('level', ApprovalLevel::L2_MANAGER)
            ->first()
            ->status)->toBe(ApprovalStatus::APPROVED);
});
