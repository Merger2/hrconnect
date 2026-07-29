<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLogDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_log_id',
        'entity_type',
        'entity_id',
        'field',
        'old_value',
        'new_value',
        'metadata',
        'integrity_hash',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'metadata' => 'json',
    ];

    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class);
    }

    public function hasValidIntegrityHash(): bool
    {
        if (! is_string($this->integrity_hash)) {
            return false;
        }

        return hash_equals($this->integrity_hash, $this->makeIntegrityHash());
    }

    public function makeIntegrityHash(): string
    {
        return hash_hmac('sha256', json_encode([
            'activity_log_id' => $this->activity_log_id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'field' => $this->field,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
        ], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function forceFill($attributes)
    {
        if (app()->bound('auth') && auth()->check()) {
            throw new AuthorizationException('Activity log details are append-only and cannot be modified.');
        }

        return parent::forceFill($attributes);
    }

    public function delete()
    {
        throw new AuthorizationException('Activity log details are append-only and cannot be deleted.');
    }
}
