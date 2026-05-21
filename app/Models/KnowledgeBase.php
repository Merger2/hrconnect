<?php

namespace App\Models;

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['title', 'content', 'metadata', 'embedding', 'status', 'category', 'source_document', 'page_number'])]
class KnowledgeBase extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => KnowledgeBaseStatus::class,
            'category' => KnowledgeBaseCategory::class,
            'embedding' => 'vector',
        ];
    }

    public function knowledgeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function processEmbedding(): void
    {
        $this->update(['status' => 'processing']);
    }
}
