<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectVisitEvidence extends Model
{
    use HasFactory;

    protected $table = 'project_visit_evidences';

    protected $fillable = [
        'log_name',
        'event',
        'subject_type',
        'subject_id',
        'attributes',
        'old_attributes',
        'tags',
        'performed_by_type',
        'performed_by_id',
        'batch_uuid',
        'user_id',
        'evidence_url',
        'evidence_type',
        'evidence_filename',
        'evidence_size',
        'evidence_metadata',
        'evidence_hash',
        'project_id',
        'evidence_description',
        'project_task_id',
        'company_id',
        'visited_at',
        'latitude',
        'longitude',
        'accuracy_meters',
        'address',
        'notes',
        'photo_disk',
        'photo_path',
        'photo_original_name',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'old_attributes' => 'array',
            'evidence_metadata' => 'array',
            'evidence_size' => 'integer',
            'visited_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy_meters' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
