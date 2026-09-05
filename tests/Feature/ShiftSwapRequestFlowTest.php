<?php

use App\Livewire\Admin\ShiftSwapApprovalManager;
use App\Livewire\User\ShiftSwapRequestPage;
use App\Livewire\User\TeamApprovals;
use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Support\ShiftSwapRequestService;
use App\Support\TeamApprovalQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createShiftSwapApprovalHierarchy(): array
{
    $division = Division::create(['name' => 'Store Operations', 'code' => 'STO_'.uniqid()]);
    $managerLevel = JobLevel::create(['name' => 'Supervisor', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Crew', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Store Supervisor',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Store Crew',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create();
    $managerEmployee = Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
    ]);

    // TeamApprovals::mount() authorizes the reviewSubordinateRequests gate.
    $managerRole = Role::create([
        'name' => 'Shift Swap Manager_'.uniqid(),
        'slug' => 'shift_swap_manager_'.uniqid(),
        'permission_keys' => ['review_subordinate_requests'],
    ]);
    $manager->roles()->sync([$managerRole->id]);

    $employee = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create([
        'user_id' => $employee->id,
        'division_id' => $division->id,
        'parent_id' => $managerEmployee->id,
    ]);

    $replacement = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $replacement->id,
        'division_id' => $division->id,
    ]);

    return [$manager, $employee, $replacement];
}

test('employee submits a shift swap request for an upcoming schedule', function () {
    [, $employee, $replacement] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($employee);

    Livewire::test(ShiftSwapRequestPage::class)
        ->call('create')
        ->set('scheduleDate', $schedule->date->toDateString())
        ->set('requestedShiftId', $requestedShift->id)
        ->set('replacementUserId', $replacement->id)
        ->set('reason', 'Need to cover a family appointment in the morning.')
        ->call('store')
        ->assertHasNoErrors();

    $request = ShiftSwapRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->user_id)->toBe($employee->id)
        ->and($request->schedule_id)->toBe($schedule->id)
        ->and($request->current_shift_id)->toBe($currentShift->id)
        ->and($request->requested_shift_id)->toBe($requestedShift->id)
        ->and($request->replacement_user_id)->toBe($replacement->id)
        ->and($request->status)->toBe(ShiftSwapRequest::STATUS_PENDING);
});

test('employee cannot submit duplicate pending shift swap requests for the same schedule', function () {
    [, $employee] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'requester_id' => $employee->employee->id,
        'target_id' => $employee->employee->id,
        'schedule_id' => $schedule->id,
        'schedule_date' => $schedule->date->toDateString(),
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Existing request.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($employee);

    Livewire::test(ShiftSwapRequestPage::class)
        ->call('create')
        ->set('scheduleId', $schedule->id)
        ->set('requestedShiftId', $requestedShift->id)
        ->set('reason', 'Trying another request.')
        ->call('store')
        ->assertHasErrors(['scheduleId']);

    expect(ShiftSwapRequest::count())->toBe(1);
});

test('employee can request a shift for an empty schedule date and approval creates the schedule', function () {
    [$manager, $employee] = createShiftSwapApprovalHierarchy();
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);
    $requestDate = now()->addDays(4)->toDateString();

    $this->actingAs($employee);

    Livewire::test(ShiftSwapRequestPage::class)
        ->call('create')
        ->set('scheduleDate', $requestDate)
        ->set('requestedShiftId', $requestedShift->id)
        ->set('reason', 'Need to add a work schedule for this date.')
        ->call('store')
        ->assertHasNoErrors();

    $request = ShiftSwapRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->schedule_id)->toBeNull()
        ->and($request->schedule_date->toDateString())->toBe($requestDate)
        ->and($request->current_shift_id)->toBeNull()
        ->and(Schedule::query()->where('user_id', $employee->id)->whereDate('date', $requestDate)->exists())->toBeFalse();

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'shift-swaps')
        ->call('approveShiftSwap', $request->id);

    $request->refresh();
    $schedule = Schedule::query()
        ->where('user_id', $employee->id)
        ->whereDate('date', $requestDate)
        ->first();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_APPROVED_L1)
        ->and($request->schedule_id)->toBe($schedule->id)
        ->and($schedule->shift_id)->toBe($requestedShift->id)
        ->and($schedule->is_off)->toBeFalse();
});

test('manager approval updates the employee schedule and stores approval history', function () {
    [$manager, $employee, $replacement] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Night', 'start_time' => '23:00', 'end_time' => '07:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDays(2)->toDateString(),
    ]);

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'requester_id' => $employee->employee->id,
        'target_id' => $replacement->employee->id,
        'schedule_id' => $schedule->id,
        'schedule_date' => $schedule->date->toDateString(),
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'replacement_user_id' => $replacement->id,
        'reason' => 'Need night coverage this week.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'shift-swaps')
        ->call('approveShiftSwap', $request->id);

    $request->refresh();
    $schedule->refresh();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_APPROVED_L1)
        ->and($request->reviewed_by)->toBe($manager->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($schedule->shift_id)->toBe($requestedShift->id);

    $history = collect(app(TeamApprovalQueryService::class)->history($manager, 'shift-swaps')->items());

    expect($history->pluck('id'))->toContain($request->id);
});

test('manager rejection keeps the original schedule unchanged', function () {
    [$manager, $employee] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Night', 'start_time' => '23:00', 'end_time' => '07:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDays(3)->toDateString(),
    ]);

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'requester_id' => $employee->employee->id,
        'target_id' => $employee->employee->id,
        'schedule_id' => $schedule->id,
        'schedule_date' => $schedule->date->toDateString(),
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Need night coverage this week.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'shift-swaps')
        ->call('rejectShiftSwap', $request->id);

    $request->refresh();
    $schedule->refresh();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_REJECTED)
        ->and($request->reviewed_by)->toBe($manager->id)
        ->and($schedule->shift_id)->toBe($currentShift->id);
});

test('admin approval page can approve empty date shift swap requests', function () {
    [, $employee] = createShiftSwapApprovalHierarchy();
    $admin = User::factory()->admin()->create();
    $adminRole = Role::create([
        'name' => 'Shift Swap Admin_'.uniqid(),
        'slug' => 'shift_swap_admin_'.uniqid(),
        'permission_keys' => ['manage_shift_swap_approvals', 'admin.scope.global'],
    ]);
    $admin->roles()->sync([$adminRole->id]);

    $requestedShift = Shift::create(['name' => 'Evening', 'start_time' => '16:00', 'end_time' => '00:00']);
    $requestDate = now()->addDays(5)->toDateString();

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'requester_id' => $employee->employee->id,
        'target_id' => $employee->employee->id,
        'schedule_date' => $requestDate,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Admin approval route coverage.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(ShiftSwapApprovalManager::class)
        ->call('approve', $request->id);

    $request->refresh();
    $schedule = Schedule::query()
        ->where('user_id', $employee->id)
        ->whereDate('date', $requestDate)
        ->first();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_APPROVED_L1)
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->schedule_id)->toBe($schedule->id)
        ->and($schedule->shift_id)->toBe($requestedShift->id);
});

test('admin superadmin and hr can open shift swap approvals page', function () {
    $admin = User::factory()->admin()->create();
    // Roleless admin tidak lolos gate route — beri role shift swap eksplisit.
    $adminRole = Role::create([
        'name' => 'Shift Swap Admin Access_'.uniqid(),
        'slug' => 'shift_swap_admin_access_'.uniqid(),
        'permission_keys' => ['manage_shift_swap_approvals'],
    ]);
    $admin->roles()->sync([$adminRole->id]);
    $superadmin = User::factory()->admin(true)->create();
    $hr = User::factory()->admin()->create();
    // Role teknis 'hr' dibuat self-contained — test tidak bergantung pada seed.
    // (Role hr-manager sudah dihapus dari seeder; HRD memakai role admin.)
    $hrRole = Role::query()->firstOrCreate(
        ['name' => 'hr', 'guard_name' => 'web'],
        ['slug' => 'hr', 'permission_keys' => ['manage_shift_swap_approvals']],
    );

    $hr->roles()->sync([$hrRole->id]);

    $this->actingAs($admin)
        ->get(route('admin.shift-swaps'))
        ->assertOk();

    $this->actingAs($superadmin)
        ->get(route('admin.shift-swaps'))
        ->assertOk();

    $this->actingAs($hr)
        ->get(route('admin.shift-swaps'))
        ->assertOk();
});

test('shift swap approval service ignores already reviewed requests', function () {
    [$manager, $employee] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Evening', 'start_time' => '15:00', 'end_time' => '23:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDays(6)->toDateString(),
    ]);

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'requester_id' => $employee->employee->id,
        'target_id' => $employee->employee->id,
        'schedule_id' => $schedule->id,
        'schedule_date' => $schedule->date->toDateString(),
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Already approved request.',
        'status' => ShiftSwapRequest::STATUS_APPROVED,
        'reviewed_by' => $manager->id,
        'reviewed_at' => now(),
    ]);

    $message = app(ShiftSwapRequestService::class)->approve($request, $manager);

    expect($message)->toBe(__('You are not allowed to review this shift swap request.'))
        ->and($request->fresh()->status)->toBe(ShiftSwapRequest::STATUS_APPROVED)
        ->and($schedule->fresh()->shift_id)->toBe($currentShift->id);
});

test('management query searches shift swap requests by nip division position and requested shift', function () {
    [$manager, $employee] = createShiftSwapApprovalHierarchy();
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'requester_id' => $employee->employee->id,
        'target_id' => $employee->employee->id,
        'schedule_date' => now()->addDays(7)->toDateString(),
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Search coverage request.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $service = app(ShiftSwapRequestService::class);
    $employeeRecord = $employee->employee;

    // Position factory tidak mengisi job_title_id — hubungkan ke JobTitle
    // supaya proxy User::jobTitle (employee.position.jobTitle) ter-resolve
    // dan assertion eager-load bermakna (bukan null vs null).
    $positionTitle = JobTitle::create([
        'name' => 'Store Crew Search',
        'division_id' => $employeeRecord->division_id,
    ]);
    $employeeRecord->position->forceFill(['job_title_id' => $positionTitle->id])->save();

    // getRawOriginal('nip') returns the physical employees.nip column — the
    // getNipAttribute() accessor proxies to employee_number instead.
    $nip = $employeeRecord->getRawOriginal('nip');

    // get() (not pluck) executes the eager loads, so a regression to the old
    // 'user.division'/'user.jobTitle' relation names would throw here.
    $byNip = $service->managementQuery($manager, 'all', $nip)->get();
    expect($byNip->pluck('id'))->toContain($request->id)
        ->and($byNip->first()->user->division?->name)->toBe($employeeRecord->division->name)
        ->and($byNip->first()->user->jobTitle?->name)->toBe($employeeRecord->position->jobTitle?->name);

    $byDivision = $service->managementQuery($manager, 'all', $employeeRecord->division->name)->get();
    expect($byDivision->pluck('id'))->toContain($request->id);

    // jobTitle proxies to employee.position — search must go through it.
    $byPosition = $service->managementQuery($manager, 'all', $employeeRecord->position->name)->get();
    expect($byPosition->pluck('id'))->toContain($request->id);

    $byRequestedShift = $service->managementQuery($manager, 'all', $requestedShift->name)->get();
    expect($byRequestedShift->pluck('id'))->toContain($request->id);

    // Unrelated term must not match, and the eager loads (user.employee.division
    // / position) must not throw RelationNotFoundException.
    $unrelated = $service->managementQuery($manager, 'all', 'zzz-no-match-'.uniqid())->get();
    expect($unrelated->pluck('id'))->not->toContain($request->id);
});
