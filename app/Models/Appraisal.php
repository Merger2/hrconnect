<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperAppraisal
 */
class Appraisal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'evaluator_id',
        'calibrator_id',
        'period',
        'review_date',
        'meeting_date',
        'final_score',
        'status',
        'calibration_status',
        'notes',
        'employee_acknowledgement',
        'recommendations',
    ];

    protected $casts = [
        'review_date' => 'date',
        'meeting_date' => 'date',
        'employee_acknowledgement' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function calibrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calibrator_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}
