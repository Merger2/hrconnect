<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedUserMenuSmokeSettings(): void
{
    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean', 'description' => 'Require Face ID enrollment before attendance']
    );

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean', 'description' => 'Require Face ID verification during attendance capture']
    );

    Setting::flushCache();
}

test('core user menu pages resolve cleanly for a regular user', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user);

    $routes = [
        'home',
        'apply-leave',
        'attendance-history',
        'attendance-corrections',
        'scan',
        'notifications',
        'reimbursement',
        'my-schedule',
        'shift-swap-requests',
        'document-requests',
        'overtime',
        'face.enrollment',
        'profile.show',
        'my-payslips',
        'my-assets',
        'my-performance',
        'my-kasbon',
        'hr-tasks',
        'collaboration',
    ];

    foreach ($routes as $routeName) {
        $this->followingRedirects()
            ->get(route($routeName))
            ->assertOk();
    }
});

test('hr tasks quick action is visible for regular users', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(__('HR Tasks'))
        ->assertSee(route('hr-tasks'), false);
});

test('manager-only user menu pages resolve cleanly for a supervisor', function () {
    seedUserMenuSmokeSettings();

    $division = Division::create([
        'name' => 'Operations',
        'code' => 'OPS_'.uniqid(),
    ]);

    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $managerRole = Role::create([
        'name' => 'Supervisor Menu Smoke_'.uniqid(),
        'slug' => 'supervisor_menu_smoke_'.uniqid(),
        'description' => 'Can review subordinate requests.',
        'permission_keys' => ['review_subordinate_requests'],
    ]);

    $manager = User::factory()->create();
    Employee::factory()->create(['user_id' => $manager->id, 'division_id' => $division->id]);
    $manager->roles()->sync([$managerRole->id]);

    $staff = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create(['user_id' => $staff->id, 'division_id' => $division->id]);

    $this->actingAs($manager);

    $routes = [
        'approvals',
        'approvals.history',
        'team-kasbon',
    ];

    foreach ($routes as $routeName) {
        $this->followingRedirects()
            ->get(route($routeName))
            ->assertOk();
    }
});

test('regular users keep a simplified shared navigation shell', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee(__('Toggle navigation menu'), false)
        ->assertDontSee(__('Open account menu'));
});

test('profile page exposes identity stats and face id management', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee(__('Face ID'))
        ->assertSee(__('Leave Days'));
});

test('admin navbar does not show language or theme toggles', function () {
    seedUserMenuSmokeSettings();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('language-toggle', false)
        ->assertDontSee('theme-switcher-desktop', false)
        ->assertDontSee('theme-switcher-mobile', false);
});

test('hr checklist admin menu visibility follows rbac permission', function () {
    seedUserMenuSmokeSettings();

    $dashboardOnly = User::factory()->admin()->create();
    $hrChecklistViewer = User::factory()->admin()->create();

    $dashboardRole = Role::create([
        'name' => 'Dashboard Only Menu Smoke_'.uniqid(),
        'slug' => 'dashboard_only_menu_smoke_'.uniqid(),
        'description' => 'Can open the admin dashboard only.',
        'permission_keys' => ['view_admin_dashboard'],
    ]);

    $hrChecklistRole = Role::create([
        'name' => 'HR Checklist Menu Viewer_'.uniqid(),
        'slug' => 'hr_checklist_menu_viewer_'.uniqid(),
        'description' => 'Can open the admin dashboard and HR checklist menu.',
        'permission_keys' => [
            'view_admin_dashboard',
            'view_hr_checklists',
        ],
    ]);

    $dashboardOnly->roles()->sync([$dashboardRole->id]);
    $hrChecklistViewer->roles()->sync([$hrChecklistRole->id]);

    $this->actingAs($dashboardOnly)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(__('HR Checklists'));

    $this->actingAs($hrChecklistViewer)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('HR Checklists'))
        ->assertSee(route('admin.hr-checklists'), false);
});

test('admin profile page uses the admin profile route and shell', function () {
    seedUserMenuSmokeSettings();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.profile.show'))
        ->assertOk()
        ->assertSee(__('Admin Profile'))
        ->assertSee(route('admin.profile.show'), false)
        ->assertDontSee('href="'.route('profile.show').'"', false);
});

test('admin users are redirected from the user profile page to the admin profile page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('profile.show'))
        ->assertRedirect(route('admin.profile.show'));
});
