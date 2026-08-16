<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class KnowledgeBaseDetailController extends Controller
{
    public function __invoke(KnowledgeBase $knowledgeBase): View|RedirectResponse
    {
        // Dokumen non-publik (draft/arsip) tidak boleh dilihat user.
        if ($knowledgeBase->status !== KnowledgeBaseStatus::READY) {
            return redirect()->route('knowledge-base.index');
        }

        return view('knowledge-base.show', compact('knowledgeBase'));
    }
}
