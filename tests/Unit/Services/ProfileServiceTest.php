<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
    $this->department = Department::factory()->create([
        'branch_id' => $this->branch->id,
        'code' => 'DEPT_'.uniqid(),
    ]);
    $this->position = Position::factory()->create(['department_id' => $this->department->id]);
    $this->service = new ProfileService;
});

it('returns employee profile with relations', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    $profile = $this->service->getProfile($user);

    expect($profile)->not->toBeNull()
        ->and($profile->id)->toBe($employee->id)
        ->and($profile->relationLoaded('branch'))->toBeTrue()
        ->and($profile->relationLoaded('department'))->toBeTrue()
        ->and($profile->relationLoaded('position'))->toBeTrue()
        ->and($profile->relationLoaded('shift'))->toBeTrue()
        ->and($profile->relationLoaded('manager'))->toBeTrue();
});

it('returns null when user has no employee', function () {
    $user = User::factory()->create();

    $profile = $this->service->getProfile($user);

    expect($profile)->toBeNull();
});

it('updates employee profile and returns fresh with relations', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'phone' => '08123456789',
        'address_detail' => 'Old address',
    ]);

    $updated = $this->service->updateProfile($employee, [
        'phone' => '08987654321',
        'address_detail' => 'New address',
    ]);

    expect($updated->phone)->toBe('08987654321')
        ->and($updated->address_detail)->toBe('New address')
        ->and($updated->relationLoaded('branch'))->toBeTrue()
        ->and($updated->relationLoaded('department'))->toBeTrue()
        ->and($updated->relationLoaded('position'))->toBeTrue()
        ->and($updated->relationLoaded('shift'))->toBeTrue()
        ->and($updated->relationLoaded('manager'))->toBeTrue();
});

it('changes password when current password is correct', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old_password'),
    ]);

    $this->service->changePassword($user, 'old_password', 'new_password');

    $user->refresh();
    expect(Hash::check('new_password', $user->password))->toBeTrue()
        ->and($user->password_changed_at)->not->toBeNull();
});

it('throws validation exception when current password is wrong', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct_password'),
    ]);

    $this->service->changePassword($user, 'wrong_password', 'new_password');
})->throws(ValidationException::class, 'Password saat ini salah.');
