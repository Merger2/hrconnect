<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_task_id',
        'title',
        'is_done',
        'sort_order',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'sort_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }
}
