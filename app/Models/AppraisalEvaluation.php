<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAppraisalEvaluation
 */
class AppraisalEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'appraisal_id',
        'kpi_template_id',
        'self_score',
        'manager_score',
        'comments',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function kpiTemplate(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class);
    }
}
