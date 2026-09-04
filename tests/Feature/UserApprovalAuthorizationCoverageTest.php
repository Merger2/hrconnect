<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Position;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

function seedUserApprovalCoverageSettings(): void
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

function createApprovalCoverageManager(): array
{
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

    // Division / job title live on the Employee record (positions), not users.
    $managerPosition = Position::create([
        'name' => $managerTitle->name,
        'code' => 'POS_'.uniqid(),
        'division_id' => $division->id,
        'job_title_id' => $managerTitle->id,
    ]);
    $staffPosition = Position::create([
        'name' => $staffTitle->name,
        'code' => 'POS_'.uniqid(),
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    $manager = User::factory()->create();
    $managerEmployee = Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
        'position_id' => $managerPosition->id,
    ]);

    $subordinate = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create([
        'user_id' => $subordinate->id,
        'division_id' => $division->id,
        'position_id' => $staffPosition->id,
        'parent_id' => $managerEmployee->id,
    ]);

    return [$manager, $subordinate];
}

test('manager-only user routes declare explicit subordinate review middleware', function () {
    $routeCollection = collect(Route::getRoutes()->getRoutes())->keyBy(fn ($route) => $route->uri());

    foreach ([
        'approvals' => 'can:reviewTeamOrHrApprovals',
        'approvals/history' => 'can:reviewTeamOrHrApprovals',
        'team-kasbon' => 'can:reviewSubordinateRequests',
    ] as $uri => $middleware) {
        $route = $routeCollection->get($uri);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain($middleware);
    }
});

test('subordinate review gate only allows users with subordinates', function () {
    [$manager] = createApprovalCoverageManager();
    $regularUser = User::factory()->create();

    $reviewRole = Role::create([
        'name' => 'Coverage Reviewer_'.uniqid(),
        'slug' => 'coverage_reviewer_'.uniqid(),
        'permission_keys' => ['review_subordinate_requests'],
    ]);
    $manager->roles()->sync([$reviewRole->id]);

    expect(Gate::forUser($manager)->allows('reviewSubordinateRequests'))->toBeTrue()
        ->and(Gate::forUser($regularUser)->allows('reviewSubordinateRequests'))->toBeFalse();
});

test('admin hr role can open approval entry points through hr approval gate', function () {
    seedUserApprovalCoverageSettings();

    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Admin HR Approver_'.uniqid(),
        'slug' => 'admin_hr_approver_'.uniqid(),
        'permission_keys' => ['manage_leave_approvals'],
    ]);
    $admin->roles()->sync([$role->id]);

    expect(Gate::forUser($admin->fresh())->allows('reviewTeamOrHrApprovals'))->toBeTrue();

    $this->actingAs($admin->fresh())
        ->get(route('approvals'))
        ->assertOk();

    $this->actingAs($admin->fresh())
        ->get(route('approvals.history'))
        ->assertOk();
});

test('direct manager assignment overrides inferred division hierarchy for approvals', function () {
    [$inferredManager, $employee] = createApprovalCoverageManager();
    $directManager = User::factory()->create();

    $employee->forceFill(['manager_id' => $directManager->id])->save();

    $directReportIds = User::query()->where('manager_id', $directManager->id)->pluck('id')->all();
    $inferredReportIds = User::query()->where('manager_id', $inferredManager->id)->pluck('id')->all();

    expect($employee->refresh()->supervisor?->id)->toBe($directManager->id)
        ->and($directReportIds)->toContain($employee->id)
        ->and($inferredReportIds)->not->toContain($employee->id);
});

test('manager-only user pages reject users without subordinate review access', function () {
    seedUserApprovalCoverageSettings();

    $regularUser = User::factory()->create();

    $this->actingAs($regularUser)
        ->get(route('approvals'))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('approvals.history'))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('team-kasbon'))
        ->assertForbidden();
});

test('manager-only user shortcuts are hidden unless subordinate review access exists', function () {
    seedUserApprovalCoverageSettings();

    [$manager] = createApprovalCoverageManager();
    $reviewRole = Role::create([
        'name' => 'Coverage Reviewer 2_'.uniqid(),
        'slug' => 'coverage_reviewer_2_'.uniqid(),
        'permission_keys' => ['review_subordinate_requests'],
    ]);
    $manager->roles()->sync([$reviewRole->id]);

    $regularUser = User::factory()->create();

    $this->actingAs($regularUser)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText(__('Approvals'))
        ->assertDontSeeText(__('Team Kasbon'));

    $this->actingAs($manager)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeText(__('Approvals'))
        ->assertSeeText(__('Team Kasbon'));
});
