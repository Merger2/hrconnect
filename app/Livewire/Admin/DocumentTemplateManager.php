<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Services\EmployeeDocumentRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DocumentTemplateManager extends Component
{
    public array $documentTemplateForm = [];

    public array $documentTypeForm = [];

    public array $templateBuilderForm = [];

    public bool $creating = false;

    public bool $editing = false;

    public ?int $selectedId = null;

    public string $editorMode = 'builder';

    public function boot(): void
    {
        Gate::authorize('view_admin_document_requests');
    }

    public function render(EmployeeDocumentRequestService $service): View
    {
        return view('livewire.admin.document-template-manager', [
            'documentTypes' => $service->types(),
            'documentTemplates' => EmployeeDocumentTemplate::query()->with('documentType')->latest()->get(),
        ]);
    }

    public function startNewDocumentType(): void
    {
        $this->documentTypeForm = [
            'name' => '',
            'code' => '',
            'category' => '',
            'description' => '',
            'employee_requestable' => false,
            'admin_requestable' => false,
            'requires_employee_upload' => false,
            'auto_generate_enabled' => false,
            'is_active' => true,
        ];
        $this->creating = true;
    }

    public function cancelDocumentTypeEditor(): void
    {
        $this->creating = false;
        $this->editing = false;
        $this->documentTypeForm = [];
    }

    public function saveDocumentType(): void
    {
        $this->validate([
            'documentTypeForm.name' => ['required', 'string', 'max:255'],
            'documentTypeForm.code' => ['required', 'string', 'max:50', 'unique:employee_document_types,code'],
            'documentTypeForm.category' => ['required', 'string'],
        ]);

        EmployeeDocumentType::create($this->documentTypeForm);
        $this->cancelDocumentTypeEditor();
        $this->dispatch('banner', message: __('Document type created.'));
    }

    public function startNewDocumentTemplate(): void
    {
        $this->documentTemplateForm = [
            'document_type_id' => '',
            'name' => '',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'is_active' => true,
            'layout_options' => [
                'show_logo' => true,
                'show_accents' => true,
                'show_document_meta' => true,
                'header_company_name' => '',
                'header_contact' => '',
                'header_address' => '',
                'header_tagline' => '',
            ],
            'footer' => '',
            'body' => '',
        ];
        $this->templateBuilderForm = [
            'heading' => '',
            'opening' => '',
            'closing' => '',
            'main_paragraph' => '',
            'details_paragraph' => '',
            'signature_title' => '',
            'signature_name' => '',
        ];
        $this->creating = true;
    }

    public function saveDocumentTemplate(): void
    {
        $this->validate([
            'documentTemplateForm.name' => ['required', 'string', 'max:255'],
            'documentTemplateForm.document_type_id' => ['required', 'exists:employee_document_types,id'],
        ]);

        EmployeeDocumentTemplate::create([
            'name' => $this->documentTemplateForm['name'],
            'document_type_id' => $this->documentTemplateForm['document_type_id'],
            'content' => $this->templateBuilderForm['body'] ?? $this->documentTemplateForm['body'] ?? '',
            'variables' => array_keys(array_filter($this->templateBuilderForm)),
            'is_active' => $this->documentTemplateForm['is_active'] ?? true,
            'paper_size' => $this->documentTemplateForm['paper_size'] ?? 'a4',
            'orientation' => $this->documentTemplateForm['orientation'] ?? 'portrait',
            'layout_options' => $this->documentTemplateForm['layout_options'] ?? [],
        ]);

        $this->creating = false;
        $this->documentTemplateForm = [];
        $this->dispatch('banner', message: __('Document template created.'));
    }

    public function editDocumentTemplate(int $id): void
    {
        $template = EmployeeDocumentTemplate::query()->findOrFail($id);
        $this->selectedId = $id;
        $this->editing = true;

        $this->documentTemplateForm = [
            'document_type_id' => (string) $template->document_type_id,
            'name' => $template->name,
            'paper_size' => $template->paper_size ?? 'a4',
            'orientation' => $template->orientation ?? 'portrait',
            'is_active' => $template->is_active,
            'layout_options' => $template->layout_options ?? [],
            'footer' => '',
            'body' => $template->content ?? '',
        ];
    }

    public function resetDocumentTemplateForm(): void
    {
        $this->documentTemplateForm = [];
        $this->editing = false;
    }

    public function downloadPreviewPdf(): void
    {
        $this->dispatch('notify', message: __('PDF preview generation is not available yet.'));
    }
}
