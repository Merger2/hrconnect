<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTraining
 */
#[Fillable(['company_id', 'name', 'code', 'description', 'type', 'provider', 'location', 'start_date', 'end_date', 'duration_minutes', 'status', 'is_mandatory', 'created_by'])]
class Training extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_EXTERNAL = 'external';

    public const TYPE_CERTIFICATION = 'certification';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public static function types(): array
    {
        return [
            self::TYPE_INTERNAL => __('Internal'),
            self::TYPE_EXTERNAL => __('External'),
            self::TYPE_CERTIFICATION => __('Certification'),
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNED => __('Planned'),
            self::STATUS_ONGOING => __('Ongoing'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_minutes' => 'integer',
            'is_mandatory' => 'boolean',
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
