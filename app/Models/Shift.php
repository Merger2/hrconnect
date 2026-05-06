<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'start_time', 'end_time', 'is_active'])]
class Shift extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'start_time' => 'time',
            'end_time' => 'time',
            'is_active' => 'boolean',
        ];
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $start = Carbon::parse($this->attributes['start_time']);
                $end = Carbon::parse($this->attributes['end_time']);

                if ($end->lessThan($start)) {
                    $end->addDay();
                }

                return $start->diffInHours($end) . ' hours';
            }
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
