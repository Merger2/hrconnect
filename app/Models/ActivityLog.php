<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['log_name', 'description', 'event', 'attribute_changes', 'properties'])]
class ActivityLog extends Model
{
    protected function casts(): array
    {
        return [
            'attribute_changes' => 'array',
            'properties' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(null, 'subject_type', 'subject_id');
    }

    public function causer(): MorphTo
    {
        return $this->morphTo(null, 'causer_type', 'causer_id');
    }
}
