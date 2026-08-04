<?php

use App\Livewire\Admin\EmployeeComponent;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

/**
 * Account deletion request flow (post-refactor):
 *
 * RequestAccountDeletionForm (user-side Livewire form) sudah dihapus. Field
 * deletion pindah ke tabel `employees` dan review request dilakukan admin di
 * EmployeeComponent (confirmDeletionApproval → approveDeletionRequest /
 * rejectDeletionRequest).
 */
function deletionReviewEmployee(User $user, string $status = Employee::EMPLOYMENT_STATUS_DELETION_REQUESTED): Employee
{
    return Employee::factory()->create([
        'user_id' => $user->id,
        'employment_status' => $status,
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'I have left the company and need this account removed.',
    ]);
}

test('admin can approve a pending account deletion request', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    deletionReviewEmployee($employee);

    Livewire::actingAs($admin)
        ->test(EmployeeComponent::class)
        ->call('confirmDeletionApproval', $employee->id)
        ->set('deletionReviewNotes', 'Approved by HR')
        ->call('approveDeletionRequest');

    $employee->refresh();

    expect($employee->employee->employment_status?->value)->toBe(User::EMPLOYMENT_STATUS_DELETED)
        ->and($employee->employee->account_deletion_reviewed_by)->toBe($admin->id)
        ->and($employee->employee->account_deletion_review_notes)->toBe('Approved by HR');
});

test('admin can reject a pending account deletion request back to active', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    deletionReviewEmployee($employee);

    Livewire::actingAs($admin)
        ->test(EmployeeComponent::class)
        ->call('confirmDeletionRejection', $employee->id)
        ->set('deletionReviewNotes', 'Employee changed their mind.')
        ->call('rejectDeletionRequest');

    $employee->refresh();

    expect($employee->employee->employment_status?->value)->toBe(User::EMPLOYMENT_STATUS_ACTIVE)
        ->and($employee->employee->account_deletion_reviewed_by)->toBe($admin->id)
        ->and($employee->employee->account_deletion_review_notes)->toBe('Employee changed their mind.');
});

test('deletion review is rejected with 404 for employees without a pending request', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $employee->id,
        'employment_status' => Employee::EMPLOYMENT_STATUS_ACTIVE,
    ]);

    Livewire::actingAs($admin)
        ->test(EmployeeComponent::class)
        ->call('confirmDeletionApproval', $employee->id)
        ->assertStatus(404);
});

test('non-admin reviewer cannot open deletion review', function () {
    $reviewer = User::factory()->create();
    $employee = User::factory()->create();
    deletionReviewEmployee($employee);

    $this->actingAs($reviewer)
        ->get(route('admin.employees'))
        ->assertForbidden();
});
