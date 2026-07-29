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

    public $attachment = null;

    public function render(EmployeeDocumentRequestService $service): View
    {
        return view('livewire.user.employee-document-request-page', [
            'requests' => EmployeeDocumentRequest::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate(10),
            'documentTypes' => $service->types(),
        ]);
    }

    public function submit(EmployeeDocumentRequestService $service): void
    {
        $this->validate([
            'documentType' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $service->request(auth()->user(), [
            'document_type' => $this->documentType,
            'attachment' => $this->attachment,
        ]);

        $this->reset(['documentType', 'attachment']);
        $this->dispatch('banner', message: __('Document request submitted.'));
    }
}
