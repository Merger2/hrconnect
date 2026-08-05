<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperEmployeeDocumentType
 */
class EmployeeDocumentType extends Model
{
    use HasFactory;

    protected $table = 'employee_document_types';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'is_required',
        'retention_days',
        'category',
        'requires_employee_upload',
        'admin_requestable',
        'auto_generate_enabled',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'retention_days' => 'integer',
        'requires_employee_upload' => 'boolean',
        'auto_generate_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(EmployeeDocumentTemplate::class, 'document_type_id');
    }

    public function activeTemplate(): ?EmployeeDocumentTemplate
    {
        return $this->hasMany(EmployeeDocumentTemplate::class, 'document_type_id')
            ->where('is_active', true)
            ->first();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class, 'document_type_id');
    }
}
