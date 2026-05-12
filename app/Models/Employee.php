<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
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
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

#[Fillable([
    'user_id', 'parent_id', 'company_id', 'branch_id', 'department_id', 'position_id', 'shift_id',
    'province_id', 'city_id', 'district_id', 'village_id', 'postal_code', 'address_detail',
    'employee_number', 'full_name', 'phone', 'bank_account_number', 'bank_name',
    'npwp', 'nik', 'marital_status', 'blood_type', 'gender', 'status',
    'birth_date', 'join_date', 'employment_type', 'contract_start_date', 'contract_end_date',
    'resign_date', 'deceased_date', 'termination_type', 'termination_reason', 'photo',
    'face_embedding', 'pin', 'education_level', 'institution_name', 'major', 'graduation_year', 'salary_type',
    'created_by', 'updated_by',
])]
#[Hidden(['face_embedding', 'pin'])]

class Employee extends Model implements CipherSweetEncrypted
{
    use HasFactory, SoftDeletes, UsesCipherSweet;

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
            'pin' => 'hashed',
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

            ->addOptionalTextField('bank_account_number');
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'parent_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'parent_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_id');
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
        return User::role('hr-manager')->first()?->employee;
    }

    public function hasClockedInToday(): bool
    {
        return $this->attendances()
            ->where('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->exists();
    }

    public function getTodayActiveAttendance(bool $lockForUpdate = false): ?Attendance
    {
        $query = $this->attendances()
            ->where('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function getDirectApprover(): ?Employee
    {
        return $this->manager;
    }

    public function calculatePtkp(): float
    {
        $base = match ($this->marital_status) {
            MaritalStatus::SINGLE, MaritalStatus::DIVORCED, MaritalStatus::WIDOWED => 54_000_000,
            MaritalStatus::MARRIED => 58_500_000,
            default => 54_000_000,
        };

        $dependentsCount = $this->families
            ->where('relationship', FamilyRelationship::CHILD)
            ->count();

        return $base + (min($dependentsCount, 3) * 4_500_000);
    }

    public function currentYearLeaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class)
            ->where('year', now()->year);
    }
}
