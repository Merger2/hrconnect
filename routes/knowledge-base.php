<?php

use App\Enums\KnowledgeBaseStatus;
use App\Livewire\Admin\KnowledgeBaseManager;
use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('knowledge-base')->name('knowledge-base.')->group(function () {
    Route::get('/', function () {
        $documents = KnowledgeBase::select('id', 'title', 'category', 'status', 'source_document', 'chunk_count', 'created_at')
            ->where('status', KnowledgeBaseStatus::READY)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('source_document')
            ->values();

        return view('knowledge-base.index', compact('documents'));
    })->name('index');
    Route::get('/manage', KnowledgeBaseManager::class)
        ->middleware('can:manage_knowledgebase')
        ->name('manage');
});
