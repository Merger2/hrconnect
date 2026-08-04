<?php

use App\Contracts\AuditServiceInterface;
use App\Livewire\Admin\AppraisalManager;
use App\Livewire\Admin\AttendanceCorrectionManager;
use App\Livewire\Admin\MasterData\Admin as AdminDirectory;
use App\Livewire\Admin\ReimbursementManager;
use App\Models\ActivityLog;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Notifications\CashAdvanceRequested;
use App\Support\EnterpriseRuntime;
use App\Support\UserNotificationRecipientService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // The suite runs with RefreshDatabase against a dedicated test DB, so the
    // default role rows (super-admin) are not present after migrate:fresh.
    // Several tests resolve Role::where('slug', 'super-admin') or mutate its
    // permission_keys — create it up front so those assertions stay valid.
    Role::firstOrCreate(
        ['slug' => 'super-admin'],
        [
            'name' => 'Super Admin',
            'description' => 'Full system access.',
            'is_super_admin' => true,
            'permission_keys' => ['*'],
        ]
    );
});

test('superadmin can access role permission management', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin)
        ->get(route('admin.roles.permissions'))
        ->assertOk();
});

test('super admin role flag grants access when stored permissions are stale', function () {
    $superadmin = User::factory()->admin(true)->create();
    Role::query()->where('slug', 'super-admin')->update(['permission_keys' => []]);

    $this->actingAs($superadmin->fresh())
        ->get(route('admin.roles.permissions'))
        ->assertOk();
});

test('superadmin group grants full menu access even when role assignments are missing or limited', function () {
    $rolelessSuperadmin = User::factory()->admin(true)->create();
    $limitedRoleSuperadmin = User::factory()->admin(true)->create();
    $limitedRole = Role::create([
        'name' => 'Limited Superadmin Regression Role_'.uniqid(),
        'slug' => 'limited_superadmin_regression_role_'.uniqid(),
        'description' => 'Only includes dashboard permission.',
        'permission_keys' => ['view_admin_dashboard'],
    ]);

    $rolelessSuperadmin->roles()->detach();
    $limitedRoleSuperadmin->roles()->sync([$limitedRole->id]);

    foreach ([$rolelessSuperadmin->fresh(), $limitedRoleSuperadmin->fresh()] as $superadmin) {
        expect($superadmin->hasPermission('manage_rbac'))->toBeTrue()
            ->and(Gate::forUser($superadmin)->allows('manageRbac'))->toBeTrue()
            ->and(Gate::forUser($superadmin)->allows('viewEmployees'))->toBeTrue()
            ->and(Gate::forUser($superadmin)->allows('viewAdminSettings'))->toBeTrue();

        $this->actingAs($superadmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.employees'))
            ->assertSee(route('admin.settings'))
            ->assertSee(route('admin.roles.permissions'));
    }
});

test('admin permissions survive partially loaded role relations', function () {
    $admin = User::factory()->admin()->create();
    $admin = User::query()->with('roles:id,slug')->findOrFail($admin->id);

    expect($admin->canAccessAdminPanel())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('legacy roleless admins can still access the dashboard', function (bool $superadmin) {
    $admin = User::factory()->admin($superadmin)->create();
    $admin->roles()->detach();

    expect($admin->fresh()->canAccessAdminPanel())->toBeTrue()
        ->and(Gate::forUser($admin->fresh())->allows('viewAdminDashboard'))->toBeTrue();

    $this->actingAs($admin->fresh())
        ->get(route('admin.dashboard'))
        ->assertOk();
})->with([false, true]);

test('legacy roleless admins do not receive unrelated RBAC permissions', function () {
    $admin = User::factory()->admin()->create();
    $admin->roles()->detach();

    $this->actingAs($admin->fresh())
        ->get(route('admin.roles.permissions'))
        ->assertForbidden();
});

test('admins with stale roles can still access the dashboard', function (bool $superadmin) {
    $admin = User::factory()->admin($superadmin)->create();
    $role = Role::create([
        'name' => 'Stale Admin Role_'.uniqid(),
        'slug' => 'stale_admin_role__'.uniqid().$admin->id,
        'description' => 'Missing dashboard permission.',
        'permission_keys' => [],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.dashboard'))
        ->assertOk();
})->with([false, true]);

test('dashboard response is not blocked when activity logging fails', function () {
    $admin = User::factory()->admin(true)->create();

    app()->instance(AuditServiceInterface::class, new class implements AuditServiceInterface
    {
        public function record(string $action, ?string $description = null)
        {
            throw new AuthorizationException('Activity logs are append-only and cannot be modified.');
        }

        public function getTrail(array $filters = []): array
        {
            return [];
        }
    });

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('activity log record failures are contained globally', function () {
    app()->instance(AuditServiceInterface::class, new class implements AuditServiceInterface
    {
        public function record(string $action, ?string $description = null)
        {
            throw new AuthorizationException('Activity logs are append-only and cannot be modified.');
        }

        public function getTrail(array $filters = []): array
        {
            return [];
        }
    });

    expect(ActivityLog::record('Any Action', 'Any description'))->toBeNull();
});

test('unauthorized admin cannot access role permission management', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.roles.permissions'))
        ->assertForbidden();
});

test('explicitly authorized admin can access role permission management', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Access Manager_'.uniqid(),
        'slug' => 'access_manager_'.uniqid(),
        'description' => 'Can manage access roles.',
        'permission_keys' => ['view_admin_dashboard', 'manage_rbac'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.roles.permissions'))
        ->assertOk();
});

test('assigned role permission grants menu access and blocks unrelated admin modules', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Limited Finance_'.uniqid(),
        'slug' => 'limited_finance_'.uniqid(),
        'description' => 'Can only open reimbursements.',
        'permission_keys' => ['view_admin_dashboard', 'view_reimbursements'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.reimbursements'))
        ->assertOk()
        ->assertSee(route('admin.reimbursements'))
        ->assertDontSee(route('admin.employees'))
        ->assertDontSee(route('admin.roles.permissions'));

    $this->actingAs($admin)
        ->get(route('admin.employees'))
        ->assertForbidden();
});

test('users cannot change their own role assignment', function () {
    $superadmin = User::factory()->admin(true)->create();
    $role = Role::create([
        'name' => 'HR Custom_'.uniqid(),
        'slug' => 'hr_custom_'.uniqid(),
        'description' => 'Temporary role for testing.',
        'permission_keys' => ['view_admin_dashboard', 'view_employees'],
    ]);

    Livewire::actingAs($superadmin)
        ->test(AdminDirectory::class)
        ->call('edit', $superadmin->id)
        ->set('form.role_ids', [$role->id])
        ->call('update')
        ->assertForbidden();
});

test('non superadmin cannot assign the super admin role', function () {
    $roleManager = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create();
    $accessRole = Role::create([
        'name' => 'Role Assigner_'.uniqid(),
        'slug' => 'role_assigner_'.uniqid(),
        'description' => 'Can manage admin accounts and assign roles.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_admin_accounts',
            'manage_user_record',
            'admin.rbac.assign',
        ],
    ]);

    $roleManager->roles()->sync([$accessRole->id]);

    $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();

    Livewire::actingAs($roleManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetAdmin->id)
        ->set('form.address', 'Jl. Role Manager No. 1')
        ->set('form.role_ids', [$superAdminRole->id])
        ->call('update')
        ->assertForbidden();
});

test('admin account manager role can update another non superadmin admin account', function () {
    $roleManager = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create([
        'name' => 'Original Admin Name',
    ]);

    $accessRole = Role::create([
        'name' => 'Admin Account Manager_'.uniqid(),
        'slug' => 'admin_account_manager_'.uniqid(),
        'description' => 'Can manage administrator accounts.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_admin_accounts',
            'manage_user_record',
        ],
    ]);

    $roleManager->roles()->sync([$accessRole->id]);

    Livewire::actingAs($roleManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetAdmin->id)
        ->set('form.address', 'Jl. Update Admin No. 1')
        ->set('form.name', 'Updated Admin Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($targetAdmin->fresh()->name)->toBe('Updated Admin Name');
});

test('explicitly authorized admin can manage another superadmin account', function () {
    $superadminManager = User::factory()->admin()->create();
    $targetSuperadmin = User::factory()->admin(true)->create([
        'name' => 'Original Superadmin Name',
    ]);

    $accessRole = Role::create([
        'name' => 'Superadmin Account Manager_'.uniqid(),
        'slug' => 'superadmin_account_manager_'.uniqid(),
        'description' => 'Can view and manage superadmin accounts.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_admin_accounts',
            'manage_user_record',
            'admin.admin_accounts.superadmin_view',
            'admin.admin_accounts.superadmin_manage',
        ],
    ]);

    $superadminManager->roles()->sync([$accessRole->id]);

    expect($superadminManager->canViewSuperadminAccounts())->toBeTrue()
        ->and($superadminManager->canManageSuperadminAccounts())->toBeTrue()
        ->and(Gate::forUser($superadminManager)->allows('manageUserRecord', [$targetSuperadmin, 'superadmin']))->toBeTrue();

    Livewire::actingAs($superadminManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetSuperadmin->id)
        ->set('form.address', 'Jl. Superadmin No. 9')
        ->set('form.name', 'Updated Superadmin Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($targetSuperadmin->fresh()->name)->toBe('Updated Superadmin Name');
});

test('explicitly authorized admin can assign the super admin role', function () {
    $roleManager = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create();

    $accessRole = Role::create([
        'name' => 'Superadmin Role Assigner_'.uniqid(),
        'slug' => 'superadmin_role_assigner_'.uniqid(),
        'description' => 'Can assign roles including the super admin role.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_admin_accounts',
            'manage_user_record',
            'admin.admin_accounts.superadmin_view',
            'admin.admin_accounts.superadmin_manage',
            'admin.rbac.assign',
            'assign_roles',
        ],
    ]);

    $roleManager->roles()->sync([$accessRole->id]);

    $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();

    Livewire::actingAs($roleManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetAdmin->id)
        ->set('form.address', 'Jl. Promote Admin No. 7')
        ->set('form.role_ids', [$superAdminRole->id])
        ->call('update')
        ->assertHasNoErrors();

    expect($targetAdmin->fresh()->roles()->where('slug', 'super-admin')->exists())->toBeTrue();
});

test('assigned-role admin notifications require notifications permission', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Dashboard Only_'.uniqid(),
        'slug' => 'dashboard_only_'.uniqid(),
        'description' => 'Can only open the dashboard.',
        'permission_keys' => ['view_admin_dashboard'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.notifications'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('notifications'))
        ->assertOk();
});

test('limited admin root routes fall back to the first permitted admin page', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Notifications Only_'.uniqid(),
        'slug' => 'notifications_only_'.uniqid(),
        'description' => 'Can only access admin notifications.',
        'permission_keys' => ['manage_admin_notifications'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('admin.notifications'));

    $this->actingAs($admin)
        ->get('/')
        ->assertRedirect(route('admin.notifications'));
});

test('assigned-role admin system maintenance access requires explicit permission', function () {
    $maintenanceAdmin = User::factory()->admin()->create();
    $limitedAdmin = User::factory()->admin()->create();

    $maintenanceRole = Role::create([
        'name' => 'Maintenance Viewer_'.uniqid(),
        'slug' => 'maintenance_viewer_'.uniqid(),
        'description' => 'Can open system maintenance.',
        'permission_keys' => [
            'view_admin_dashboard',
            'admin.system_maintenance.view',
        ],
    ]);

    $limitedRole = Role::create([
        'name' => 'Limited Admin Access_'.uniqid(),
        'slug' => 'limited_admin_access_'.uniqid(),
        'description' => 'Cannot open maintenance.',
        'permission_keys' => ['view_admin_dashboard'],
    ]);

    $maintenanceAdmin->roles()->sync([$maintenanceRole->id]);
    $limitedAdmin->roles()->sync([$limitedRole->id]);

    expect(Gate::forUser($maintenanceAdmin)->allows('viewAny', SystemBackupRun::class))->toBeTrue()
        ->and(Gate::forUser($limitedAdmin)->allows('viewAny', SystemBackupRun::class))->toBeFalse();

    $maintenanceResponse = $this->actingAs($maintenanceAdmin)
        ->get(route('admin.system-maintenance'));

    if (EnterpriseRuntime::sourceAvailable(probeClass: 'App\\Livewire\\Admin\\SystemMaintenance')) {
        $maintenanceResponse->assertOk();
    } else {
        $maintenanceResponse->assertRedirect();
    }

    $this->actingAs($limitedAdmin)
        ->get(route('admin.system-maintenance'))
        ->assertForbidden();
});

test('view-only appraisal admins cannot edit or calibrate appraisals', function () {
    $admin = User::factory()->admin()->create();
    $adminEmployee = Employee::factory()->create(['user_id' => $admin->id]);
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);
    $role = Role::create([
        'name' => 'Appraisal Viewer_'.uniqid(),
        'slug' => 'appraisal_viewer_'.uniqid(),
        'description' => 'Can view appraisals without changing them.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_admin_appraisals',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    // evaluator_id references users, reviewer_id references employees.
    $appraisal = Appraisal::create([
        'employee_id' => $employeeRecord->id,
        'evaluator_id' => $admin->id,
        'reviewer_id' => $adminEmployee->id,
        'period' => now()->format('Y-m'),
        'review_date' => now(),
        'status' => 'completed',
    ]);

    expect(Gate::forUser($admin)->allows('viewAdminAny', Appraisal::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage', Appraisal::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('calibrate', $appraisal))->toBeFalse();

    if (! EnterpriseRuntime::sourceAvailable(probeClass: AppraisalManager::class)) {
        return;
    }

    Livewire::actingAs($admin)
        ->test(AppraisalManager::class)
        ->call('initOrEvaluate', $employee->id)
        ->assertForbidden();

    Livewire::actingAs($admin)
        ->test(AppraisalManager::class)
        ->call('calibrate', $appraisal->id, 'approved')
        ->assertForbidden();
});

test('explicitly authorized appraisal calibrator can approve pending calibration', function () {

    $calibrator = User::factory()->admin()->create();
    $calibratorEmployee = Employee::factory()->create(['user_id' => $calibrator->id]);
    $manager = User::factory()->admin()->create();
    $managerEmployee = Employee::factory()->create(['user_id' => $manager->id]);
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);
    $role = Role::create([
        'name' => 'Appraisal Calibrator_'.uniqid(),
        'slug' => 'appraisal_calibrator_'.uniqid(),
        'description' => 'Can calibrate completed appraisals.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_admin_appraisals',
            'admin.appraisals.calibrate',
        ],
    ]);

    $calibrator->roles()->sync([$role->id]);

    // evaluator_id references users, reviewer_id references employees.
    $appraisal = Appraisal::create([
        'employee_id' => $employeeRecord->id,
        'evaluator_id' => $manager->id,
        'reviewer_id' => $managerEmployee->id,
        'period' => now()->format('Y-m'),
        'review_date' => now(),
        'status' => 'completed',
    ]);

    Livewire::actingAs($calibrator)
        ->test(AppraisalManager::class)
        ->call('calibrate', $appraisal->id, 'approved')
        ->assertHasNoErrors();

    $appraisal->refresh();

    expect($appraisal->calibrator_id)->toBe($calibrator->id)
        ->and($appraisal->calibration_status)->toBe('approved');
});

test('view-only reimbursement admin cannot approve reimbursement requests', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);
    $role = Role::create([
        'name' => 'Reimbursement Viewer_'.uniqid(),
        'slug' => 'reimbursement_viewer_'.uniqid(),
        'description' => 'Can view reimbursements without approving them.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_reimbursements',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $employeeRecord->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Meal',
        'amount' => 75000,
        'description' => 'Lunch meeting',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reimbursements'))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(ReimbursementManager::class)
        ->assertDontSee(__('Approve this claim?'))
        ->assertDontSee(__('Reject this claim?'))
        ->call('approve', $reimbursement->id)
        ->assertForbidden();
});

test('reimbursement admin page tolerates claims whose employee record is missing', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Reimbursement Viewer With Orphans_'.uniqid(),
        'slug' => 'reimbursement_viewer_with_orphans_'.uniqid(),
        'description' => 'Can view reimbursement records.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_reimbursements',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin);

    $reimbursement = new Reimbursement([
        'employee_id' => 999999,
        'expense_date' => now()->toDateString(),
        'title' => 'Meal',
        'amount' => 75000,
        'description' => 'Claim with missing employee record',
        'status' => 'pending',
    ]);
    $reimbursement->id = 999;
    $reimbursement->setRelation('user', null);

    $html = view('livewire.admin.reimbursement-manager', [
        'reimbursements' => new LengthAwarePaginator([$reimbursement], 1, 10),
    ])->render();

    expect($html)->toContain(__('Deleted employee'))
        ->and($html)->toContain(__('Employee record not found'));
});

test('attendance correction admin with manage permission can approve pending corrections', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Correction Approver_'.uniqid(),
        'slug' => 'correction_approver_'.uniqid(),
        'description' => 'Can approve pending attendance corrections.',
        'permission_keys' => [
            'view_admin_dashboard',
            'manage_attendance_corrections',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $correction = AttendanceCorrection::create([
        'employee_id' => Employee::factory()->create(['user_id' => $employee->id])->id,
        'attendance_date' => now()->toDateString(),
        'request_type' => AttendanceCorrection::TYPE_WRONG_TIME,
        'reason' => 'Sync issue',
        'status' => AttendanceCorrection::STATUS_PENDING_ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance-corrections'))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(AttendanceCorrectionManager::class)
        ->call('approve', $correction->id)
        ->assertHasNoErrors();

    expect($correction->fresh()->status)->not->toBe(AttendanceCorrection::STATUS_PENDING_ADMIN);
});

test('admin without cash advance permission cannot manage cash advance approvals', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Dashboard Only Admin_'.uniqid(),
        'slug' => 'dashboard_only_admin_'.uniqid(),
        'description' => 'Can access admin home only.',
        'permission_keys' => ['view_admin_dashboard'],
    ]);

    $admin->roles()->sync([$role->id]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 200000,
        'purpose' => 'Travel advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    expect(Gate::forUser($admin)->allows('approve', $advance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('reject', $advance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $advance))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.manage-kasbon'))
        ->assertForbidden();
});

test('cash advance request notifications only target actual reviewers', function () {
    Notification::fake();

    $operations = Division::create(['name' => 'Operations', 'code' => 'OPS_'.uniqid()]);
    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Operations Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $operations->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Operations Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $operations->id,
    ]);

    $manager = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $operations->id,
    ]);

    $managerRole = Role::create([
        'name' => 'Supervisor Notification_'.uniqid(),
        'slug' => 'supervisor_notification_'.uniqid(),
        'description' => 'Can review subordinate requests.',
        'permission_keys' => ['review_subordinate_requests'],
    ]);
    $manager->roles()->sync([$managerRole->id]);

    $employee = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create([
        'user_id' => $employee->id,
        'division_id' => $operations->id,
    ]);

    $cashAdvanceAdmin = User::factory()->admin()->create();
    $viewOnlyAdmin = User::factory()->admin()->create();
    $cashAdvanceRole = Role::create([
        'name' => 'Cash Advance Reviewer_'.uniqid(),
        'slug' => 'cash_advance_reviewer_'.uniqid(),
        'description' => 'Can review cash advance requests.',
        'permission_keys' => ['manage_cash_advances'],
    ]);

    $viewOnlyRole = Role::create([
        'name' => 'View Only Finance_'.uniqid(),
        'slug' => 'view_only_finance_'.uniqid(),
        'description' => 'Cannot manage cash advances.',
        'permission_keys' => ['view_admin_dashboard', 'view_reimbursements'],
    ]);

    $cashAdvanceAdmin->roles()->sync([$cashAdvanceRole->id]);
    $viewOnlyAdmin->roles()->sync([$viewOnlyRole->id]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 350000,
        'purpose' => 'Field travel',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $recipientCount = app(UserNotificationRecipientService::class)->notifyCashAdvanceRequested($advance);

    expect($recipientCount)->toBe(2);

    Notification::assertSentTo($manager, CashAdvanceRequested::class, function (CashAdvanceRequested $notification, array $channels) use ($manager) {
        return $notification->toArray($manager)['url'] === route('team-kasbon', absolute: false);
    });

    Notification::assertSentTo($cashAdvanceAdmin, CashAdvanceRequested::class, function (CashAdvanceRequested $notification, array $channels) use ($cashAdvanceAdmin) {
        return $notification->toArray($cashAdvanceAdmin)['url'] === route('admin.manage-kasbon', absolute: false);
    });

    Notification::assertNotSentTo($viewOnlyAdmin, CashAdvanceRequested::class);
});

test('dashboard-only admin dashboard hides unauthorized workflow links and counts', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $role = Role::create([
        'name' => 'Dashboard Only Access_'.uniqid(),
        'slug' => 'dashboard_only_access_'.uniqid(),
        'description' => 'Can only open the dashboard.',
        'permission_keys' => ['view_admin_dashboard'],
    ]);

    $admin->roles()->sync([$role->id]);

    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);

    Attendance::create([
        'employee_id' => $employeeRecord->id,
        'date' => now()->toDateString(),
        'status' => 'sick',
        'approval_status' => 'pending',
    ]);

    Reimbursement::create([
        'employee_id' => $employeeRecord->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Meal',
        'amount' => 50000,
        'description' => 'Team lunch',
        'status' => 'pending',
    ]);

    CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 150000,
        'purpose' => 'Fuel advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('Pending Queue'))
        ->assertSee('0 '.__('total'))
        ->assertDontSee(route('admin.leaves'))
        ->assertDontSee(route('admin.attendance-corrections'))
        ->assertDontSee(route('admin.reimbursements'))
        ->assertDontSee(route('admin.overtime'))
        ->assertDontSee(route('admin.manage-kasbon'))
        ->assertDontSee(route('admin.notifications'))
        ->assertDontSee(route('admin.activity-logs'))
        ->assertDontSee(route('admin.employees'));
});

test('view-only activity log admins do not see export action', function () {
    $admin = User::factory()->admin()->create();

    $role = Role::create([
        'name' => 'Activity Log Viewer_'.uniqid(),
        'slug' => 'activity_log_viewer_'.uniqid(),
        'description' => 'Can view logs without exporting them.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_activity_logs',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.activity-logs'));

    if (! EnterpriseRuntime::sourceAvailable()) {
        $response->assertRedirect();

        return;
    }

    $response->assertOk()
        ->assertSee(__('Read-only audit access'))
        ->assertDontSee(route('admin.activity-logs.export'));
});
