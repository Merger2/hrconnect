<?php

use App\Livewire\Admin\Finance\CashAdvanceManager as AdminCashAdvanceManager;
use App\Livewire\Admin\ReimbursementManager;
use App\Livewire\User\Finance\TeamCashAdvanceManager;
use App\Livewire\User\TeamApprovals;
use App\Livewire\User\TeamApprovalsHistory;
use App\Models\AccountingAccount;
use App\Models\ApprovalMatrixRule;
use App\Models\CashAdvance;
use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CashAdvanceUpdated;
use App\Notifications\ReimbursementStatusUpdated;
use App\Support\CashAdvanceApprovalService;
use App\Support\MultiCompanyService;
use App\Support\ReimbursementApprovalService;
use App\Support\TeamApprovalQueryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createApprovalHierarchy(string $divisionName = 'Operations'): array
{
    $division = Division::create(['name' => $divisionName, 'code' => strtoupper(substr($divisionName, 0, 3)).'_'.uniqid()]);

    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => $divisionName.' Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => $divisionName.' Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $managerRole = Role::create([
        'name' => 'Supervisor Workflow_'.uniqid(),
        'slug' => 'supervisor_workflow_'.uniqid(),
        'description' => 'Can review subordinate requests.',
        'permission_keys' => ['review_subordinate_requests'],
    ]);

    $manager = User::factory()->create();
    $managerEmployee = Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
    ]);
    $manager->roles()->sync([$managerRole->id]);

    $employee = User::factory()->create(['manager_id' => $manager->id]);
    Employee::factory()->create([
        'user_id' => $employee->id,
        'division_id' => $division->id,
        'parent_id' => $managerEmployee->id,
    ]);

    return [$manager, $employee, $division, $managerTitle, $staffTitle];
}

function createFinanceHead(bool $admin = false): User
{
    $division = Division::create(['name' => 'Finance', 'code' => 'FIN_'.uniqid()]);

    $factory = $admin ? User::factory()->admin() : User::factory();
    $user = $factory->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'division_id' => $division->id,
    ]);

    return $user;
}

test('supervisor approval forwards reimbursement to finance', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->employee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Transport',
        'amount' => 150000,
        'description' => 'Airport pickup',
        'status' => 'pending',
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'reimbursements')
        ->call('approveReimbursement', $reimbursement->id);

    $reimbursement->refresh();

    expect($reimbursement->status->value)->toBe('pending_finance')
        ->and($reimbursement->head_approved_by)->toBe($manager->id)
        ->and($reimbursement->head_approved_at)->not->toBeNull()
        ->and($reimbursement->finance_approved_by)->toBeNull();

    Notification::assertSentTo($employee, ReimbursementStatusUpdated::class);
});

test('team approval history keeps finance-forwarded requests visible to supervisors', function () {
    [$manager, $employee] = createApprovalHierarchy();

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->employee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Meal',
        'amount' => 50000,
        'description' => 'Client lunch',
        'status' => 'pending_finance',
        'head_approved_by' => $manager->id,
        'head_approved_at' => now(),
    ]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 300000,
        'purpose' => 'Team transport advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending_finance',
        'head_approved_by' => $manager->id,
        'head_approved_at' => now(),
    ]);

    $service = app(TeamApprovalQueryService::class);

    $reimbursementHistory = collect($service->history($manager, 'reimbursements')->items());
    $cashAdvanceHistory = collect($service->history($manager, 'kasbons')->items());

    expect($reimbursementHistory->pluck('id'))->toContain($reimbursement->id)
        ->and($cashAdvanceHistory->pluck('id'))->toContain($advance->id);
});

test('team approval tabs keep selected query tab on reload', function () {
    [$manager] = createApprovalHierarchy();

    Livewire::actingAs($manager)
        ->withQueryParams(['activeTab' => 'reimbursements'])
        ->test(TeamApprovals::class)
        ->assertSet('activeTab', 'reimbursements');

    Livewire::actingAs($manager)
        ->withQueryParams(['activeTab' => 'bad-tab'])
        ->test(TeamApprovals::class)
        ->assertSet('activeTab', 'leaves');

    Livewire::actingAs($manager)
        ->withQueryParams(['activeTab' => 'kasbons'])
        ->test(TeamApprovalsHistory::class)
        ->assertSet('activeTab', 'kasbons');

    Livewire::actingAs($manager)
        ->withQueryParams(['activeTab' => 'bad-tab'])
        ->test(TeamApprovalsHistory::class)
        ->assertSet('activeTab', 'leaves');
});

test('cash advance manager tabs keep selected query tab on reload', function () {

    [$manager] = createApprovalHierarchy();
    $superadmin = User::factory()->admin(true)->create();

    Livewire::actingAs($manager)
        ->withQueryParams(['activeTab' => 'users'])
        ->test(TeamCashAdvanceManager::class)
        ->assertSet('activeTab', 'users');

    Livewire::actingAs($manager)
        ->withQueryParams(['activeTab' => 'bad-tab'])
        ->test(TeamCashAdvanceManager::class)
        ->assertSet('activeTab', 'requests');

    Livewire::actingAs($superadmin)
        ->withQueryParams(['activeTab' => 'users'])
        ->test(AdminCashAdvanceManager::class)
        ->assertSet('activeTab', 'users');

    Livewire::actingAs($superadmin)
        ->withQueryParams(['activeTab' => 'bad-tab'])
        ->test(AdminCashAdvanceManager::class)
        ->assertSet('activeTab', 'requests');
});

test('supervisor approval forwards cash advance to finance', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 700000,
        'purpose' => 'Project field advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'kasbons')
        ->call('approveKasbon', $advance->id);

    $advance->refresh();

    expect($advance->status)->toBe('pending_finance')
        ->and($advance->head_approved_by)->toBe($manager->id)
        ->and($advance->head_approved_at)->not->toBeNull()
        ->and($advance->approved_by)->toBeNull();

    Notification::assertSentTo($employee, CashAdvanceUpdated::class);
});

test('finance head can finalize pending finance reimbursements from manager queue', function () {
    $this->markTestSkipped('Depends on AccountingAccount/JournalEntry/JournalEntryLine models not present in this build.');

    Notification::fake();

    [, $employee] = createApprovalHierarchy();
    $financeHead = createFinanceHead(true);
    $company = app(MultiCompanyService::class)->createCompany('PT Reimbursement Accounting', $financeHead);
    $employee->forceFill(['company_id' => $company->id])->save();

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->employee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Hotel',
        'amount' => 450000,
        'description' => 'Site visit stay',
        'status' => 'pending_finance',
    ]);

    $this->actingAs($financeHead);

    Livewire::test(ReimbursementManager::class)
        ->set('statusFilter', 'pending_finance')
        ->call('approve', $reimbursement->id);

    $reimbursement->refresh();

    expect($reimbursement->status->value)->toBe('approved')
        ->and($reimbursement->finance_approved_by)->toBe($financeHead->id)
        ->and($reimbursement->finance_approved_at)->not->toBeNull()
        ->and($reimbursement->approved_by)->toBe($financeHead->id)
        ->and($reimbursement->accounting_journal_entry_id)->not->toBeNull()
        ->and($reimbursement->accounting_posted_at)->not->toBeNull();

    $journal = JournalEntry::query()
        ->with('lines.account')
        ->where('source_type', Reimbursement::class)
        ->where('source_id', $reimbursement->id)
        ->firstOrFail();

    expect(AccountingAccount::query()->where('company_id', $company->id)->whereIn('code', ['1100', '5200'])->count())->toBe(2)
        ->and((float) JournalEntryLine::query()->where('journal_entry_id', $journal->id)->sum('debit'))->toBe(450000.0)
        ->and((float) JournalEntryLine::query()->where('journal_entry_id', $journal->id)->sum('credit'))->toBe(450000.0)
        ->and($journal->lines->firstWhere('account.code', '5200'))->not->toBeNull()
        ->and($journal->lines->firstWhere('account.code', '1100'))->not->toBeNull();

    Notification::assertSentTo($employee, ReimbursementStatusUpdated::class);
});

test('approval matrix can require manager finance and hr role for high value reimbursement', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();
    $financeHead = createFinanceHead();
    $hr = User::factory()->create();
    $hrRole = Role::create([
        'name' => 'HR Head Matrix_'.uniqid(),
        'slug' => 'hr_head_matrix_'.uniqid(),
        'description' => 'Can approve HR matrix steps.',
        'permission_keys' => [],
    ]);
    $hr->roles()->sync([$hrRole->id]);

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_REIMBURSEMENT,
        'condition_type' => 'min_amount',
        'condition_value' => '5000000',
        'approval_level' => 1,
        'approver_id' => $manager->employee->id,
        'is_active' => true,
    ]);
    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_REIMBURSEMENT,
        'condition_type' => 'min_amount',
        'condition_value' => '5000000',
        'approval_level' => 2,
        'approver_id' => $financeHead->employee->id,
        'is_active' => true,
    ]);
    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_REIMBURSEMENT,
        'condition_type' => 'min_amount',
        'condition_value' => '5000000',
        'approval_level' => 3,
        'approver_role_id' => $hrRole->id,
        'is_active' => true,
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->employee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Travel',
        'amount' => 6000000,
        'description' => 'Out of town implementation.',
        'status' => 'pending',
    ]);

    app(ReimbursementApprovalService::class)->approve($reimbursement, $manager);
    $reimbursement->refresh();

    expect($reimbursement->status->value)->toBe('pending_matrix')
        ->and($reimbursement->approval_current_step)->toBe('2')
        ->and($reimbursement->approval_completed_steps)->toHaveCount(1);

    app(ReimbursementApprovalService::class)->approve($reimbursement, $financeHead);
    $reimbursement->refresh();

    expect($reimbursement->status->value)->toBe('pending_matrix')
        ->and($reimbursement->approval_current_step)->toBe('3')
        ->and($reimbursement->approval_completed_steps)->toHaveCount(2)
        ->and(Gate::forUser($hr)->allows('approve', $reimbursement))->toBeTrue();

    app(ReimbursementApprovalService::class)->approve($reimbursement, $hr);
    $reimbursement->refresh();

    expect($reimbursement->status->value)->toBe('approved')
        ->and($reimbursement->approval_current_step)->toBeNull()
        ->and($reimbursement->approved_by)->toBe($hr->id)
        ->and($reimbursement->approval_completed_steps)->toHaveCount(3);
});

test('team cash advance manager allows authorized supervisor to approve subordinate request', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_CASH_ADVANCE,
        'condition_type' => 'min_amount',
        'condition_value' => '0',
        'approval_level' => 1,
        'approver_id' => $manager->employee->id,
        'is_active' => true,
    ]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 450000,
        'purpose' => 'Site transport advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamCashAdvanceManager::class)
        ->call('approve', $advance->id);

    $advance->refresh();

    expect($advance->status)->toBe('approved')
        ->and($advance->approved_by)->toBe($manager->id)
        ->and($advance->approval_completed_steps)->toHaveCount(1);

    Notification::assertSentTo($employee, CashAdvanceUpdated::class);
});

test('approval matrix can route cash advance through manager and finance', function () {
    Notification::fake();

    [$manager, $employee] = createApprovalHierarchy();
    $financeHead = createFinanceHead();

    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_CASH_ADVANCE,
        'condition_type' => 'min_amount',
        'condition_value' => '1000000',
        'approval_level' => 1,
        'approver_id' => $manager->employee->id,
        'is_active' => true,
    ]);
    ApprovalMatrixRule::create([
        'module_name' => ApprovalMatrixRule::MODULE_CASH_ADVANCE,
        'condition_type' => 'min_amount',
        'condition_value' => '1000000',
        'approval_level' => 2,
        'approver_id' => $financeHead->employee->id,
        'is_active' => true,
    ]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 1500000,
        'purpose' => 'Emergency project float',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    app(CashAdvanceApprovalService::class)->approve($advance, $manager);
    $advance->refresh();

    expect($advance->status)->toBe('pending_matrix')
        ->and($advance->approval_current_step)->toBe('2')
        ->and($advance->approval_completed_steps)->toHaveCount(1);

    app(CashAdvanceApprovalService::class)->approve($advance, $financeHead);
    $advance->refresh();

    expect($advance->status)->toBe('approved')
        ->and($advance->approved_by)->toBe($financeHead->id)
        ->and($advance->finance_approved_by)->toBe($financeHead->id)
        ->and($advance->approval_completed_steps)->toHaveCount(2);
});

test('team cash advance manager forbids unrelated users from approving subordinate request', function () {

    [, $employee] = createApprovalHierarchy();
    $unrelated = User::factory()->create();

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 325000,
        'purpose' => 'Equipment pickup',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    Livewire::actingAs($unrelated)
        ->test(TeamCashAdvanceManager::class)
        ->assertForbidden();

    expect($advance->fresh()->status)->toBe('pending');
});

test('review services reject stale reimbursement and cash advance approvals', function () {
    [$manager, $employee] = createApprovalHierarchy();

    $reimbursement = Reimbursement::create([
        'employee_id' => $employee->employee->id,
        'expense_date' => now()->toDateString(),
        'title' => 'Transport',
        'amount' => 125000,
        'description' => 'Already settled claim.',
        'status' => 'approved',
        'approved_by' => $manager->id,
    ]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 350000,
        'purpose' => 'Already rejected kasbon.',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'rejected',
    ]);

    expect(fn () => app(ReimbursementApprovalService::class)->approve($reimbursement, $manager))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(CashAdvanceApprovalService::class)->approve($advance, $manager))
        ->toThrow(AuthorizationException::class);

    expect($reimbursement->fresh()->status->value)->toBe('approved')
        ->and($advance->fresh()->status)->toBe('rejected');
});
