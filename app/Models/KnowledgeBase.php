<?php

namespace App\Models;

use App\Enums\KnowledgeBaseCategory as KnowledgeBaseCategoryEnum;
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
        // Alias enum: nama kelas bentrok dengan Model KnowledgeBaseCategory
        // (relasi category() di bawah menunjuk ke Model, bukan enum).
        'category' => KnowledgeBaseCategoryEnum::class,
    ];

    /**
     * Kolom `category` (string enum) vs relasi `category()` (BelongsTo via
     * category_id): keduanya hidup berdampingan — cast ini membuat kolom
     * menjadi KnowledgeBaseCategoryEnum::GENERAL (dll) sehingga blade/resource
     * bisa memakai ->value tanpa error "Attempt to read property on string"
     * (bug P1: /knowledge-base 500 untuk SEMUA user saat ada dokumen).
     */
    public function knowledgeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeBaseChunk::class, 'knowledge_base_id');
    }

    /**
     * ⚠️ JANGAN dipakai: relasi ini menunjuk ke Model App\Models\KnowledgeBaseCategory
     * via category_id, tapi kolom `category` (string enum, di-cast ke enum)
     * selalu menang saat akses atribut. 0 pemakaian di seluruh repo — memakai
     * ->category() / with('category') / load('category') akan meng-override
     * nilai enum dengan Model/null dan merusak blade yang pakai ->value.
     * Kolom category_id sengaja tidak di-populate.
     */
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
