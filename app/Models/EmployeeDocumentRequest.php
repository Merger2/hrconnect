<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperEmployeeDocumentRequest
 */
final class EmployeeDocumentRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_READY = 'ready';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'employee_id',
        'document_type_id',
        'requested_by',
        'request_source',
        'purpose',
        'details',
        'due_date',
        'status',
        'uploaded_path',
        'uploaded_original_name',
        'uploaded_at',
        'generated_path',
        'generated_template_id',
        'generated_at',
        'metadata',
        'reviewed_by',
        'reviewed_at',
        'fulfillment_note',
        'rejection_note',
    ];

    protected $casts = [
        'due_date' => 'date',
        'uploaded_at' => 'datetime',
        'generated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentType::class, 'document_type_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function generatedTemplate(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentTemplate::class, 'generated_template_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_REQUESTED => __('Requested'),
            self::STATUS_UPLOADED => __('Uploaded'),
            self::STATUS_GENERATED => __('Generated'),
            self::STATUS_READY => __('Ready'),
            self::STATUS_REJECTED => __('Rejected'),
            self::STATUS_EXPIRED => __('Expired'),
            default => ucfirst($this->status),
        };
    }

    public function documentTypeLabel(): string
    {
        return $this->documentType?->name ?? '—';
    }

    /**
     * Blade convenience accessor: the User behind the request's Employee.
     */
    public function getUserAttribute(): ?User
    {
        return $this->employee?->user;
    }
}
