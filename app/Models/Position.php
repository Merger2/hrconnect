<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPosition
 */
#[Fillable(['division_id', 'name', 'code', 'grade', 'basic_salary', 'allowance_jabatan', 'is_active'])]
class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'basic_salary' => 'decimal:2',
            'allowance_jabatan' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
