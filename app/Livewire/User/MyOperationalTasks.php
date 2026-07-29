<?php

namespace App\Livewire\User;

use App\Models\ProjectTask;
use App\Models\ProjectTaskChecklistItem;
use App\Support\OperationalWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MyOperationalTasks extends Component
{
    use InteractsWithBanner;
    use WithFileUploads;

    protected OperationalWorkspaceService $operations;

    public string $statusFilter = 'open';

    public string $search = '';

    /** @var array<int, string> */
    public array $visitNotes = [];

    /** @var array<int, string> */
    public array $visitLatitude = [];

    /** @var array<int, string> */
    public array $visitLongitude = [];

    /** @var array<int, string> */
    public array $visitAccuracy = [];

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $visitPhotos = [];

    public function boot(OperationalWorkspaceService $operations): void
    {
        $this->operations = $operations;
    }

    public function updateTaskStatus(int $taskId, string $status): void
    {
        validator(
            ['status' => $status],
            ['status' => ['required', Rule::in([ProjectTask::STATUS_TODO, ProjectTask::STATUS_IN_PROGRESS, ProjectTask::STATUS_DONE])]],
        )->validate();

        $task = $this->assignedTask($taskId);
        $this->operations->updateTask(auth()->user(), $task, ['status' => $status]);

        $this->banner(__('Task updated.'));
    }

    public function toggleChecklistItem(int $itemId): void
    {
        $item = ProjectTaskChecklistItem::query()
            ->whereHas('task', fn (Builder $query) => $query->where('assigned_to', auth()->id()))
            ->with('task')
            ->findOrFail($itemId);

        $this->operations->toggleChecklistItem(auth()->user(), $item);
    }

    public function submitVisitEvidence(int $taskId): void
    {
        $task = $this->assignedTask($taskId);

        $validated = $this->validate([
            "visitNotes.{$taskId}" => ['nullable', 'string', 'max:1200'],
            "visitLatitude.{$taskId}" => ['nullable', 'numeric', 'between:-90,90'],
            "visitLongitude.{$taskId}" => ['nullable', 'numeric', 'between:-180,180'],
            "visitAccuracy.{$taskId}" => ['nullable', 'integer', 'min:0', 'max:10000'],
            "visitPhotos.{$taskId}" => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photo = $validated['visitPhotos'][$taskId] ?? null;
        $photoPath = null;
        $photoOriginalName = null;

        if ($photo instanceof TemporaryUploadedFile) {
            $photoOriginalName = $photo->getClientOriginalName();
            $photoPath = $photo->store('operational-visits/'.now()->format('Y/m'), 'local');
        }

        $this->operations->recordVisitEvidence(auth()->user(), $task, [
            'notes' => $validated['visitNotes'][$taskId] ?? null,
            'latitude' => $validated['visitLatitude'][$taskId] ?? null,
            'longitude' => $validated['visitLongitude'][$taskId] ?? null,
            'accuracy_meters' => $validated['visitAccuracy'][$taskId] ?? null,
            'photo_disk' => $photoPath ? 'local' : null,
            'photo_path' => $photoPath,
            'photo_original_name' => $photoOriginalName,
            'metadata' => [
                'source' => 'user_operational_tasks',
                'submitted_via' => request()->header('X-Paspapan-Client', 'web'),
            ],
        ]);

        unset(
            $this->visitNotes[$taskId],
            $this->visitLatitude[$taskId],
            $this->visitLongitude[$taskId],
            $this->visitAccuracy[$taskId],
            $this->visitPhotos[$taskId],
        );

        $this->banner(__('Visit evidence submitted.'));
    }

    public function render()
    {
        $tasks = ProjectTask::query()
            ->with(['project.client', 'project.branch', 'checklistItems', 'visitEvidences' => fn ($query) => $query->latest()])
            ->where('assigned_to', auth()->id())
            ->when($this->statusFilter === 'open', fn (Builder $query) => $query->whereIn('status', [ProjectTask::STATUS_TODO, ProjectTask::STATUS_IN_PROGRESS]))
            ->when($this->statusFilter === ProjectTask::STATUS_DONE, fn (Builder $query) => $query->where('status', ProjectTask::STATUS_DONE))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $nested): void {
                    $nested
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhereHas('project', fn (Builder $project) => $project->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.user.my-operational-tasks', [
            'tasks' => $tasks,
            'pollingEnabled' => ! $this->hasDraftVisitEvidence(),
            'statuses' => [
                'open' => __('Open'),
                ProjectTask::STATUS_DONE => __('Done'),
                'all' => __('All'),
            ],
        ]);
    }

    private function assignedTask(int $taskId): ProjectTask
    {
        return ProjectTask::query()
            ->where('assigned_to', auth()->id())
            ->findOrFail($taskId);
    }

    private function hasDraftVisitEvidence(): bool
    {
        foreach ([$this->visitNotes, $this->visitLatitude, $this->visitLongitude, $this->visitAccuracy, $this->visitPhotos] as $values) {
            foreach ($values as $value) {
                if ($value instanceof TemporaryUploadedFile) {
                    return true;
                }

                if (is_string($value) && trim($value) !== '') {
                    return true;
                }

                if ($value !== null && ! is_string($value)) {
                    return true;
                }
            }
        }

        return false;
    }
}
