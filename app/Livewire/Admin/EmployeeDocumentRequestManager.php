<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Services\EmployeeDocumentRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class EmployeeDocumentRequestManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected EmployeeDocumentRequestService $documentService;

    // ── Filters ──
    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    // ── Bulk selection ──
    public array $selectedRequestIds = [];

    public bool $selectAll = false;

    // ── Create modal ──
    public bool $showCreateModal = false;

    /** @var array<int> */
    public array $targetUserIds = [];

    public string $documentType = '';

    public string $purpose = '';

    public string $details = '';

    public string $dueDate = '';

    public bool $generateImmediately = false;

    // ── Approve / Reject modals ──
    public bool $confirmingReady = false;

    public bool $confirmingRejection = false;

    public ?EmployeeDocumentRequest $reviewRequest = null;

    public string $reviewNote = '';

    protected function rules(): array
    {
        return [
            'targetUserIds' => ['required', 'array', 'min:1'],
            'targetUserIds.*' => ['required', 'integer', 'exists:employees,id'],
            'documentType' => ['required', 'integer', 'exists:employee_document_types,id'],
            'purpose' => ['required', 'string', 'max:500'],
            'details' => ['nullable', 'string', 'max:2000'],
            'dueDate' => ['nullable', 'date', 'after_or_equal:today'],
            'generateImmediately' => ['boolean'],
        ];
    }

    public function boot(EmployeeDocumentRequestService $documentService): void
    {
        $this->documentService = $documentService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedRequestIds = EmployeeDocumentRequest::query()
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('purpose', 'like', '%'.$this->search.'%')
                        ->orWhere('details', 'like', '%'.$this->search.'%')
                        ->orWhereHas('employee.user', fn ($uq) => $uq
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhereHas('employee', fn ($eq) => $eq
                                ->where('nip', 'like', '%'.$this->search.'%')
                            )
                        );
                }))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter !== 'all', fn ($q) => $q->where('document_type_id', (int) $this->typeFilter))
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedRequestIds = [];
        }
    }

    #[Computed]
    public function selectedDocumentTypeProfile(): ?EmployeeDocumentType
    {
        if (blank($this->documentType)) {
            return null;
        }

        return EmployeeDocumentType::find((int) $this->documentType);
    }

    #[Computed]
    public function statuses(): array
    {
        return [
            'all' => __('All statuses'),
            EmployeeDocumentRequest::STATUS_PENDING => __('Pending'),
            EmployeeDocumentRequest::STATUS_REQUESTED => __('Requested'),
            EmployeeDocumentRequest::STATUS_UPLOADED => __('Uploaded'),
            EmployeeDocumentRequest::STATUS_GENERATED => __('Generated'),
            EmployeeDocumentRequest::STATUS_READY => __('Ready'),
            EmployeeDocumentRequest::STATUS_REJECTED => __('Rejected'),
        ];
    }

    #[Computed]
    public function documentTypes(): array
    {
        return EmployeeDocumentType::query()
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->prepend(__('All types'), 'all')
            ->toArray();
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with('user')
            ->whereHas('user')
            ->get()
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => $e->full_name ?? $e->user?->name ?? '-',
                'nip' => $e->nip,
                'email' => $e->user?->email,
            ]);
    }

    #[Computed]
    public function adminDocumentTypes(): array
    {
        return EmployeeDocumentType::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (EmployeeDocumentType $t) => [
                $t->id => sprintf('%s (%s)', $t->name, $t->category ?? __('General')),
            ])
            ->toArray();
    }

    public function render()
    {
        $this->authorize('viewAdminDocumentRequests');

        return view('livewire.admin.employee-document-request-manager', [
            'requests' => $this->requests,
        ]);
    }

    #[Computed]
    public function requests()
    {
        return EmployeeDocumentRequest::query()
            ->with([
                'employee.user.division',
                'documentType',
                'requester',
                'reviewer',
            ])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('purpose', 'like', '%'.$this->search.'%')
                    ->orWhere('details', 'like', '%'.$this->search.'%')
                    ->orWhereHas('employee.user', fn ($uq) => $uq
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('employee', fn ($eq) => $eq
                            ->where('nip', 'like', '%'.$this->search.'%')
                        )
                    );
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('document_type_id', (int) $this->typeFilter))
            ->latest()
            ->paginate(20);
    }

    // ── Create ──

    public function createRequest(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);

        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function storeRequest(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);

        $this->validate();

        $data = [
            'document_type_id' => (int) $this->documentType,
            'purpose' => $this->purpose,
            'details' => $this->details ?: null,
            'due_date' => $this->dueDate ?: null,
        ];

        foreach ($this->targetUserIds as $employeeId) {
            $employee = Employee::findOrFail($employeeId);

            $this->documentService->createForEmployeeByAdmin(
                $employee,
                $data,
                $this->generateImmediately,
            );
        }

        $this->closeCreateModal();
        $this->dispatch('saved');
    }

    // ── Generate PDF ──

    public function generate(int $id): void
    {
        $request = EmployeeDocumentRequest::findOrFail($id);

        $this->authorize('generate', $request);

        try {
            $this->documentService->generate($request);
            $this->dispatch('saved');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ── Approve / Mark Ready ──

    public function confirmReady(int $id): void
    {
        $request = EmployeeDocumentRequest::findOrFail($id);

        $this->authorize('fulfill', $request);

        $this->reviewRequest = $request;
        $this->reviewNote = '';
        $this->confirmingReady = true;
    }

    public function markReady(): void
    {
        abort_if(! $this->reviewRequest, 404);

        $this->authorize('fulfill', $this->reviewRequest);

        $this->validate([
            'reviewNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->documentService->approve($this->reviewRequest, $this->reviewNote ?: null);

        $this->cancelReview();
        $this->dispatch('saved');
    }

    // ── Reject ──

    public function confirmReject(int $id): void
    {
        $request = EmployeeDocumentRequest::findOrFail($id);

        $this->authorize('reject', $request);

        $this->reviewRequest = $request;
        $this->reviewNote = '';
        $this->confirmingRejection = true;
    }

    public function reject(): void
    {
        abort_if(! $this->reviewRequest, 404);

        $this->authorize('reject', $this->reviewRequest);

        $this->validate([
            'reviewNote' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $this->documentService->reject($this->reviewRequest, $this->reviewNote);

        $this->cancelReview();
        $this->dispatch('saved');
    }

    public function cancelReview(): void
    {
        $this->confirmingReady = false;
        $this->confirmingRejection = false;
        $this->reviewRequest = null;
        $this->reviewNote = '';
    }

    // ── Bulk actions ──

    public function bulkGenerate(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);

        $requests = EmployeeDocumentRequest::whereIn('id', $this->selectedRequestIds)->get();

        foreach ($requests as $request) {
            if (Auth::user()->can('generate', $request)) {
                try {
                    $this->documentService->generate($request);
                } catch (\RuntimeException) {
                    // Skip requests without active template
                }
            }
        }

        $this->selectedRequestIds = [];
        $this->selectAll = false;
        $this->dispatch('saved');
    }

    public function bulkApprove(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);

        $requests = EmployeeDocumentRequest::whereIn('id', $this->selectedRequestIds)->get();

        foreach ($requests as $request) {
            if (Auth::user()->can('fulfill', $request)) {
                $this->documentService->approve($request);
            }
        }

        $this->selectedRequestIds = [];
        $this->selectAll = false;
        $this->dispatch('saved');
    }

    public function bulkReject(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);

        $requests = EmployeeDocumentRequest::whereIn('id', $this->selectedRequestIds)->get();

        foreach ($requests as $request) {
            if (Auth::user()->can('reject', $request)) {
                $this->documentService->reject($request, __('Bulk rejection'));
            }
        }

        $this->selectedRequestIds = [];
        $this->selectAll = false;
        $this->dispatch('saved');
    }

    // ── Due date helpers ──

    public function setDueDatePreset(int $days): void
    {
        $this->dueDate = now()->addDays($days)->toDateString();
    }

    public function clearDueDate(): void
    {
        $this->dueDate = '';
    }

    // ── Preset (stub — bisa diperluas dengan template presets) ──

    public function applyRequestPreset(): void
    {
        session()->flash('info', __('Preset feature — select a document type with an active template.'));
    }

    // ── Helpers ──

    protected function resetForm(): void
    {
        $this->targetUserIds = [];
        $this->documentType = '';
        $this->purpose = '';
        $this->details = '';
        $this->dueDate = '';
        $this->generateImmediately = false;
        $this->resetValidation();
    }
}
