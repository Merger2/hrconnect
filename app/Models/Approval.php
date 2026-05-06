<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['approver_id','level', 'status', 'notes'])]
class Approval extends Model
{
    use HasFactory; 
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'status' => ApprovalStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function approvable(): MorphTo 
    {
        return $this->morphTo();
    }
}
