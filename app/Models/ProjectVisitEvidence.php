<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'old_attributes' => 'array',
            'evidence_metadata' => 'array',
            'evidence_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
