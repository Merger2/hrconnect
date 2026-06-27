<?php

namespace App\Http\Controllers\Api;

use App\Enums\KnowledgeBaseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChatRequest;
use App\Http\Requests\Api\ChatStreamRequest;
use App\Http\Requests\Api\UploadDocumentRequest;
use App\Models\KnowledgeBase;
use App\Services\KnowledgeBaseService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Knowledge Base')]
class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $kbService,
    ) {}

    #[Endpoint(title: 'List Documents', description: 'List all knowledge base documents (grouped by source document).')]
    public function index(Request $request): JsonResponse
    {
        $docs = KnowledgeBase::select('id', 'title', 'category', 'status', 'source_document', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('source_document')
            ->values()
            ->map(fn ($kb) => [
                'id' => $kb->id,
                'title' => $kb->title,
                'category' => $kb->category?->value,
                'status' => $kb->status?->value,
                'source_document' => $kb->source_document,
                'created_at' => $kb->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $docs,
        ]);
    }

    #[Endpoint(title: 'Chat', description: 'Ask a question against the knowledge base (RAG with Gemini + pgvector + pg_trgm fallback). Flow: Knowledge Base (usage).')]
    #[BodyParameter(name: 'question', description: 'Question text (min 5, max 500 chars)', required: true, type: 'string')]
    public function chat(ChatRequest $request): JsonResponse
    {
        $this->authorize('chat', KnowledgeBase::class);

        $result = $this->kbService->chat($request->validated('question'));

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    #[Endpoint(title: 'Chat Stream (SSE)', description: 'Streaming RAG answer via Server-Sent Events. Returns text/event-stream with text delta events. Fallback to sync /chat endpoint jika streaming gagal.')]
    public function chatStream(ChatStreamRequest $request): StreamedResponse
    {
        $this->authorize('chat', KnowledgeBase::class);

        $data = $request->validated();

        return response()->stream(function () use ($data) {
            foreach ($this->kbService->chatStream($data['question'], $data['conversation_id'] ?? null) as $event) {
                echo 'data: '.json_encode($event)."\n\n";
                ob_flush();
                flush();
            }

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, headers: [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    #[Endpoint(title: 'Upload Document', description: 'Upload PDF document to knowledge base (max 10MB). Embedding processing is async. Flow: Knowledge Base (Step 1/2) — Upload → Chat.')]
    #[BodyParameter(name: 'title', description: 'Document title', required: true, type: 'string')]
    #[BodyParameter(name: 'category', description: 'Document category', required: false, type: 'string')]
    #[BodyParameter(name: 'file', description: 'PDF file (max 10MB)', required: true, type: 'string', format: 'binary')]
    public function upload(UploadDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', KnowledgeBase::class);

        $category = $request->validated('category') ? KnowledgeBaseCategory::from($request->validated('category')) : null;

        $kb = $this->kbService->uploadPdf(
            pdf: $request->file('file'),
            title: $request->validated('title'),
            category: $category,
            owner: $request->user(),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'KnowledgeBase berhasil di-upload. Embedding sedang diproses async.',
            'data' => [
                'id' => $kb->id,
                'title' => $kb->title,
                'category' => $kb->category?->value,
                'status' => $kb->status?->value,
                'source_document' => $kb->source_document,
            ],
        ], 201);
    }

    #[Endpoint(title: 'Delete Document', description: 'Delete knowledge base document and all associated chunks. Flow: Knowledge Base (admin).')]
    public function destroy(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $this->authorize('delete', $knowledgeBase);

        $deleted = $this->kbService->deleteKnowledgeBase($knowledgeBase);

        return response()->json([
            'status' => 'success',
            'message' => 'KnowledgeBase dihapus',
            'data' => ['deleted_count' => $deleted],
        ]);
    }
}
