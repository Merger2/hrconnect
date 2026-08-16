<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * @mixin IdeHelperFaceDescriptor
 */
class FaceDescriptor extends Model
{
    use HasFactory, HasNeighbors;

    protected $fillable = [
        'employee_id',
        'embedding',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'embedding' => Vector::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
