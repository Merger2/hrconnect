<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\EmployeeDocumentTemplate;
use App\Services\DocumentTemplateRenderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DocumentTemplateLibrary extends Component
{
    public ?int $selectedTemplateId = null;

    public bool $confirmingTemplateDeletion = false;

    public ?int $deletingTemplateId = null;

    public function boot(): void
    {
        Gate::authorize('view_admin_document_requests');
    }

    public function render(DocumentTemplateRenderService $renderer): View
    {
        return view('livewire.admin.document-template-library', [
            'templates' => EmployeeDocumentTemplate::query()
                ->with('documentType')
                ->withCount('documents as generated_requests_count')
                ->latest()
                ->get(),
            'selectedTemplate' => $this->selectedTemplate,
            'templatePreviewHtml' => $this->templatePreviewHtml($renderer),
            'confirmingTemplateDeletion' => $this->confirmingTemplateDeletion,
        ]);
    }

    public function getSelectedTemplateProperty(): ?EmployeeDocumentTemplate
    {
        if ($this->selectedTemplateId === null) {
            return null;
        }

        return EmployeeDocumentTemplate::query()
            ->with('documentType')
            ->withCount('documents as generated_requests_count')
            ->find($this->selectedTemplateId);
    }

    public function selectTemplate(int $id): void
    {
        $this->selectedTemplateId = $id;
    }

    public function duplicateTemplate(int $id): void
    {
        $template = EmployeeDocumentTemplate::query()->findOrFail($id);

        EmployeeDocumentTemplate::create([
            'document_type_id' => $template->document_type_id,
            'name' => __('Copy of :name', ['name' => $template->name]),
            'content' => $template->content,
            'variables' => $template->variables,
            'paper_size' => $template->paper_size,
            'orientation' => $template->orientation,
            'header' => $template->header,
            'footer' => $template->footer,
            'layout_options' => $template->layout_options,
            'is_active' => false,
        ]);

        $this->dispatch('banner', message: __('Template duplicated.'));
    }

    public function activateTemplate(int $id): void
    {
        $template = EmployeeDocumentTemplate::query()->findOrFail($id);

        // Satu template aktif per tipe dokumen.
        EmployeeDocumentTemplate::query()
            ->where('document_type_id', $template->document_type_id)
            ->where('is_active', true)
            ->where('id', '!=', $template->id)
            ->update(['is_active' => false]);

        $template->update(['is_active' => true]);

        $this->dispatch('banner', message: __('Template activated.'));
    }

    public function deactivateTemplate(int $id): void
    {
        EmployeeDocumentTemplate::query()
            ->findOrFail($id)
            ->update(['is_active' => false]);

        $this->dispatch('banner', message: __('Template deactivated.'));
    }

    public function confirmDeleteTemplate(int $id): void
    {
        $this->deletingTemplateId = $id;
        $this->confirmingTemplateDeletion = true;
    }

    public function cancelDeleteTemplate(): void
    {
        $this->deletingTemplateId = null;
        $this->confirmingTemplateDeletion = false;
    }

    public function deleteTemplate(): void
    {
        if ($this->deletingTemplateId === null) {
            return;
        }

        $template = EmployeeDocumentTemplate::query()->findOrFail($this->deletingTemplateId);

        if ($template->documents()->exists()) {
            // Template sudah dipakai dokumen → deaktivasi agar riwayat tetap terjaga.
            $template->update(['is_active' => false]);
        } else {
            $template->delete();
        }

        if ($this->selectedTemplateId === $template->id) {
            $this->selectedTemplateId = null;
        }

        $this->cancelDeleteTemplate();
        $this->dispatch('banner', message: __('Template deleted.'));
    }

    public function downloadPreviewPdf(): void
    {
        $this->dispatch('notify', message: __('PDF preview generation is not available yet.'));
    }

    private function templatePreviewHtml(DocumentTemplateRenderService $renderer): string
    {
        $template = $this->selectedTemplate;

        if ($template === null) {
            return '';
        }

        $employee = Employee::query()->with('user')->first();

        if ($employee !== null) {
            return $renderer->renderHtml($template, $employee);
        }

        return (string) $template->content;
    }
}
