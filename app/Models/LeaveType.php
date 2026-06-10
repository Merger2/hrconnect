<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
