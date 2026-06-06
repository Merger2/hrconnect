<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperCompanySetting
 */
#[Fillable(['company_id', 'key', 'value', 'description'])]
class CompanySetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("settings:{$key}", now()->addDay(),
            fn () => static::where('key', $key)->first()?->value ?? $default);
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("settings:{$key}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
