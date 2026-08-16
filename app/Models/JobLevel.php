<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperJobLevel
 */
class JobLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rank'];

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }
}
