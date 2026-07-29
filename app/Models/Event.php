<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperEvent
 */
#[Fillable(['company_id', 'title', 'slug', 'description', 'type', 'location', 'start_at', 'end_at', 'timezone', 'color', 'is_all_day', 'is_active', 'created_by'])]
class Event extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_GENERAL = 'general';

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_TRAINING = 'training';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_SOCIAL = 'social';

    public static function types(): array
    {
        return [
            self::TYPE_GENERAL => __('General'),
            self::TYPE_HOLIDAY => __('Holiday'),
            self::TYPE_TRAINING => __('Training'),
            self::TYPE_MEETING => __('Meeting'),
            self::TYPE_SOCIAL => __('Social'),
        ];
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
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
