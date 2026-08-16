<?php

namespace App\Livewire\User;

use App\Models\EmployeeDocumentRequest;
use App\Services\EmployeeDocumentRequestService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class EmployeeDocumentRequestPage extends Component
{
    use WithFileUploads;

    public string $documentType = '';

    public string $purpose = '';

    public string $details = '';

    public $attachment = null;

    public bool $showModal = false;

    public ?int $uploadingRequestId = null;

    protected function getRequestStats(EmployeeDocumentRequestService $service): array
    {
        $user = auth()->user();

        $all = EmployeeDocumentRequest::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))->get();

        return [
            'total' => $all->count(),
            'in_progress' => $all->whereIn('status', [
                EmployeeDocumentRequest::STATUS_PENDING,
                EmployeeDocumentRequest::STATUS_REQUESTED,
                EmployeeDocumentRequest::STATUS_GENERATED,
                EmployeeDocumentRequest::STATUS_UPLOADED,
            ])->count(),
            'ready' => $all->where('status', EmployeeDocumentRequest::STATUS_READY)->count(),
            'needs_upload' => $all->where('status', EmployeeDocumentRequest::STATUS_REQUESTED)->count(),
        ];
    }

    public function render(EmployeeDocumentRequestService $service): View
    {
        $user = auth()->user();

        return view('livewire.user.employee-document-request-page', [
            'requests' => EmployeeDocumentRequest::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->paginate(10),
            'documentTypes' => $service->types(),
            'requestStats' => $this->getRequestStats($service),
        ]);
    }

    public function create(): void
    {
        $this->reset(['documentType', 'purpose', 'details']);
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
    }

    public function store(EmployeeDocumentRequestService $service): void
    {
        $this->validate([
            'documentType' => 'required|string',
            'purpose' => 'required|string|max:500',
            'details' => 'nullable|string|max:2000',
        ]);

        $service->request(auth()->user(), [
            'document_type' => $this->documentType,
            'purpose' => $this->purpose,
            'details' => $this->details,
        ]);

        $this->reset(['documentType', 'purpose', 'details', 'showModal']);
        $this->dispatch('banner', message: __('Document request submitted.'));
    }

    public function prepareUpload(int $id): void
    {
        $this->uploadingRequestId = $id;
        $this->attachment = null;
    }

    public function cancelUpload(): void
    {
        $this->reset(['uploadingRequestId', 'attachment']);
    }

    public function upload(EmployeeDocumentRequestService $service): void
    {
        $this->validate([
            'attachment' => 'required|file|max:10240',
        ]);

        $request = EmployeeDocumentRequest::findOrFail($this->uploadingRequestId);

        $service->upload($request, $this->attachment);

        $this->reset(['uploadingRequestId', 'attachment']);
        $this->dispatch('banner', message: __('Document uploaded successfully.'));
    }
}
