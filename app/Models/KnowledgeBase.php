<?php

namespace App\Models;

use App\Enums\KnowledgeBaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperKnowledgeBase
 */
class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledgeable_type',
        'knowledgeable_id',
        'title',
        'content',
        'category',
        'embedding',
        'status',
        'source_document',
        'page_number',
        'category_id',
        'summary',
        'source_type',
        'file_type',
        'file_size',
        'original_filename',
        'chunk_count',
        'is_indexed',
        'indexed_at',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'page_number' => 'integer',
        'file_size' => 'integer',
        'chunk_count' => 'integer',
        'is_indexed' => 'boolean',
        'indexed_at' => 'datetime',
        'status' => KnowledgeBaseStatus::class,
    ];

    public function knowledgeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeBaseChunk::class, 'knowledge_base_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function scopeIndexed($query)
    {
        return $query->where('is_indexed', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
