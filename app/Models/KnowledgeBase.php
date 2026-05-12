<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['title', 'content', 'metadata', 'embedding'])]
class KnowledgeBase extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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
