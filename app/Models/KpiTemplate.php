<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function kpiGroup()
    {
        return $this->belongsTo(KpiGroup::class);
    }

    public function evaluations()
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}
