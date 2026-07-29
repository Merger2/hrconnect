<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDocumentTemplate extends Model
{
    use HasFactory;

    protected $table = 'employee_document_templates';

    protected $fillable = [
        'document_type_id',
        'name',
        'content',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentType::class, 'document_type_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class, 'template_id');
    }
}
