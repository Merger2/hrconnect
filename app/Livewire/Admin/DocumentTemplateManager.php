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
        $documentTemplates = EmployeeDocumentTemplate::query()->with('documentType')->latest()->get();

        return view('livewire.admin.document-template-manager', [
            'documentTypes' => $service->types(),
            'documentTemplates' => $documentTemplates,
            'documentWorkflowTypes' => EmployeeDocumentType::query()->where('is_active', true)->orderBy('name')->get(),
            'documentWorkflowTemplates' => $documentTemplates,
            'documentTemplateVariables' => $this->templateVariables(),
            'templatePreviewHtml' => $this->templatePreviewHtml(),
            'editingDocumentType' => $this->creating || $this->editing,
            'templateEditorMode' => $this->editorMode,
            'documentTypeForm' => $this->documentTypeForm,
            'documentTemplateForm' => $this->documentTemplateForm,
            'templateBuilderForm' => $this->templateBuilderForm,
        ]);
    }

    /**
     * Available {{ placeholder }} variables shown in the builder.
     *
     * @return array<int, array{label:string,placeholder:string}>
     */
    private function templateVariables(): array
    {
        return [
            ['label' => __('Employee name'), 'placeholder' => '{{ employee.name }}'],
            ['label' => __('NIP'), 'placeholder' => '{{ employee.nip }}'],
            ['label' => __('Position'), 'placeholder' => '{{ employee.position }}'],
            ['label' => __('Company'), 'placeholder' => '{{ company.name }}'],
            ['label' => __('Date'), 'placeholder' => '{{ date }}'],
        ];
    }

    /**
     * Safe HTML preview for the live preview panel.
     */
    private function templatePreviewHtml(): string
    {
        $body = $this->documentTemplateForm['body'] ?? '';
        $heading = $this->templateBuilderForm['heading'] ?? '';
        $opening = $this->templateBuilderForm['opening'] ?? '';
        $closing = $this->templateBuilderForm['closing'] ?? '';

        if ($this->editorMode === 'html' && $body !== '') {
            return $body;
        }

        return implode("\n", array_filter([
            $heading !== '' ? "<h2>{$heading}</h2>" : null,
            $opening !== '' ? "<p>{$opening}</p>" : null,
            '<p>{{ employee.name }}</p>',
            $closing !== '' ? "<p>{$closing}</p>" : null,
        ]));
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

        $isActive = $this->documentTemplateForm['is_active'] ?? true;

        if ($isActive) {
            // Pastikan hanya satu template aktif per tipe dokumen.
            EmployeeDocumentTemplate::query()
                ->where('document_type_id', $this->documentTemplateForm['document_type_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        EmployeeDocumentTemplate::create([
            'name' => $this->documentTemplateForm['name'],
            'document_type_id' => $this->documentTemplateForm['document_type_id'],
            'content' => $this->templateBuilderForm['body'] ?? $this->documentTemplateForm['body'] ?? '',
            'variables' => array_keys(array_filter($this->templateBuilderForm)),
            'is_active' => $isActive,
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
