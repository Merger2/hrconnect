<?php

use App\Livewire\Admin\OperationalWorkspace;
use App\Models\Client;
use App\Models\CompanyBranch;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\SalesOpportunity;
use App\Models\User;
use App\Support\MultiCompanyService;
use App\Support\OperationalWorkspaceService;
use Livewire\Livewire;

test('superadmin can create operational workspace records', function () {
    $superadmin = User::factory()->admin(true)->create();
    $company = app(MultiCompanyService::class)->createCompany('PT Ops Platform');
    $manager = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($superadmin);

    Livewire::test(OperationalWorkspace::class)
        ->set('branchCompanyId', (string) $company->id)
        ->set('branchName', 'Bandung Branch')
        ->set('branchType', 'branch')
        ->call('createBranch')
        ->assertHasNoErrors()
        ->set('clientCompanyId', (string) $company->id)
        ->set('clientName', 'PT Client Utama')
        ->set('clientContactName', 'Budi')
        ->call('createClient')
        ->assertHasNoErrors();

    $branch = CompanyBranch::query()->where('name', 'Bandung Branch')->firstOrFail();
    $client = Client::query()->where('name', 'PT Client Utama')->firstOrFail();

    Livewire::test(OperationalWorkspace::class)
        ->set('projectCompanyId', (string) $company->id)
        ->set('projectClientId', (string) $client->id)
        ->set('projectBranchId', (string) $branch->id)
        ->set('projectManagerId', $manager->id)
        ->set('projectName', 'Implementation Project')
        ->call('createProject')
        ->assertHasNoErrors();

    $project = Project::query()->where('name', 'Implementation Project')->firstOrFail();

    Livewire::test(OperationalWorkspace::class)
        ->set('taskProjectId', (string) $project->id)
        ->set('taskAssignedTo', $manager->id)
        ->set('taskTitle', 'Visit client location')
        ->set('taskChecklist', "Take location photo\nCollect signed form")
        ->call('createTask')
        ->assertHasNoErrors();

    $task = ProjectTask::query()->where('title', 'Visit client location')->firstOrFail();

    expect($project->company_id)->toBe($company->id)
        ->and($project->client_id)->toBe($client->id)
        ->and($task->company_id)->toBe($company->id)
        ->and($task->checklistItems()->count())->toBe(2);
});

test('tenant scoped admin cannot create project for another company', function () {
    $admin = User::factory()->admin()->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Tenant Ops A', $admin);
    $companyB = app(MultiCompanyService::class)->createCompany('PT Tenant Ops B');

    $role = Role::query()->create([
        'name' => 'Operations Manager',
        'slug' => 'operations_manager',
        // Key kanonik rbac — gate viewOperationsWorkspace meng-map ke
        // admin.operations_workspace.view (bukan admin.operations.view).
        'permission_keys' => ['admin.operations_workspace.view', 'admin.operations_workspace.manage'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh());

    Livewire::test(OperationalWorkspace::class)
        ->set('projectCompanyId', (string) $companyB->id)
        ->set('projectName', 'Cross tenant project')
        ->call('createProject')
        ->assertForbidden();

    expect(Project::query()->where('company_id', $companyB->id)->exists())->toBeFalse()
        ->and($admin->fresh()->company_id)->toBe($companyA->id);
});

test('operations form scopes related options to selected company and project', function () {
    $superadmin = User::factory()->admin(true)->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Ops Scope A');
    $companyB = app(MultiCompanyService::class)->createCompany('PT Ops Scope B');
    $clientA = Client::query()->create([
        'company_id' => $companyA->id,
        'name' => 'Client Scope A',
        'status' => Client::STATUS_ACTIVE,
    ]);
    $clientB = Client::query()->create([
        'company_id' => $companyB->id,
        'name' => 'Client Scope B',
        'status' => Client::STATUS_ACTIVE,
    ]);
    $branchA = CompanyBranch::query()->create([
        'company_id' => $companyA->id,
        'name' => 'Branch Scope A',
        'type' => 'branch',
        'status' => CompanyBranch::STATUS_ACTIVE,
    ]);
    $branchB = CompanyBranch::query()->create([
        'company_id' => $companyB->id,
        'name' => 'Branch Scope B',
        'type' => 'branch',
        'status' => CompanyBranch::STATUS_ACTIVE,
    ]);
    $assigneeA = User::factory()->create(['company_id' => $companyA->id, 'name' => 'Assignee Scope A']);
    $assigneeB = User::factory()->create(['company_id' => $companyB->id, 'name' => 'Assignee Scope B']);
    $projectA = Project::query()->create([
        'company_id' => $companyA->id,
        'client_id' => $clientA->id,
        'branch_id' => $branchA->id,
        'name' => 'Project Scope A',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(OperationalWorkspace::class)
        ->set('projectCompanyId', (string) $companyA->id)
        ->assertSee('Client Scope A')
        ->assertSee('Branch Scope A')
        ->assertSee('Assignee Scope A')
        ->assertDontSee('Client Scope B')
        ->assertDontSee('Branch Scope B')
        ->assertDontSee('Assignee Scope B')
        ->set('activeTab', 'tasks')
        ->set('taskProjectId', (string) $projectA->id)
        ->assertSee('Assignee Scope A')
        ->assertDontSee('Assignee Scope B');

    expect($clientB->exists)->toBeTrue()
        ->and($branchB->exists)->toBeTrue()
        ->and($assigneeB->exists)->toBeTrue();
});

test('operations workspace keeps selected tab from query string on reload', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::withQueryParams(['activeTab' => 'tasks'])
        ->test(OperationalWorkspace::class)
        ->assertSet('activeTab', 'tasks');

    Livewire::withQueryParams(['activeTab' => 'bad-tab'])
        ->test(OperationalWorkspace::class)
        ->assertSet('activeTab', 'projects');
});

test('operations task form shows validation error for assignee from another company', function () {
    $superadmin = User::factory()->admin(true)->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Ops Task A');
    $companyB = app(MultiCompanyService::class)->createCompany('PT Ops Task B');
    $project = Project::query()->create([
        'company_id' => $companyA->id,
        'name' => 'Company Scoped Project',
        'status' => Project::STATUS_ACTIVE,
    ]);
    $crossCompanyAssignee = User::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($superadmin);

    Livewire::test(OperationalWorkspace::class)
        ->set('activeTab', 'tasks')
        ->set('taskProjectId', (string) $project->id)
        ->set('taskAssignedTo', $crossCompanyAssignee->id)
        ->set('taskTitle', 'Wrong company assignment')
        ->call('createTask')
        ->assertHasErrors(['taskAssignedTo']);

    expect(ProjectTask::query()->where('title', 'Wrong company assignment')->exists())->toBeFalse();
});

test('operations project financial summary includes linked commercial records', function () {
    $superadmin = User::factory()->admin(true)->create();
    $company = app(MultiCompanyService::class)->createCompany('PT Project Finance');
    $client = Client::query()->create([
        'company_id' => $company->id,
        'name' => 'PT Finance Client',
        'status' => Client::STATUS_ACTIVE,
    ]);
    $project = Project::query()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'name' => 'Finance Visibility Project',
        'status' => Project::STATUS_ACTIVE,
    ]);

    Invoice::query()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'project_id' => $project->id,
        'number' => 'INV-PROJECT-001',
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now()->toDateString(),
        'paid_at' => now(),
        'subtotal' => 2000000,
        'tax_total' => 220000,
        'grand_total' => 2220000,
    ]);
    Invoice::query()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'project_id' => $project->id,
        'number' => 'INV-PROJECT-002',
        'status' => Invoice::STATUS_SENT,
        'issued_at' => now()->toDateString(),
        'subtotal' => 1000000,
        'tax_total' => 110000,
        'grand_total' => 1110000,
    ]);
    SalesOpportunity::query()->create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'name' => 'Expansion pipeline',
        'stage' => SalesOpportunity::STAGE_PROPOSAL,
        'expected_value' => 5000000,
        'probability' => 70,
    ]);

    $summary = app(OperationalWorkspaceService::class)->projectFinancialSummaries([$project->id])[$project->id];

    expect($summary['invoiced'])->toBe(3330000.0)
        ->and($summary['paid'])->toBe(2220000.0)
        ->and($summary['outstanding'])->toBe(1110000.0)
        ->and($summary['pipeline'])->toBe(3500000.0);
});

test('operations route requires explicit permission', function () {
    $admin = User::factory()->admin()->create();
    $admin->roles()->detach();

    $this->actingAs($admin)
        ->get(route('admin.operations'))
        ->assertForbidden();

    $role = Role::query()->create([
        'name' => 'Operations Viewer',
        'slug' => 'operations_viewer',
        'permission_keys' => ['admin.operations_workspace.view'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.operations'))
        ->assertOk();
});
