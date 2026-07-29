<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrChecklistCase extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'template_id',
        'user_id',
        'type',
        'status',
        'effective_date',
        'started_by',
        'completed_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrChecklistTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HrChecklistTask::class, 'case_id');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? __(str($this->status)->headline()->toString());
    }

    public function progressPercent(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();

        if ((int) $total === 0) {
            return 0;
        }

        $closed = $this->closed_tasks_count
            ?? $this->tasks()->whereIn('status', HrChecklistTask::closedStatuses())->count();

        return (int) round(((int) $closed / (int) $total) * 100);
    }

    /**
     * @return array{total:int,closed:int,pending:int,blocked:int,overdue:int,clearance_ready:bool}
     */
    public function clearanceSummary(): array
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get();
        $closedStatuses = HrChecklistTask::closedStatuses();

        $closed = $tasks->whereIn('status', $closedStatuses)->count();
        $blocked = $tasks->where('status', HrChecklistTask::STATUS_BLOCKED)->count();

        return [
            'total' => $tasks->count(),
            'closed' => $closed,
            'pending' => $tasks->where('status', HrChecklistTask::STATUS_PENDING)->count(),
            'blocked' => $blocked,
            'overdue' => $tasks->filter(fn (HrChecklistTask $task): bool => $task->isOverdue())->count(),
            'clearance_ready' => $tasks->count() > 0 && $closed === $tasks->count() && $blocked === 0,
        ];
    }
}
