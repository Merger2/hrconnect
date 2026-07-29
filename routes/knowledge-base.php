<?php

use App\Livewire\Admin\KnowledgeBaseManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('knowledge-base')->name('knowledge-base.')->group(function () {
    Route::get('/', fn () => view('knowledge-base.index'))->name('index');
    Route::get('/manage', KnowledgeBaseManager::class)
        ->middleware('can:manage_knowledgebase')
        ->name('manage');
});
