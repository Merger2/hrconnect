<?php

namespace App\Models;

use App\Enums\TerCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperTaxConfig
 */
#[Fillable(['ter_category', 'min_income', 'max_income', 'rate', 'effective_rate'])]
class TaxConfig extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ter_category' => TerCategory::class,
            'min_income' => 'decimal:2',
            'max_income' => 'decimal:2',
            'rate' => 'decimal:4',
            'effective_rate' => 'decimal:4',
        ];
    }

    public static function cachedAll(): array
    {
        return Cache::remember(
            'tax_configs',
            now()->addDay(),
            fn () => static::all()->map(fn (TaxConfig $t) => [
                'ter_category' => $t->ter_category instanceof \BackedEnum
                    ? $t->ter_category->value
                    : $t->ter_category,
                'min_income' => (float) $t->min_income,
                'max_income' => (float) $t->max_income,
                'rate' => (float) $t->rate,
                'effective_rate' => $t->effective_rate !== null
                    ? (float) $t->effective_rate
                    : null,
            ])->toArray()
        );
    }
}
