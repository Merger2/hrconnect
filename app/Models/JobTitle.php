<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTitle extends Model
{
    use HasFactory;
    use HasTimestamps;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'level',
        'job_level_id',
        'division_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (JobTitle $jobTitle): void {
            User::query()
                ->where('job_title_id', $jobTitle->id)
                ->update(['job_title_id' => null]);
        });
    }
}
