<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'address', 'is_main', 'is_active', 'latitude', 'longitude', 'radius'])]
class Branch extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function validateRadius(float $lat, float $lng, float $radius): bool
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng / 2) ** 2;
        $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $distance <= $radius;
    }
}
