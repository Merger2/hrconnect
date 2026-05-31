<?php

namespace App\Http\Controllers\Api;

use App\Enums\KnowledgeBaseCategory;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KnowledgeBaseController — RAG chat + upload PDF.
 *
 * Authorization via KnowledgeBasePolicy:
 * - chat: VIEW_KNOWLEDGEBASE permission (default super-admin + hr-manager)
 * - upload/destroy: MANAGE_KNOWLEDGEBASE (super-admin + hr-manager)
 *
 * Endpoints:
 * - POST /api/v1/knowledgebase/chat (throttle 20/menit)
 * - POST /api/v1/knowledgebase (multipart PDF upload, max 10MB)
 * - DELETE /api/v1/knowledgebase/{id}
 */
class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $kbService,
    ) {}

    /**
     * POST /knowledgebase/chat — RAG query (Gemini + pgvector + pg_trgm fallback).
     */
    public function chat(Request $request): JsonResponse
    {
        $this->authorize('chat', KnowledgeBase::class);

        $data = $request->validate([
            'question' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $result = $this->kbService->chat($data['question']);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * POST /knowledgebase — upload PDF + dispatch embedding jobs per chunk.
     */
    public function upload(Request $request): JsonResponse
    {
        $this->authorize('create', KnowledgeBase::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'in:hr_policy,it_guide,general,finance,other'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $category = isset($data['category']) ? KnowledgeBaseCategory::from($data['category']) : null;

        $kb = $this->kbService->uploadPdf(
            pdf: $request->file('file'),
            title: $data['title'],
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

    /**
     * DELETE /knowledgebase/{id} — hapus KB record + semua chunks dengan source_document sama.
     */
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
