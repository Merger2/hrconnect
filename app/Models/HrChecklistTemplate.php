<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperHrChecklistTemplate
 */
class HrChecklistTemplate extends Model
{
    use HasFactory;

    public const TYPE_ONBOARDING = 'onboarding';

    public const TYPE_OFFBOARDING = 'offboarding';

    protected $fillable = [
        'type',
        'name',
        'description',
        'division_id',
        'job_title_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(HrChecklistTemplateItem::class, 'template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(HrChecklistCase::class, 'template_id');
    }

    public static function types(): array
    {
        return [
            self::TYPE_ONBOARDING => __('Onboarding'),
            self::TYPE_OFFBOARDING => __('Offboarding'),
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? __(str($this->type)->headline()->toString());
    }
}
