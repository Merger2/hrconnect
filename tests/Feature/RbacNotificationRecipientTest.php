<?php

use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AssetReturnOtpRequested;
use App\Notifications\LeaveRequested;
use App\Notifications\OvertimeRequested;
use App\Notifications\ReimbursementRequested;
use App\Services\Attendance\LeaveRequestService;
use App\Support\UserAssetService;
use App\Support\UserNotificationRecipientService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {});

function createNotificationHierarchy(string $divisionName = 'Operations'): array
{
    $division = Division::create([
        'name' => $divisionName,
        'code' => strtoupper(substr($divisionName, 0, 3)).'_'.uniqid(),
    ]);
    $managerLevel = JobLevel::create(['name' => $divisionName.' Manager Level', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => $divisionName.' Staff Level', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => $divisionName.' Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => $divisionName.' Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create();
    Employee::factory()->create(['user_id' => $manager->id, 'division_id' => $division->id]);

    $employee = User::factory()->create();
    Employee::factory()->create(['user_id' => $employee->id, 'division_id' => $division->id]);

    return [$manager, $employee];
}

function createFinanceHeadReviewer(bool $admin = false): User
{
    $division = Division::create([
        'name' => 'Finance',
        'code' => 'FIN_'.uniqid(),
    ]);
    $level = JobLevel::create(['name' => 'Finance Head Level', 'rank' => 2]);
    $title = JobTitle::create([
        'name' => 'Finance Head',
        'job_level_id' => $level->id,
        'division_id' => $division->id,
    ]);

    $factory = $admin ? User::factory()->admin() : User::factory();

    $user = $factory->create();
    Employee::factory()->create(['user_id' => $user->id, 'division_id' => $division->id]);

    return $user;
}

test('leave request notifications only target supervisor and explicit leave approvers', function () {
    Notification::fake();

    [$manager, $employee] = createNotificationHierarchy();
    $leaveAdmin = User::factory()->admin()->create();
    $dashboardAdmin = User::factory()->admin()->create();

    $leaveApproverRole = Role::create([
        'name' => 'Leave Approver_'.uniqid(),
        'slug' => 'leave_approver_notification_'.uniqid(),
        'description' => 'Can review leave requests.',
        'permission_keys' => ['admin.leave_approvals.approve'],
    ]);

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Notifications_'.uniqid(),
        'slug' => 'dashboard_only_notifications_'.uniqid(),
        'description' => 'Cannot review leave requests.',
        'permission_keys' => ['admin.dashboard.view'],
    ]);

    $leaveAdmin->roles()->sync([$leaveApproverRole->id]);
    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $result = app(LeaveRequestService::class)->submitLeaveRequest(
        $employee,
        'leave',
        'Family event',
        Carbon::tomorrow(),
        Carbon::tomorrow(),
    );

    expect($result->ok)->toBeTrue();

    $attendance = Attendance::query()->where('user_id', $employee->id)->latest('created_at')->firstOrFail();

    Notification::assertSentTo($manager, LeaveRequested::class, fn (LeaveRequested $notification) => $notification->attendance->is($attendance));
    Notification::assertSentTo($leaveAdmin, LeaveRequested::class, fn (LeaveRequested $notification) => $notification->attendance->is($attendance));
    Notification::assertNotSentTo($dashboardAdmin, LeaveRequested::class);
});

test('reimbursement request notifications only target supervisor and reimbursement approvers', function () {
    Notification::fake();

    [$manager, $employee] = createNotificationHierarchy();
    $financeHead = createFinanceHeadReviewer();
    $dashboardAdmin = User::factory()->admin()->create();

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Reimbursement Notifications_'.uniqid(),
        'slug' => 'dashboard_only_reimbursement_notifications_'.uniqid(),
        'description' => 'Cannot review reimbursement requests.',
        'permission_keys' => ['admin.dashboard.view'],
    ]);

    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $emp = Employee::factory()->create(['user_id' => $employee->id]);
    $reimbursement = Reimbursement::create([
        'employee_id' => $emp->id,
        'title' => 'Transport',
        'expense_date' => now()->toDateString(),
        'amount' => 125000,
        'description' => 'Taxi to client site',
        'status' => 'pending',
    ]);

    $service = app(UserNotificationRecipientService::class);
    $recipientEmails = $service->reimbursementApprovers($employee)->pluck('email')->all();
    $recipientCount = $service->notifyReimbursementRequested($reimbursement);

    expect($recipientEmails)->toEqualCanonicalizing([$manager->email, $financeHead->email])
        ->and($recipientCount)->toBe(2);

    Notification::assertSentTo($manager, ReimbursementRequested::class, fn (ReimbursementRequested $notification) => $notification->reimbursement->is($reimbursement));
    Notification::assertSentTo($financeHead, ReimbursementRequested::class, fn (ReimbursementRequested $notification) => $notification->reimbursement->is($reimbursement));
    Notification::assertNotSentTo($dashboardAdmin, ReimbursementRequested::class);
});

test('overtime request notifications only target supervisor and overtime approvers', function () {
    Notification::fake();

    [$manager, $employee] = createNotificationHierarchy();
    $overtimeAdmin = User::factory()->admin()->create();
    $dashboardAdmin = User::factory()->admin()->create();

    $overtimeApproverRole = Role::create([
        'name' => 'Overtime Approver_'.uniqid(),
        'slug' => 'overtime_approver_notification_'.uniqid(),
        'description' => 'Can review overtime requests.',
        'permission_keys' => ['admin.overtime.manage'],
    ]);

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Overtime Notifications_'.uniqid(),
        'slug' => 'dashboard_only_overtime_notifications_'.uniqid(),
        'description' => 'Cannot review overtime requests.',
        'permission_keys' => ['admin.dashboard.view'],
    ]);

    $overtimeAdmin->roles()->sync([$overtimeApproverRole->id]);
    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $overtime = Overtime::create([
        'employee_id' => $employee->employee->id,
        'date' => now()->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '20:00:00',
        'duration' => 120,
        'reason' => 'Server maintenance',
        'status' => 'pending',
    ]);

    $recipientEmails = app(UserNotificationRecipientService::class)->overtimeApprovers($employee)->pluck('email')->all();
    $recipientCount = app(UserNotificationRecipientService::class)->notifyOvertimeRequested($overtime);

    expect($recipientEmails)->toEqualCanonicalizing([$manager->email, $overtimeAdmin->email])
        ->and($recipientCount)->toBe(2);

    Notification::assertSentTo($manager, OvertimeRequested::class, fn (OvertimeRequested $notification) => $notification->overtime->is($overtime));
    Notification::assertSentTo($overtimeAdmin, OvertimeRequested::class, fn (OvertimeRequested $notification) => $notification->overtime->is($overtime));
    Notification::assertNotSentTo($dashboardAdmin, OvertimeRequested::class);
});

test('asset return otp notifications fall back to explicit asset admins when no supervisor exists', function () {
    Notification::fake();

    $employee = User::factory()->create();
    Employee::factory()->create(['user_id' => $employee->id, 'division_id' => null]);
    $assetAdmin = User::factory()->admin()->create();
    $dashboardAdmin = User::factory()->admin()->create();

    $assetViewerRole = Role::create([
        'name' => 'Asset Viewer_'.uniqid(),
        'slug' => 'asset_viewer_notification_'.uniqid(),
        'description' => 'Can access company asset administration.',
        'permission_keys' => ['admin.assets.view'],
    ]);

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Asset Notifications_'.uniqid(),
        'slug' => 'dashboard_only_asset_notifications_'.uniqid(),
        'description' => 'Cannot access company asset administration.',
        'permission_keys' => ['admin.dashboard.view'],
    ]);

    $assetAdmin->roles()->sync([$assetViewerRole->id]);
    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $asset = CompanyAsset::create([
        'name' => 'Laptop Aset',
        'type' => 'electronics',
        'user_id' => $employee->id,
        'date_assigned' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    app(UserAssetService::class)->requestReturnOtp($employee, $asset);

    Notification::assertSentTo($assetAdmin, AssetReturnOtpRequested::class);
    Notification::assertNotSentTo($dashboardAdmin, AssetReturnOtpRequested::class);
});
