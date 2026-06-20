<?php

use App\Exceptions\GeofenceViolationException;
use App\Http\Middleware\GeofenceValidation;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $company = Company::factory()->create();
    $branch = Branch::factory()->create([
        'company_id' => $company->id,
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
    ]);
    $department = Department::factory()->create([
        'branch_id' => $branch->id,
        'code' => 'DEPT_'.uniqid(),
    ]);
    $position = Position::factory()->create(['department_id' => $department->id]);

    $this->employee = Employee::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
    $this->user = User::factory()->create();
    $this->user->employee()->save($this->employee);
});

function handleGeofence(Request $request): Response
{
    $middleware = new GeofenceValidation;

    return $middleware->handle($request, fn ($r) => response('ok'));
}

test('passes through when no user is authenticated', function () {
    $request = Request::create('/api/v1/attendance/clock-in', 'POST', [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);

    $result = handleGeofence($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('passes through when user has no employee', function () {
    $userWithoutEmp = User::factory()->create();
    $request = Request::create('/api/v1/attendance/clock-in', 'POST', [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);
    $request->setUserResolver(fn () => $userWithoutEmp);

    $result = handleGeofence($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('passes through when employee has no branch (zero coords)', function () {
    $zeroBranch = Branch::factory()->create([
        'latitude' => 0,
        'longitude' => 0,
        'radius' => 0,
    ]);
    $emp = Employee::factory()->create([
        'company_id' => $this->employee->company_id,
        'branch_id' => $zeroBranch->id,
        'department_id' => $this->employee->department_id,
        'position_id' => $this->employee->position_id,
    ]);
    $user = User::factory()->create();
    $user->employee()->save($emp);

    $request = Request::create('/api/v1/attendance/clock-in', 'POST', [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);
    $request->setUserResolver(fn () => $user);

    $result = handleGeofence($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('passes through on GET request', function () {
    $request = Request::create('/api/v1/attendance/today', 'GET');
    $request->setUserResolver(fn () => $this->user);

    $result = handleGeofence($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('passes through when POST has no lat/lng', function () {
    $request = Request::create('/api/v1/attendance/clock-in', 'POST', []);
    $request->setUserResolver(fn () => $this->user);

    $result = handleGeofence($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('passes through when user is within branch radius', function () {
    $branch = Branch::where('latitude', -6.2088)->first();
    $request = Request::create('/api/v1/attendance/clock-in', 'POST', [
        'latitude' => $branch->latitude,
        'longitude' => $branch->longitude,
    ]);
    $request->setUserResolver(fn () => $this->user);

    $result = handleGeofence($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('throws exception when user is outside branch radius', function () {
    $request = Request::create('/api/v1/attendance/clock-in', 'POST', [
        'latitude' => -6.5,
        'longitude' => 107.0,
    ]);
    $request->setUserResolver(fn () => $this->user);

    expect(fn () => handleGeofence($request))
        ->toThrow(GeofenceViolationException::class, 'di luar radius');
});
