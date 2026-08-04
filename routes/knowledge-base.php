<?php

use App\Http\Controllers\KnowledgeBaseIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'password.expired'])->prefix('knowledge-base')->name('knowledge-base.')->group(function () {
    Route::get('/', KnowledgeBaseIndexController::class)->name('index');
    Route::livewire('/manage', 'admin.knowledge-base-manager')
        ->middleware('can:manage_knowledgebase')
        ->name('manage');
});
