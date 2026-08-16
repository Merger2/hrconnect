<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperProjectTask
 */
class ProjectTask extends Model
{
    use HasFactory;

    public const STATUS_TODO = 'todo';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'project_id',
        'company_id',
        'assigned_to',
        'title',
        'status',
        'priority',
        'due_date',
        'description',
        'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ProjectTaskChecklistItem::class);
    }

    public function visitEvidences(): HasMany
    {
        return $this->hasMany(ProjectVisitEvidence::class)->latest('visited_at');
    }
}
