<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPerformanceReview
 */
#[Fillable(['employee_id', 'reviewer_id', 'status', 'review_date', 'period', 'final_score', 'notes'])]
class PerformanceReview extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'final_score' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }
}
