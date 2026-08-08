<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperKpiGroup
 */
class KpiGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'weight',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function kpiTemplates(): HasMany
    {
        return $this->hasMany(KpiTemplate::class);
    }

    public function activeKpiTemplates(): HasMany
    {
        return $this->hasMany(KpiTemplate::class)->where('is_active', true);
    }
}
