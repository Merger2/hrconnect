<?php

namespace App\Models;

use App\Casts\PgVector;
use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperKnowledgeBase
 */
#[Fillable(['knowledgeable_type', 'knowledgeable_id', 'title', 'content', 'metadata', 'embedding', 'status', 'category', 'source_document', 'page_number'])]
class KnowledgeBase extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => KnowledgeBaseStatus::class,
            'category' => KnowledgeBaseCategory::class,
            'embedding' => PgVector::class,
        ];
    }

    public function knowledgeable(): MorphTo
    {
        return $this->morphTo();
    }
}
