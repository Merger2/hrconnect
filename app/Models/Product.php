<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProduct
 */
#[Fillable(['company_id', 'name', 'sku', 'status', 'stock_tracking', 'stock_quantity', 'reorder_point', 'price'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DISCONTINUED = 'discontinued';

    protected function casts(): array
    {
        return [
            'stock_tracking' => 'boolean',
            'stock_quantity' => 'integer',
            'reorder_point' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isLowStock(): bool
    {
        if (! $this->stock_tracking || $this->reorder_point <= 0) {
            return false;
        }

        return $this->stock_quantity <= $this->reorder_point;
    }
}
