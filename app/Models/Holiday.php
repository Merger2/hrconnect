<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'name', 'is_active'])]
class Holiday extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function isHoliday(Carbon $date): bool
    {
        return static::where('date', $date->toDateString())
        ->where('is_active', true)
        ->exists();
    }
}
