<?php

namespace App\Models;

use App\Enums\TerCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
