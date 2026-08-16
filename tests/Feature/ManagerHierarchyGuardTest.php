<?php

use App\Models\User;
use App\Support\ManagerHierarchyGuard;

function managerGuard(): ManagerHierarchyGuard
{
    return app(ManagerHierarchyGuard::class);
}

test('assigning an employee as their own manager creates a cycle', function () {
    $employee = User::factory()->create();

    expect(managerGuard()->wouldCreateCycle($employee, $employee))->toBeTrue();
});

test('an unrelated manager does not create a cycle', function () {
    $employee = User::factory()->create();
    $manager = User::factory()->create();

    expect(managerGuard()->wouldCreateCycle($employee, $manager))->toBeFalse();
});

test('a direct report assigned as manager creates a cycle', function () {
    $employee = User::factory()->create();
    $report = User::factory()->create(['manager_id' => $employee->id]);

    // Making the employee report to their own direct report closes the loop.
    expect(managerGuard()->wouldCreateCycle($employee, $report))->toBeTrue();
});

test('an indirect descendant assigned as manager creates a cycle', function () {
    $employee = User::factory()->create();
    $mid = User::factory()->create(['manager_id' => $employee->id]);
    $bottom = User::factory()->create(['manager_id' => $mid->id]);

    expect(managerGuard()->wouldCreateCycle($employee, $bottom))->toBeTrue();
});

test('a deep chain with no loop is allowed', function () {
    $top = User::factory()->create();
    $mid = User::factory()->create(['manager_id' => $top->id]);
    $employee = User::factory()->create();

    // employee -> mid -> top, no path back to employee.
    expect(managerGuard()->wouldCreateCycle($employee, $mid))->toBeFalse();
});
