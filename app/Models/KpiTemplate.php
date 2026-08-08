<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperKpiTemplate
 */
class KpiTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_group_id',
        'name',
        'indicator_description',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'float',
        'is_active' => 'boolean',
    ];

    public function kpiGroup(): BelongsTo
    {
        return $this->belongsTo(KpiGroup::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}
