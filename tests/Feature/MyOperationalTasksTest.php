<?php

use App\Livewire\User\MyOperationalTasks;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskChecklistItem;
use App\Models\ProjectVisitEvidence;
use App\Models\User;
use App\Policies\ProjectVisitEvidencePolicy;
use App\Support\MultiCompanyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function createAssignedOperationalTask(User $assignee, string $companyName = 'PT Field Ops'): ProjectTask
{
    $company = app(MultiCompanyService::class)->createCompany($companyName, $assignee);

    $client = Client::query()->create([
        'company_id' => $company->id,
        'name' => 'Client Visit',
        'status' => 'active',
    ]);

    $project = Project::query()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'name' => 'Retail Rollout',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $task = ProjectTask::query()->create([
        'project_id' => $project->id,
        'company_id' => $company->id,
        'assigned_to' => $assignee->id,
        'title' => 'Visit outlet A',
        'status' => ProjectTask::STATUS_TODO,
        'priority' => ProjectTask::PRIORITY_NORMAL,
    ]);

    ProjectTaskChecklistItem::query()->create([
        'project_task_id' => $task->id,
        'title' => 'Capture storefront photo',
        'sort_order' => 1,
    ]);

    return $task->fresh(['checklistItems']);
}

test('assigned user can manage operational task status checklist and visit evidence', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $task = createAssignedOperationalTask($user);
    $checklistItem = $task->checklistItems->firstOrFail();

    $this->actingAs($user)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertSee('Visit outlet A');

    Livewire::test(MyOperationalTasks::class)
        ->set('search', 'outlet')
        ->assertSee('Visit outlet A')
        ->call('updateTaskStatus', $task->id, ProjectTask::STATUS_IN_PROGRESS)
        ->assertHasNoErrors()
        ->call('toggleChecklistItem', $checklistItem->id)
        ->assertHasNoErrors()
        ->set("visitNotes.{$task->id}", 'Client location verified.')
        ->set("visitLatitude.{$task->id}", '-6.2000000')
        ->set("visitLongitude.{$task->id}", '106.8166667')
        ->set("visitAccuracy.{$task->id}", '18')
        ->set("visitPhotos.{$task->id}", UploadedFile::fake()->image('visit.webp', 900, 600)->size(512))
        ->call('submitVisitEvidence', $task->id)
        ->assertHasNoErrors();

    $task->refresh();
    $evidence = ProjectVisitEvidence::query()->where('project_task_id', $task->id)->firstOrFail();

    expect($task->status)->toBe(ProjectTask::STATUS_IN_PROGRESS)
        ->and($checklistItem->fresh()->is_done)->toBeTrue()
        ->and($evidence->user_id)->toBe($user->id)
        ->and($evidence->notes)->toBe('Client location verified.')
        ->and($evidence->photo_disk)->toBe('local')
        ->and($evidence->photo_path)->not->toBeNull();

    Storage::disk('local')->assertExists($evidence->photo_path);

    $this->actingAs($user)
        ->get(route('operations.visit-evidence.photo', $evidence))
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('operations.visit-evidence.photo', $evidence))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin(true)->create())
        ->get(route('operations.visit-evidence.photo', $evidence))
        ->assertOk();

    expect(Gate::getPolicyFor(ProjectVisitEvidence::class))
        ->toBeInstanceOf(ProjectVisitEvidencePolicy::class);
});

test('user cannot update another employee operational task', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = createAssignedOperationalTask($owner, 'PT Field Ops Guard');

    $this->actingAs($otherUser);

    expect(fn () => Livewire::test(MyOperationalTasks::class)
        ->call('updateTaskStatus', $task->id, ProjectTask::STATUS_DONE)
    )->toThrow(ModelNotFoundException::class);

    expect($task->fresh()->status)->toBe(ProjectTask::STATUS_TODO);
});
