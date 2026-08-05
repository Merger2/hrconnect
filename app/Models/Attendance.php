<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\BusinessRuleException;
use App\Traits\Approvable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $shift_id
 * @property CarbonImmutable $date
 * @property CarbonImmutable|null $clock_in Null jika status=absent dari DetectAlphaAttendanceCommand
 * @property CarbonImmutable|null $clock_out
 * @property numeric|null $lat_in
 * @property numeric|null $long_in
 * @property bool $clock_in_is_mocked
 * @property numeric|null $clock_in_accuracy
 * @property numeric|null $lat_out
 * @property numeric|null $long_out
 * @property bool|null $clock_out_is_mocked
 * @property numeric|null $clock_out_accuracy
 * @property string|null $device_fingerprint
 * @property numeric|null $face_similarity_score Akurasi kemiripan wajah dalam persentase (%) - clock-in
 * @property VerificationMethod|null $clock_out_verification_method face_verified|pin_verified|manual
 * @property numeric|null $clock_out_face_similarity_score Akurasi kemiripan wajah clock-out
 * @property string|null $photo_selfie_in
 * @property string|null $photo_selfie_out
 * @property AttendanceStatus $status
 * @property bool $is_wfa
 * @property ApprovalStatus|null $status_wfa
 * @property string|null $exception_type
 * @property string|null $exception_notes
 * @property int|null $approved_late_by
 * @property VerificationMethod|null $verification_method face_verified|pin_verified|manual
 * @property string|null $wfa_note
 * @property int $late_minutes
 * @property int|null $risk_score
 * @property string|null $risk_level
 * @property string|null $risk_factors
 * @property int|null $device_id
 * @property string $verification_type
 * @property string|null $photo_clock_in
 * @property string|null $photo_clock_out
 * @property string|null $ip_address
 * @property bool $is_offline_sync
 * @property string|null $synced_at
 * @property string|null $latitude
 * @property string|null $longitude
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property ApprovalStatus|null $approval_status
 * @property int|null $leave_type_id
 * @property string|null $note
 * @property-read Collection<int, Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read Employee|null $approvedLateBy
 * @property-read mixed $attachment
 * @property-read mixed $attachment_url
 * @property-read Employee|null $employee
 * @property-read mixed $lat_lng
 * @property-read mixed $latitude_in
 * @property-read mixed $latitude_out
 * @property-read mixed $longitude_in
 * @property-read mixed $longitude_out
 * @property-read Overtime|null $overtime
 * @property-read Shift|null $shift
 * @property-read mixed $time_in
 * @property-read mixed $time_out
 * @property-read User|null $user
 * @method static \Database\Factories\AttendanceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Attendance managedBy(\App\Models\User $admin)
 * @method static Builder<static>|Attendance newModelQuery()
 * @method static Builder<static>|Attendance newQuery()
 * @method static Builder<static>|Attendance onlyTrashed()
 * @method static Builder<static>|Attendance query()
 * @method static Builder<static>|Attendance whereApprovalStatus($value)
 * @method static Builder<static>|Attendance whereApprovedLateBy($value)
 * @method static Builder<static>|Attendance whereClockIn($value)
 * @method static Builder<static>|Attendance whereClockInAccuracy($value)
 * @method static Builder<static>|Attendance whereClockInIsMocked($value)
 * @method static Builder<static>|Attendance whereClockOut($value)
 * @method static Builder<static>|Attendance whereClockOutAccuracy($value)
 * @method static Builder<static>|Attendance whereClockOutFaceSimilarityScore($value)
 * @method static Builder<static>|Attendance whereClockOutIsMocked($value)
 * @method static Builder<static>|Attendance whereClockOutVerificationMethod($value)
 * @method static Builder<static>|Attendance whereCreatedAt($value)
 * @method static Builder<static>|Attendance whereDate($value)
 * @method static Builder<static>|Attendance whereDeletedAt($value)
 * @method static Builder<static>|Attendance whereDeviceFingerprint($value)
 * @method static Builder<static>|Attendance whereDeviceId($value)
 * @method static Builder<static>|Attendance whereEmployeeId($value)
 * @method static Builder<static>|Attendance whereExceptionNotes($value)
 * @method static Builder<static>|Attendance whereExceptionType($value)
 * @method static Builder<static>|Attendance whereFaceSimilarityScore($value)
 * @method static Builder<static>|Attendance whereId($value)
 * @method static Builder<static>|Attendance whereIpAddress($value)
 * @method static Builder<static>|Attendance whereIsOfflineSync($value)
 * @method static Builder<static>|Attendance whereIsWfa($value)
 * @method static Builder<static>|Attendance whereLatIn($value)
 * @method static Builder<static>|Attendance whereLatOut($value)
 * @method static Builder<static>|Attendance whereLateMinutes($value)
 * @method static Builder<static>|Attendance whereLatitude($value)
 * @method static Builder<static>|Attendance whereLeaveTypeId($value)
 * @method static Builder<static>|Attendance whereLongIn($value)
 * @method static Builder<static>|Attendance whereLongOut($value)
 * @method static Builder<static>|Attendance whereLongitude($value)
 * @method static Builder<static>|Attendance whereNote($value)
 * @method static Builder<static>|Attendance wherePhotoClockIn($value)
 * @method static Builder<static>|Attendance wherePhotoClockOut($value)
 * @method static Builder<static>|Attendance wherePhotoSelfieIn($value)
 * @method static Builder<static>|Attendance wherePhotoSelfieOut($value)
 * @method static Builder<static>|Attendance whereRiskFactors($value)
 * @method static Builder<static>|Attendance whereRiskLevel($value)
 * @method static Builder<static>|Attendance whereRiskScore($value)
 * @method static Builder<static>|Attendance whereShiftId($value)
 * @method static Builder<static>|Attendance whereStatus($value)
 * @method static Builder<static>|Attendance whereStatusWfa($value)
 * @method static Builder<static>|Attendance whereSyncedAt($value)
 * @method static Builder<static>|Attendance whereUpdatedAt($value)
 * @method static Builder<static>|Attendance whereVerificationMethod($value)
 * @method static Builder<static>|Attendance whereVerificationType($value)
 * @method static Builder<static>|Attendance whereWfaNote($value)
 * @method static Builder<static>|Attendance withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Attendance withoutTrashed()
 * @mixin \Eloquent
 * @mixin IdeHelperAttendance
 */
#[Fillable(['employee_id', 'shift_id', 'date', 'clock_in', 'clock_out', 'lat_in', 'long_in', 'lat_out', 'long_out', 'clock_in_is_mocked', 'clock_in_accuracy', 'clock_out_is_mocked', 'clock_out_accuracy', 'device_fingerprint', 'face_similarity_score', 'clock_out_face_similarity_score', 'status', 'is_wfa', 'status_wfa', 'approval_status', 'exception_type', 'exception_notes', 'approved_late_by', 'photo_selfie_in', 'photo_selfie_out', 'late_minutes', 'verification_method', 'clock_out_verification_method', 'wfa_note', 'leave_type_id', 'note'])]
class Attendance extends Model
{
    use Approvable, HasFactory, SoftDeletes;

    public const REQUEST_STATUSES = ['late', 'early', 'absent', 'missed_clock_in', 'missed_clock_out'];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected static function booted(): void
    {
        static::saving(function (self $attendance) {
            if ($attendance->date && $attendance->date->isFuture()) {
                throw new BusinessRuleException('Tanggal absensi tidak boleh di masa depan.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'lat_in' => 'decimal:8',
            'long_in' => 'decimal:8',
            'lat_out' => 'decimal:8',
            'long_out' => 'decimal:8',
            'clock_in_is_mocked' => 'boolean',
            'clock_in_accuracy' => 'decimal:2',
            'clock_out_is_mocked' => 'boolean',
            'clock_out_accuracy' => 'decimal:2',
            'face_similarity_score' => 'decimal:2',
            'clock_out_face_similarity_score' => 'decimal:2',
            'is_wfa' => 'boolean',
            'status_wfa' => ApprovalStatus::class,
            'approval_status' => ApprovalStatus::class,
            'verification_method' => VerificationMethod::class,
            'clock_out_verification_method' => VerificationMethod::class,
            'late_minutes' => 'integer',
            'status' => AttendanceStatus::class,
        ];
    }

    public function scopeManagedBy(Builder $query, User $admin): Builder
    {
        return $query->whereHas('employee.user', fn (Builder $q) => $q->managedBy($admin));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function overtime(): HasOne
    {
        return $this->hasOne(Overtime::class);
    }

    public function approvedLateBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_late_by');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Employee::class,
            'id',
            'id',
            'employee_id',
            'user_id',
        );
    }

    protected function latitudeIn(): Attribute
    {
        return Attribute::get(fn () => $this->lat_in);
    }

    protected function longitudeIn(): Attribute
    {
        return Attribute::get(fn () => $this->long_in);
    }

    protected function latitudeOut(): Attribute
    {
        return Attribute::get(fn () => $this->lat_out);
    }

    protected function longitudeOut(): Attribute
    {
        return Attribute::get(fn () => $this->long_out);
    }

    protected function latitude(): Attribute
    {
        return Attribute::get(fn () => $this->lat_in);
    }

    protected function longitude(): Attribute
    {
        return Attribute::get(fn () => $this->long_in);
    }

    protected function timeIn(): Attribute
    {
        return Attribute::get(fn () => $this->clock_in);
    }

    protected function timeOut(): Attribute
    {
        return Attribute::get(fn () => $this->clock_out);
    }

    protected function latLng(): Attribute
    {
        return Attribute::get(fn () => $this->lat_in && $this->long_in
            ? ['lat' => (float) $this->lat_in, 'lng' => (float) $this->long_in]
            : null);
    }

    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_selfie_in
            ? url('storage/'.$this->photo_selfie_in)
            : ($this->photo_selfie_out ? url('storage/'.$this->photo_selfie_out) : null));
    }

    protected function attachment(): Attribute
    {
        return Attribute::get(fn () => $this->photo_selfie_in ?? $this->photo_selfie_out);
    }

    public function needsReview(): bool
    {
        $latestApproval = $this->approvals()->latest()->first();
        $wfaPending = $this->is_wfa
            && (! $latestApproval
        || $latestApproval->status === ApprovalStatus::PENDING);

        return $wfaPending
            || $this->clock_in_is_mocked
            || $this->clock_out_is_mocked;
    }
}
