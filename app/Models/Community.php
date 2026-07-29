<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCommunity
 */
#[Fillable(['company_id', 'name', 'slug', 'description', 'type', 'icon', 'cover_photo', 'is_active', 'created_by'])]
class Community extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_OPEN = 'open';

    public const TYPE_CLOSED = 'closed';

    public const TYPE_PRIVATE = 'private';

    public static function types(): array
    {
        return [
            self::TYPE_OPEN => __('Open'),
            self::TYPE_CLOSED => __('Closed'),
            self::TYPE_PRIVATE => __('Private'),
        ];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
