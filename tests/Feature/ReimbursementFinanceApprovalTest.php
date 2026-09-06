<?php

use App\Enums\Permission;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\User;
use App\Support\ApprovalActorService;
use App\Support\ReimbursementApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('finance role with reimbursement L2 permission can finalize finance approval', function () {
    $employee = User::factory()->create();
    Employee::factory()->create(['user_id' => $employee->id]);

    $finance = User::factory()->create();
    $financeRole = Role::create([
        'name' => 'Finance E2E',
        'slug' => 'finance-e2e',
        'permission_keys' => [Permission::APPROVE_REIMBURSEMENTS_L2->value],
    ]);
    $finance->roles()->sync([$financeRole->id]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->employee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Transport final approval',
        'amount' => 250000,
        'description' => 'Already approved by direct manager.',
        'status' => 'pending_finance',
    ]);

    expect(app(ApprovalActorService::class)->canFinalizeReimbursementApproval($finance))->toBeTrue();

    app(ReimbursementApprovalService::class)->approve($reimbursement, $finance);

    $reimbursement->refresh();

    expect($reimbursement->status->value)->toBe('approved')
        ->and($reimbursement->finance_approved_by)->toBe($finance->id)
        ->and($reimbursement->approved_by)->toBe($finance->id);
});
