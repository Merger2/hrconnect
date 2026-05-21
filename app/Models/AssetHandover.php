<?php

namespace App\Models;

use App\Enums\HandoverCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_id', 'employee_id', 'handover_date', 'return_date', 'condition', 'category'])]
class AssetHandover extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'handover_date' => 'date',
            'return_date' => 'date',
            'category' => HandoverCategory::class,
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
