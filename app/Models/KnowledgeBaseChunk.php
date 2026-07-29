<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_id',
        'chunk_text',
        'chunk_index',
        'embedding_model',
        'token_count',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
    ];

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_base_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('chunk_index');
    }
}
