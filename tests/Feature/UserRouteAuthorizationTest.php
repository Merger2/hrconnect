<?php

use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\FaceDescriptor;
use App\Models\Overtime;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use App\Support\EnterpriseRuntime;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function () {});

test('user self-service finance and overtime routes reject admin accounts', function (string $routeName) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'overtime',
    'my-kasbon',
]);

test('employee accounts can open self-service finance and overtime routes', function () {

    $employee = User::factory()->create();

    $this->actingAs($employee)
        ->get(route('overtime'))
        ->assertOk();

    if (! EnterpriseRuntime::sourceAvailable(probeClass: 'App\\Livewire\\User\\Finance\\TeamCashAdvanceManager')) {
        return;
    }

    $this->actingAs($employee)
        ->get(route('my-kasbon'))
        ->assertOk();
});

test('self-service policies only expose overtime and cash advance indexes to employees', function () {
    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($employee)->allows('viewAny', Overtime::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', Overtime::class))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('viewAny', CashAdvance::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', CashAdvance::class))->toBeFalse();
});

test('core self-service routes declare user or inbox-safe middleware coverage', function () {
    $routeCollection = collect(Route::getRoutes()->getRoutes())->keyBy(fn ($route) => $route->getName());

    foreach (['home', 'my-schedule', 'face.enrollment'] as $routeName) {
        expect($routeCollection->get($routeName)?->gatherMiddleware())
            ->toContain('user');
    }

    expect($routeCollection->get('notifications')?->gatherMiddleware())
        ->toContain('auth:sanctum')
        ->toContain('verified');
});

test('admin accounts cannot open employee-only home schedule or face enrollment routes', function (string $routeName) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'home',
    'my-schedule',
    'face.enrollment',
]);

test('my schedule only renders schedules owned by the current user', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $ownShift = Shift::factory()->create(['name' => 'Visible Own Shift']);
    $otherShift = Shift::factory()->create(['name' => 'Hidden Coworker Shift']);

    Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $ownShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    Schedule::create([
        'user_id' => $otherEmployee->id,
        'shift_id' => $otherShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($employee)
        ->get(route('my-schedule'))
        ->assertOk()
        ->assertSee('Visible Own Shift')
        ->assertDontSee('Hidden Coworker Shift');
});

test('home page renders only the current user identity', function () {
    $employee = User::factory()->create(['name' => 'Visible Home User']);
    $otherEmployee = User::factory()->create(['name' => 'Hidden Home User']);

    $this->actingAs($employee)
        ->get(route('home'))
        ->assertOk()
        ->assertSee($employee->name)
        ->assertDontSee($otherEmployee->name);
});

test('face enrollment state is scoped to the current user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);

    FaceDescriptor::create([
        'employee_id' => $otherEmployee->id,
        'embedding' => array_fill(0, 128, 0.1),
        'is_active' => true,
    ]);

    $currentEmployee = Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('face.enrollment'))
        ->assertOk()
        ->assertDontSee(__('Face ID Active'));

    FaceDescriptor::create([
        'employee_id' => $currentEmployee->id,
        'embedding' => array_fill(0, 128, 0.2),
        'is_active' => true,
    ]);

    $this->get(route('face.enrollment'))
        ->assertOk()
        ->assertSee(__('Face ID Active'));
});

test('notifications page only renders notifications for the current user', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $employee->id,
        'data' => [
            'title' => 'Visible Employee Notification',
            'message' => 'This notification belongs to the signed in user.',
        ],
    ]);

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $otherEmployee->id,
        'data' => [
            'title' => 'Hidden Coworker Notification',
            'message' => 'This notification belongs to another user.',
        ],
    ]);

    $this->actingAs($employee)
        ->get(route('notifications'))
        ->assertOk()
        ->assertSee('Visible Employee Notification')
        ->assertDontSee('Hidden Coworker Notification');
});
