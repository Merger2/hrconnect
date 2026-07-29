<?php

namespace App\Livewire\Admin;

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class KnowledgeBaseManager extends Component
{
    use AuthorizesRequests;
    use InteractsWithBanner;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = 'all';

    public string $statusFilter = 'all';

    // Upload modal
    public bool $showUploadModal = false;

    public string $uploadTitle = '';

    public string $uploadCategory = '';

    /** @var UploadedFile|null */
    public $uploadFile = null;

    // Detail modal
    public bool $showDetailModal = false;

    public ?int $detailDocumentId = null;

    protected function rules(): array
    {
        return [
            'uploadTitle' => ['required', 'string', 'max:255'],
            'uploadCategory' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, KnowledgeBaseCategory::cases()))],
            'uploadFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function boot(): void
    {
        Gate::authorize('manage_knowledgebase');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->reset('detailDocumentId');
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        $this->reset('detailDocumentId');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->reset('detailDocumentId');
    }

    public function showUpload(): void
    {
        $this->resetErrorBag();
        $this->reset(['uploadTitle', 'uploadCategory', 'uploadFile']);
        $this->uploadCategory = KnowledgeBaseCategory::GENERAL->value;
        $this->showUploadModal = true;
    }

    public function upload(): void
    {
        $validated = $this->validate();

        /** @var KnowledgeBaseService $kbService */
        $kbService = app(KnowledgeBaseService::class);

        $kbService->uploadPdf(
            pdf: $validated['uploadFile'],
            title: $validated['uploadTitle'],
            category: KnowledgeBaseCategory::from($validated['uploadCategory']),
            owner: auth()->user(),
        );

        $this->showUploadModal = false;
        $this->reset(['uploadTitle', 'uploadCategory', 'uploadFile']);
        $this->banner(__('Dokumen berhasil diupload. Embedding sedang diproses.'));
    }

    public function showDetail(int $id): void
    {
        $this->detailDocumentId = $id;
        $this->showDetailModal = true;
    }

    public function delete(int $id): void
    {
        $kb = KnowledgeBase::findOrFail($id);

        /** @var KnowledgeBaseService $kbService */
        $kbService = app(KnowledgeBaseService::class);
        $kbService->deleteKnowledgeBase($kb);

        $this->banner(__('Dokumen berhasil dihapus.'));
    }

    public function reindex(int $id): void
    {
        $kb = KnowledgeBase::findOrFail($id);

        /** @var KnowledgeBaseService $kbService */
        $kbService = app(KnowledgeBaseService::class);
        $kbService->reindex($kb);

        $this->banner(__('Embedding sedang diproses ulang.'));
    }

    public function render()
    {
        $documents = KnowledgeBase::select('id', 'title', 'category', 'status', 'source_document', 'file_size', 'chunk_count', 'is_indexed', 'created_at')
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $detailDoc = $this->detailDocumentId
            ? KnowledgeBase::with(['chunks' => fn ($q) => $q->select('id', 'knowledge_base_id', 'content', 'page_number', 'created_at')])
                ->select('id', 'title', 'category', 'status', 'source_document', 'file_size', 'chunk_count', 'is_indexed', 'created_at')
                ->find($this->detailDocumentId)
            : null;

        return view('livewire.admin.knowledge-base-manager', [
            'documents' => $documents,
            'detailDoc' => $detailDoc,
            'categories' => KnowledgeBaseCategory::cases(),
            'statusOptions' => KnowledgeBaseStatus::cases(),
        ]);
    }
}
