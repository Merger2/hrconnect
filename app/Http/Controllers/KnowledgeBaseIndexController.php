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
        // 2026-08-16 (fix bug P0): dulu `->unique('source_document')` menyatukan
        // SEMUA entry ber-source_document NULL (entry seeder) jadi 1 kartu —
        // index hanya menampilkan 1 dokumen dari puluhan. Sekarang entry tanpa
        // source_document dianggap dokumen mandiri (key unik per id), sedangkan
        // chunk PDF (source_document sama) tetap dikelompokkan jadi 1 dokumen.
        $documents = KnowledgeBase::select('id', 'title', 'category', 'status', 'source_document', 'chunk_count', 'content', 'created_at')
            ->where('status', KnowledgeBaseStatus::READY)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn (KnowledgeBase $kb) => (string) ($kb->source_document ?? 'entry-'.$kb->id))
            ->map(fn ($group) => $group->first())
            ->values();

        return view('knowledge-base.index', compact('documents'));
    }
}
