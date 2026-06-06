<?php

namespace App\Http\Controllers\Api;

use App\Enums\KnowledgeBaseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChatRequest;
use App\Http\Requests\Api\UploadDocumentRequest;
use App\Models\KnowledgeBase;
use App\Services\KnowledgeBaseService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Knowledge Base')]
class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $kbService,
    ) {}

    #[Endpoint(title: 'Chat', description: 'Ask a question against the knowledge base (RAG with Gemini + pgvector + pg_trgm fallback).')]
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

    #[Endpoint(title: 'Upload Document', description: 'Upload PDF document to knowledge base (max 10MB). Embedding processing is async.')]
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

    #[Endpoint(title: 'Delete Document', description: 'Delete knowledge base document and all associated chunks.')]
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
