<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SalaryType;
use App\Enums\TerminationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Pgvector\Laravel\HasNeighbors;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

/**
 * @mixin IdeHelperEmployee
 */
#[Fillable([
    'user_id', 'parent_id', 'company_id', 'branch_id', 'division_id', 'position_id', 'shift_id',
    'province_id', 'city_id', 'district_id', 'village_id', 'postal_code', 'address_detail',
    'employee_number', 'full_name', 'phone', 'bank_account_number', 'bank_name', 'bank_account_holder',
    'npwp', 'nik', 'marital_status', 'blood_type', 'gender', 'status',
    'birth_date', 'birth_place', 'join_date', 'employment_type', 'contract_start_date', 'contract_end_date',
    'resign_date', 'deceased_date', 'termination_type', 'termination_reason', 'phk_variant', 'photo',
    'education_level', 'institution_name', 'major', 'graduation_year', 'salary_type',
    'payslip_password', 'ptkp_status', 'nip', 'basic_salary',
    'golongan_ptkp_id',
    // --- LIFECYCLE FIELDS ---
    'probation_ends_at', 'contract_ends_at', 'resignation_submitted_at', 'resigned_at',
    'resignation_reason', 'exit_interview_completed_at', 'account_auto_disable_at',
    'employment_status', 'account_deletion_requested_at', 'account_deletion_reason',
    'account_deletion_reviewed_at', 'account_deletion_reviewed_by', 'account_deletion_review_notes',
    'manager_id',
    'bank_account_name', 'emergency_contact_name', 'emergency_contact_phone',
    'emergency_contact_relation', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan',
    'tarif_ter_id', 'kategori_ter_id', 'kode_karyawan',
    'provinsi_kode', 'kabupaten_kode', 'kecamatan_kode', 'kelurahan_kode',
])]
#[Hidden(['pin', 'nik', 'phone', 'npwp', 'bank_account_number', 'bank_account_name', 'emergency_contact_phone', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'payslip_password'])]
class Employee extends Model implements CipherSweetEncrypted
{
    use HasFactory, HasNeighbors, SoftDeletes, UsesCipherSweet;

    public const EMPLOYMENT_STATUS_ACTIVE = 'active';

    public const EMPLOYMENT_STATUS_INACTIVE = 'inactive';

    public const EMPLOYMENT_STATUS_RESIGNED = 'resigned';

    public const EMPLOYMENT_STATUS_TERMINATED = 'terminated';

    public const EMPLOYMENT_STATUS_DECEASED = 'deceased';

    public const EMPLOYMENT_STATUS_DELETION_REQUESTED = 'deletion_requested';

    public const EMPLOYMENT_STATUS_DELETED = 'deleted';

    public static function employmentStatuses(): array
    {
        return [
            self::EMPLOYMENT_STATUS_ACTIVE => __('Active'),
            self::EMPLOYMENT_STATUS_INACTIVE => __('Inactive'),
            self::EMPLOYMENT_STATUS_RESIGNED => __('Resigned'),
            self::EMPLOYMENT_STATUS_TERMINATED => __('Terminated'),
            self::EMPLOYMENT_STATUS_DECEASED => __('Deceased'),
            self::EMPLOYMENT_STATUS_DELETION_REQUESTED => __('Deletion Requested'),
            self::EMPLOYMENT_STATUS_DELETED => __('Deleted'),
        ];
    }

    public static function manuallyManagedEmploymentStatuses(): array
    {
        return [
            self::EMPLOYMENT_STATUS_DELETION_REQUESTED,
            self::EMPLOYMENT_STATUS_DELETED,
        ];
    }

    public function canTransitionEmploymentStatusTo(string $status): bool
    {
        return false; // Default: lifecycle-managed statuses cannot be changed via simple edit
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', EmployeeStatus::ACTIVE->value);
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'resign_date' => 'date',
            'deceased_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'gender' => Gender::class,
            'status' => EmployeeStatus::class,
            'marital_status' => MaritalStatus::class,
            'blood_type' => BloodType::class,
            'education_level' => EducationLevel::class,
            'salary_type' => SalaryType::class,
            'employment_type' => EmploymentType::class,
            'termination_type' => TerminationType::class,
            'graduation_year' => 'integer',
            'pin' => 'hashed',
            'payslip_password' => 'hashed',
            // --- LIFECYCLE CASTS ---
            'probation_ends_at' => 'date',
            'contract_ends_at' => 'date',
            'resignation_submitted_at' => 'datetime',
            'resigned_at' => 'datetime',
            'exit_interview_completed_at' => 'datetime',
            'account_auto_disable_at' => 'datetime',
            'account_deletion_requested_at' => 'datetime',
            'account_deletion_reviewed_at' => 'datetime',
            'employment_status' => EmploymentStatus::class,
        ];
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('phone')
            ->addBlindIndex('phone', new BlindIndex('phone_hash'))

            ->addOptionalTextField('nik')
            ->addBlindIndex('nik', new BlindIndex('nik_hash'))

            ->addOptionalTextField('npwp')
            ->addBlindIndex('npwp', new BlindIndex('npwp_hash'))

            ->addOptionalTextField('bank_account_number')
            ->addOptionalTextField('bank_account_holder')

            ->addOptionalTextField('bank_account_name')
            ->addOptionalTextField('emergency_contact_name')
            ->addOptionalTextField('emergency_contact_phone')
            ->addBlindIndex('emergency_contact_phone', new BlindIndex('emergency_phone_hash'))
            ->addOptionalTextField('emergency_contact_relation')
            ->addOptionalTextField('bpjs_kesehatan')
            ->addOptionalTextField('bpjs_ketenagakerjaan')
            ->addOptionalTextField('address_detail');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function golonganPtkp(): BelongsTo
    {
        return $this->belongsTo(GolonganPtkp::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function faceDescriptors(): HasMany
    {
        return $this->hasMany(FaceDescriptor::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'parent_id');
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function accountDeletionReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_deletion_reviewed_by');
    }

    public function hrChecklistCases(): HasManyThrough
    {
        return $this->hasManyThrough(
            HrChecklistCase::class,
            User::class,
            'id',       // Foreign key on users table (local key)
            'user_id',  // Foreign key on hr_checklist_cases table
            'user_id',  // Local key on employees table
            'id'        // Local key on users table
        );
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'parent_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'provinsi_kode', 'kode');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kabupaten_kode', 'kode');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kecamatan_kode', 'kode');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kelurahan_kode', 'kode');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function families(): HasMany
    {
        return $this->hasMany(FamilyDetail::class);
    }

    /**
     * B5 fix: scoped relation untuk anak (CHILD) saja.
     * Pakai ini di withCount untuk hindari counting parent/spouse/sibling.
     *
     * Usage:
     *   Employee::withCount('children')->get();
     *   $employee->children_count; // jumlah anak (untuk PTKP/TER)
     */
    public function children(): HasMany
    {
        return $this->hasMany(FamilyDetail::class)
            ->where('relationship', FamilyRelationship::CHILD);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(AssetHandover::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function reviewedVersions(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'reviewer_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function shiftSchedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function getHrApprover(): ?Employee
    {
        return User::role('admin')->first()?->employee;
    }

    public function hasClockedInToday(): bool
    {
        return $this->attendances()
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->exists();
    }

    public function getTodayActiveAttendance(bool $lockForUpdate = false): ?Attendance
    {
        $query = $this->attendances()
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function getDirectApprover(): ?Employee
    {
        // B-12: Only active managers can approve
        $manager = $this->manager;

        if ($manager && $manager->status !== EmployeeStatus::ACTIVE) {
            return null;
        }

        return $manager;
    }

    public function hasValidPayslipPassword(): bool
    {
        return $this->payslip_password !== null;
    }

    public function calculatePtkp(): float
    {
        $base = match ($this->marital_status) {
            MaritalStatus::MARRIED => (float) CompanySetting::get('ptkp_base_married', 58_500_000),
            default => (float) CompanySetting::get('ptkp_base_single', 54_000_000),
        };

        $perDependent = (float) CompanySetting::get('ptkp_per_dependent', 4_500_000);
        $maxDependents = (int) CompanySetting::get('ptkp_max_dependents', 3);

        $dependentsCount = $this->children()->count();

        return $base + (min($dependentsCount, $maxDependents) * $perDependent);
    }

    public function currentYearLeaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class)
            ->where('year', now()->year);
    }

    /**
     * Blade convenience accessor: display name (falls back to linked user name).
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['full_name'] ?? $this->user?->name;
    }

    /**
     * Blade convenience accessor: returns the real `nip` column when set,
     * falling back to `employee_number` so existing views keep working for
     * records that only carry a generated employee number.
     */
    public function getNipAttribute(): ?string
    {
        return $this->attributes['nip'] ?? ($this->attributes['employee_number'] ?? null);
    }

    /**
     * Blade convenience accessor: email from linked user.
     */
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }
}
