<?php

use App\Livewire\Admin\EmployeeComponent;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

function createEmployeeWithUser(array $attributes = []): Employee
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(array_merge([
        'user_id' => $user->id,
    ], $attributes));

    return $employee;
}

test('employee page delete button renders valid livewire click binding', function () {
    $superadmin = User::factory()->create();
    $employee = createEmployeeWithUser(['full_name' => 'Budi "Operator"']);

    $this->actingAs($superadmin);

    $response = $this->get(route('admin.employees'));

    $response->assertOk();
    $response->assertSee($employee->full_name);
});

test('employee component can open delete confirmation and delete user', function () {
    $superadmin = User::factory()->create();
    $employee = createEmployeeWithUser();

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletion', $employee->user->id)
        ->assertSet('confirmingDeletion', true)
        ->assertSet('deleteName', $employee->user->name)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('users', ['id' => $employee->user->id]);
});

test('employee component can approve a pending account deletion request', function () {
    $superadmin = User::factory()->create();
    $employee = createEmployeeWithUser([
        'employment_status' => 'deletion_requested',
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'I already resigned from the company.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletionApproval', $employee->user->id)
        ->assertSet('confirmingDeletionReview', true)
        ->assertSet('deletionReviewAction', 'approve')
        ->set('deletionReviewNotes', 'Approved by HR admin.')
        ->call('approveDeletionRequest')
        ->assertHasNoErrors();

    $employee->user->refresh();

    expect($employee->user->employment_status)->toBe('deleted')
        ->and($employee->user->account_deletion_reviewed_by)->toBe($superadmin->id)
        ->and($employee->user->account_deletion_review_notes)->toBe('Approved by HR admin.');
});

test('employee component can reject a pending account deletion request', function () {
    $superadmin = User::factory()->create();
    $employee = createEmployeeWithUser([
        'employment_status' => 'deletion_requested',
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'Please delete my account.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletionRejection', $employee->user->id)
        ->assertSet('confirmingDeletionReview', true)
        ->assertSet('deletionReviewAction', 'reject')
        ->set('deletionReviewNotes', 'Employee still needs access for handover.')
        ->call('rejectDeletionRequest')
        ->assertHasNoErrors();

    $employee->user->refresh();

    expect($employee->user->employment_status)->toBe('active')
        ->and($employee->user->account_deletion_requested_at)->toBeNull()
        ->and($employee->user->account_deletion_reviewed_by)->toBe($superadmin->id)
        ->and($employee->user->account_deletion_review_notes)->toBe('Employee still needs access for handover.');
});

test('employee component can assign a direct manager', function () {
    $superadmin = User::factory()->create();
    $manager = createEmployeeWithUser();
    $employee = createEmployeeWithUser([
        'parent_id' => null,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('edit', $employee->user->id)
        ->set('form.manager_id', $manager->id)
        ->call('update')
        ->assertHasNoErrors();

    expect($employee->refresh()->parent_id)->toBe($manager->id);
});

test('employee component prevents circular direct manager assignments', function () {
    $superadmin = User::factory()->create();
    $manager = createEmployeeWithUser();
    $employee = createEmployeeWithUser([
        'parent_id' => $manager->id,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('edit', $manager->user->id)
        ->set('form.parent_id', $employee->id)
        ->call('update')
        ->assertHasErrors(['form.parent_id']);
});
