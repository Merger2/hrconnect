<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use Illuminate\Contracts\View\View;

class KnowledgeBaseIndexController extends Controller
{
    public function __invoke(): View
    {
        $documents = KnowledgeBase::select('id', 'title', 'category', 'status', 'source_document', 'chunk_count', 'created_at')
            ->where('status', KnowledgeBaseStatus::READY)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('source_document')
            ->values();

        return view('knowledge-base.index', compact('documents'));
    }
}
