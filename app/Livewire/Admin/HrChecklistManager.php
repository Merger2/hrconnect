<?php

namespace App\Livewire\Admin;

use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\User;
use App\Support\HrChecklistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class HrChecklistManager extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public array $taskNotes = [];

    public bool $showCreateCaseModal = false;

    public ?int $employeeId = null;

    public string $type = '';

    public string $effectiveDate = '';

    public ?int $templateId = null;

    public string $activeTab = 'cases';

    public ?int $selectedCaseId = null;

    public function mount(): void
    {
        // M19 AUDIT: sebelumnya hanya route gate (can('viewAny', HrChecklistCase::class))
        // tanpa authorize in-component — method wire-callable (createCase/startCase/
        // cancelCase/updateTask) bisa dipanggil langsung. Guard di mount + tiap mutasi.
        $this->authorize('viewAny', HrChecklistCase::class);
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'activeTab' => ['except' => 'cases'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['cases', 'templates'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function selectCase(int $caseId): void
    {
        $this->selectedCaseId = $caseId;
    }

    public function unselectCase(): void
    {
        $this->selectedCaseId = null;
    }

    public function cancelCase(int $caseId, HrChecklistService $service): void
    {
        $case = HrChecklistCase::query()->findOrFail($caseId);
        $this->authorize('cancel', $case);

        $service->cancelCase($case);
        $this->dispatch('banner', message: __('Case cancelled.'));
    }

    public function updateTask(int $taskId, string $status, HrChecklistService $service): void
    {
        $task = HrChecklistTask::query()->findOrFail($taskId);
        $this->authorize('update', $task);

        $note = $this->taskNotes[$taskId] ?? null;

        $service->updateTaskStatus($task, auth()->user(), $status, $note);

        unset($this->taskNotes[$taskId]);
        $this->dispatch('banner', message: __('Task updated.'));
    }

    public function createCase(): void
    {
        $this->reset(['employeeId', 'type', 'effectiveDate', 'templateId']);
        $this->showCreateCaseModal = true;
    }

    public function startCase(HrChecklistService $service): void
    {
        // isolated

        $this->validate([
            'employeeId' => 'required|exists:users,id',
            'type' => 'required|string',
            'effectiveDate' => 'required|date',
            'templateId' => 'required|exists:hr_checklist_templates,id',
        ]);

        $employee = User::query()->findOrFail($this->employeeId);
        $template = HrChecklistTemplate::query()->findOrFail($this->templateId);

        $service->createCase($employee, $template, auth()->user(), $this->effectiveDate);

        $this->showCreateCaseModal = false;
        $this->reset(['employeeId', 'type', 'effectiveDate', 'templateId']);
        $this->dispatch('banner', message: __('Case created successfully.'));
    }

    public function render(): View
    {
        /** @var LengthAwarePaginator $cases */
        $cases = HrChecklistCase::with(['user.employee.division', 'user.employee.position', 'template'])
            ->when($this->search, fn ($q) => $q->whereHas('user', function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('employee', fn ($eq) => $eq->where('employee_number', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        $templates = HrChecklistTemplate::query()
            ->where('is_active', true)
            ->get();

        $selectedCase = null;
        if ($this->selectedCaseId !== null) {
            $selectedCase = HrChecklistCase::with([
                'user.employee.division',
                'user.employee.position',
                'template',
                'tasks' => fn ($q) => $q->with(['assignee', 'dependency'])->orderBy('id'),
            ])->find($this->selectedCaseId);
        }

        $employeeOptions = User::query()
            ->whereHas('employee')
            ->with('employee')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => (object) [
                'id' => $u->id,
                'name' => $u->name,
                'nip' => $u->employee?->employee_number,
            ]);

        $templateOptions = HrChecklistTemplate::query()
            ->where('is_active', true)
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->get()
            ->map(fn (HrChecklistTemplate $t) => (object) [
                'id' => $t->id,
                'name' => $t->name,
            ]);

        return view('livewire.admin.hr-checklist-manager', [
            'cases' => $cases,
            'templates' => $templates,
            'selectedCase' => $selectedCase,
            'types' => HrChecklistTemplate::types(),
            'caseStatuses' => HrChecklistCase::statuses(),
            'caseColumns' => HrChecklistCase::statuses(),
            'taskColumns' => HrChecklistTask::statuses(),
            'employeeOptions' => $employeeOptions,
            'templateOptions' => $templateOptions,
        ]);
    }
}
