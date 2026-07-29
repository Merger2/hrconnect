<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperLeaveType
 */
#[Fillable(['name', 'code', 'quota', 'is_paid', 'is_active', 'deducts_from_quota', 'eligible_for_carry_forward'])]
class LeaveType extends Model
{
    use HasFactory;

    public const CATEGORY_ANNUAL = 'annual';

    public const CATEGORY_SICK = 'sick';

    public const CATEGORY_OTHER = 'other';

    public static function categories(): array
    {
        return [
            self::CATEGORY_ANNUAL => __('Annual'),
            self::CATEGORY_SICK => __('Sick'),
            self::CATEGORY_OTHER => __('Other'),
        ];
    }

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'deducts_from_quota' => 'boolean',
            'eligible_for_carry_forward' => 'boolean',
        ];
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    public function deductsFromQuota(): bool
    {
        return $this->deducts_from_quota;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Get the default attendance status for this leave type.
     * Paid leave types default to 'excused', unpaid to 'sick'.
     */
    public function attendanceStatus(): string
    {
        return $this->is_paid ? 'excused' : 'sick';
    }
}
