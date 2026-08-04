<?php

use App\Enums\AttendanceStatus;
use App\Models\Announcement;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {});

test('policies cover attendance appraisal reimbursement asset and payslip access', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $limitedAdmin = User::factory()->admin()->create();
    $appraisalAdmin = User::factory()->admin()->create();
    $assetPayrollAdmin = User::factory()->admin()->create();
    $appraisalRole = Role::create([
        'name' => 'Policy Appraisal Viewer_'.uniqid(),
        'slug' => 'policy_appraisal_viewer__'.uniqid().uniqid(),
        'description' => 'Can access appraisal administration for policy coverage tests.',
        'permission_keys' => ['admin.appraisals.view'],
    ]);
    $assetPayrollRole = Role::create([
        'name' => 'Asset Payroll Viewer_'.uniqid(),
        'slug' => 'asset_payroll_viewer__'.uniqid().uniqid(),
        'description' => 'Can access payroll and company asset administration.',
        // view_payrolls → legacy alias 'admin.payrolls.view' (plural, sesuai
        // legacyAdminPermissionKey) — 'admin.payroll.view' singular tidak match.
        'permission_keys' => [
            'admin.assets.view',
            'admin.payrolls.view',
        ],
    ]);

    // admin = admin dengan role berpermission eksplisit (strict RBAC, bukan
    // superadmin bypass & bukan roleless read-only fallback).
    $adminRole = Role::create([
        'name' => 'Policy Attendance Admin_'.uniqid(),
        'slug' => 'policy_attendance_admin_'.uniqid(),
        'description' => 'Can view attendance and reimbursement records.',
        'permission_keys' => ['view_attendances', 'view_reimbursements'],
    ]);
    $admin->roles()->sync([$adminRole->id]);

    // limitedAdmin = admin dengan role tapi TANPA akses appraisal (roleless admin
    // justru dapat read-only legacy fallback → export appraisal bocor).
    $limitedAdminRole = Role::create([
        'name' => 'Policy Dashboard Only_'.uniqid(),
        'slug' => 'policy_dashboard_only_'.uniqid(),
        'description' => 'No appraisal access.',
        'permission_keys' => ['admin.dashboard.view'],
    ]);
    $limitedAdmin->roles()->sync([$limitedAdminRole->id]);
    $appraisalAdmin->roles()->sync([$appraisalRole->id]);
    $assetPayrollAdmin->roles()->sync([$assetPayrollRole->id]);

    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);

    // appraisals.reviewer_id → constrained('employees') — pakai employee id,
    // bukan user id (evaluator_id → users, reviewer_id → employees).
    $adminEmployee = Employee::factory()->create(['user_id' => $admin->id]);

    $attendance = Attendance::create([
        'employee_id' => $ownerEmployee->id,
        'date' => now()->toDateString(),
        'status' => 'excused',
        'note' => 'Family matter',
        'attachment' => 'secure/attendance-proof.pdf',
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $ownerEmployee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'medical',
        'amount' => 150000,
        'description' => 'Clinic reimbursement',
        'attachment_path' => 'secure/reimbursement-proof.pdf',
        'status' => 'pending',
    ]);

    $selfAssessment = Appraisal::create([
        'employee_id' => $ownerEmployee->id,
        'evaluator_id' => $admin->id,
        'reviewer_id' => $adminEmployee->id,
        'period' => '2026-01',
        'review_date' => now(),
        'status' => 'self_assessment',
    ]);

    $completedAppraisal = Appraisal::create([
        'employee_id' => $ownerEmployee->id,
        'evaluator_id' => $admin->id,
        'reviewer_id' => $adminEmployee->id,
        'period' => '2026-02',
        'review_date' => now(),
        'status' => 'completed',
    ]);

    $asset = CompanyAsset::create([
        'name' => 'Laptop Kerja',
        'type' => 'electronics',
        'user_id' => $owner->id,
        'date_assigned' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    $payroll = Payroll::create([
        'employee_id' => $ownerEmployee->id,
        'period' => '2026-01',
        'basic_salary' => 5000000,
        'total_allowance' => 0,
        'gross_salary' => 5000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    expect(Gate::forUser($owner)->allows('view', $attendance))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $attendance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $attendance))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $reimbursement))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $reimbursement))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $reimbursement))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('exportPdf', $selfAssessment))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('exportPdf', $selfAssessment))->toBeFalse()
        ->and(Gate::forUser($limitedAdmin)->allows('exportPdf', $selfAssessment))->toBeFalse()
        ->and(Gate::forUser($appraisalAdmin)->allows('exportPdf', $selfAssessment))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $asset))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $asset))->toBeFalse()
        ->and(Gate::forUser($assetPayrollAdmin)->allows('view', $asset))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('download', $payroll))->toBeFalse()
        ->and(Gate::forUser($assetPayrollAdmin)->allows('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('selfAssess', $selfAssessment))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('acknowledge', $selfAssessment))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('acknowledge', $completedAppraisal))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('returnAsset', $asset))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('returnAsset', $asset))->toBeFalse();
});

test('backup policy requires explicit maintenance access', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $maintenanceViewer = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Maintenance Viewer_'.uniqid(),
        'slug' => 'policy_maintenance_viewer_'.uniqid(),
        'description' => 'Can view system maintenance.',
        'permission_keys' => ['admin.system_maintenance.view'],
    ]);

    $maintenanceViewer->roles()->sync([$role->id]);

    expect(Gate::forUser($user)->allows('viewAny', SystemBackupRun::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAny', SystemBackupRun::class))->toBeFalse()
        ->and(Gate::forUser($maintenanceViewer)->allows('viewAny', SystemBackupRun::class))->toBeTrue();
});

test('announcement and holiday policies only allow admins to manage records', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    // Roleless admin hanya read-only (fix P0) — admin yang bisa manage harus
    // punya role dengan permission eksplisit (strict RBAC).
    $adminRole = Role::create([
        'name' => 'Policy Announcement Admin_'.uniqid(),
        'slug' => 'policy_announcement_admin_'.uniqid(),
        'description' => 'Can manage announcements and holidays.',
        'permission_keys' => ['manage_announcements', 'manage_holidays'],
    ]);
    $admin->roles()->sync([$adminRole->id]);

    $announcement = Announcement::create([
        'title' => 'Office Update',
        'content' => 'Please check the new schedule.',
        'priority' => 'medium',
        'modal_behavior' => 'acknowledge',
        'published_at' => now(),
        'is_active' => true,
        'created_by' => $admin->id,
    ]);

    $holiday = Holiday::create([
        'date' => now()->addWeek()->toDateString(),
        'name' => 'Company Leave',
        'description' => 'Policy coverage test.',
        'is_recurring' => false,
    ]);

    expect(Gate::forUser($user)->allows('create', Announcement::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $announcement))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $announcement))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', Holiday::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $holiday))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $holiday))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', Announcement::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $announcement))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $announcement))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Holiday::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $holiday))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $holiday))->toBeTrue();
});

test('attachment and appraisal export routes deny unrelated users', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $adminEmployee = Employee::factory()->create(['user_id' => $admin->id]);

    $attendance = Attendance::create([
        'employee_id' => $ownerEmployee->id,
        'date' => now()->toDateString(),
        'status' => 'excused',
        'note' => 'Personal matter',
        'attachment' => 'secure/attendance-proof.pdf',
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $ownerEmployee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'transport',
        'amount' => 50000,
        'description' => 'Taxi reimbursement',
        'attachment_path' => 'secure/reimbursement-proof.pdf',
        'status' => 'pending',
    ]);

    $appraisal = Appraisal::create([
        'employee_id' => $ownerEmployee->id,
        'evaluator_id' => $admin->id,
        'reviewer_id' => $adminEmployee->id,
        'period' => '2026-03',
        'review_date' => now(),
        'status' => 'completed',
    ]);

    $this->actingAs($otherUser)
        ->get(route('attendance.attachment.download', $attendance))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->get(route('appraisal.export-pdf', $appraisal))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('attendance.attachment.download', $attendance))
        ->assertNotFound();

    $this->actingAs($owner)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertNotFound();
});

test('attendance approval policy allows supervisors to review subordinate requests only', function () {
    $division = Division::create(['name' => 'Operations', 'code' => 'OPS_'.uniqid()]);
    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Operations Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Operations Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
    ]);

    $subordinate = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create([
        'user_id' => $subordinate->id,
        'division_id' => $division->id,
        // canReview() memeriksa subordinates via employees.parent_id.
        'parent_id' => $manager->employee->id,
    ]);

    $unrelated = User::factory()->create();

    $attendance = Attendance::create([
        'employee_id' => $subordinate->employee->id,
        'user_id' => $subordinate->id,
        'date' => now()->toDateString(),
        // 'late' termasuk Attendance::REQUEST_STATUSES (yang bisa di-approve).
        'status' => AttendanceStatus::LATE->value,
        'approval_status' => Attendance::STATUS_PENDING,
        'note' => 'Family event',
    ]);

    expect(Gate::forUser($manager)->allows('approve', $attendance))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('reject', $attendance))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('view', $attendance))->toBeTrue()
        ->and(Gate::forUser($unrelated)->allows('approve', $attendance))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('reject', $attendance))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('view', $attendance))->toBeFalse();
});

test('cash advance policy matches approver scope and keeps delete admin only', function () {
    $division = Division::create(['name' => 'Operations', 'code' => 'OPS_'.uniqid()]);
    $financeDivision = Division::create(['name' => 'Finance', 'code' => 'FIN_'.uniqid()]);
    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Operations Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $financeTitle = JobTitle::create([
        'name' => 'Finance Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $financeDivision->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Operations Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    // Employee::factory() membuat Position default tapi job_title_id null →
    // user->jobTitle (employee->position->jobTitle) null → rank null → policy
    // canManage() menolak. Assign JobTitle ke Position agar hierarki resolvable.
    $managerPosition = Position::factory()->create(['division_id' => $division->id]);
    $managerPosition->forceFill(['job_title_id' => $managerTitle->id])->save();

    $financePosition = Position::factory()->create(['division_id' => $financeDivision->id]);
    $financePosition->forceFill(['job_title_id' => $financeTitle->id])->save();

    $staffPosition = Position::factory()->create(['division_id' => $division->id]);
    $staffPosition->forceFill(['job_title_id' => $staffTitle->id])->save();

    $manager = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
        'position_id' => $managerPosition->id,
    ]);

    $financeHead = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $financeHead->id,
        'division_id' => $financeDivision->id,
        'position_id' => $financePosition->id,
    ]);

    $subordinate = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create([
        'user_id' => $subordinate->id,
        'division_id' => $division->id,
        'position_id' => $staffPosition->id,
    ]);

    $unrelated = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $cashAdvanceAdmin = User::factory()->admin()->create();
    $cashAdvanceRole = Role::create([
        'name' => 'Cash Advance Approver_'.uniqid(),
        'slug' => 'cash_advance_approver_'.uniqid(),
        'description' => 'Can manage cash advance approvals.',
        'permission_keys' => ['admin.cash_advances.manage'],
    ]);

    $cashAdvanceAdmin->roles()->sync([$cashAdvanceRole->id]);

    $pendingAdvance = CashAdvance::create([
        'user_id' => $subordinate->id,
        'amount' => 500000,
        'purpose' => 'Field allowance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $pendingFinanceAdvance = CashAdvance::create([
        'user_id' => $subordinate->id,
        'amount' => 750000,
        'purpose' => 'Client visit',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending_finance',
    ]);

    expect(Gate::forUser($manager)->allows('approve', $pendingAdvance))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('reject', $pendingAdvance))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($financeHead)->allows('approve', $pendingFinanceAdvance))->toBeTrue()
        ->and(Gate::forUser($financeHead)->allows('reject', $pendingFinanceAdvance))->toBeTrue()
        ->and(Gate::forUser($financeHead)->allows('delete', $pendingFinanceAdvance))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('approve', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('reject', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($unrelated)->allows('delete', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('approve', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('reject', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $pendingAdvance))->toBeFalse()
        ->and(Gate::forUser($cashAdvanceAdmin)->allows('approve', $pendingAdvance))->toBeTrue()
        ->and(Gate::forUser($cashAdvanceAdmin)->allows('reject', $pendingAdvance))->toBeTrue()
        ->and(Gate::forUser($cashAdvanceAdmin)->allows('delete', $pendingAdvance))->toBeTrue();
});
