<?php

namespace App\Models;

use App\Models\Concerns\HasRolePermissions;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Services\Security\FaceRecognitionService;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin IdeHelperUser
 */
#[Fillable(['name', 'email', 'password', 'password_changed_at', 'profile_photo_path', 'group', 'company_id', 'email_verified_at', 'manager_id', 'email_verification_code_hash', 'email_verification_code_expires_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'password_changed_at', 'email_verification_code_hash'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasProfilePhoto, HasRolePermissions, MustVerifyEmail, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    // ── Proxied Employee Status Constants ──
    public const EMPLOYMENT_STATUS_ACTIVE = 'active';

    public const EMPLOYMENT_STATUS_RESIGNED = 'resigned';

    public const EMPLOYMENT_STATUS_DELETION_REQUESTED = 'deletion_requested';

    public const EMPLOYMENT_STATUS_DELETED = 'deleted';

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'group' => 'string', // Added based on PasPapan
        ];
    }

    /**
     * Static groups for filtering
     */
    public static $groups = ['user', 'admin', 'superadmin']; // Added based on PasPapan

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // ═══════════════════════════════════════════════
    //  EMAIL VERIFICATION CODE
    // ═══════════════════════════════════════════════
    //
    // Selain link verifikasi Fortify (verification.verify), aplikasi
    // mendukung verifikasi email via kode 6 digit (POST /email/verify-code).
    // Hanya hash kode yang disimpan; kolom di tabel `users`:
    //   email_verification_code_hash / email_verification_code_expires_at.
    //

    /**
     * Generate a new 6-digit verification code and persist its hash.
     * Returns the plain-text code so the caller can include it in the
     * notification payload.
     */
    public function generateEmailVerificationCode(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        return $code;
    }

    /**
     * Check whether a plain-text code matches the stored hash and is not
     * expired. Expired codes are cleared so they cannot be reused.
     */
    public function hasValidEmailVerificationCode(string $code): bool
    {
        $hash = $this->email_verification_code_hash;
        $expiresAt = $this->email_verification_code_expires_at;

        if (! $hash || ! $expiresAt) {
            return false;
        }

        if ($expiresAt->isPast()) {
            $this->clearEmailVerificationCode();

            return false;
        }

        return Hash::check($code, $hash);
    }

    /**
     * Remove the stored verification code (after successful verification
     * or when it expires).
     */
    public function clearEmailVerificationCode(): void
    {
        $this->forceFill([
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
        ])->save();
    }

    /**
     * Send the email-verification notification. Overrides MustVerifyEmail to
     * generate a 6-digit code and dispatch QueuedVerifyEmail (instead of the
     * default VerifyEmail link). The code is embedded in the notification
     * payload, while only its hash is persisted.
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = $this->generateEmailVerificationCode();

        $this->notify(new QueuedVerifyEmail($code));
    }

    /**
     * Send the password-reset notification. Overrides the Laravel default so
     * the reset link is delivered via QueuedResetPassword (ShouldQueue).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function photoUrl(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }

    // Method preferredAdminRouteName (Added based on PasPapan)
    public function preferredAdminRouteName(): ?string
    {
        $candidates = [
            'viewAdminDashboard' => 'admin.dashboard',
            'manageAdminNotifications' => 'admin.notifications',
            'manageRbac' => 'admin.roles.permissions',
            'viewEmployees' => 'admin.employees',
            'manageCashAdvances' => 'admin.manage-kasbon',
            'viewAdminAppraisals' => 'admin.appraisals',
            'viewAdminSettings' => 'admin.settings',
        ];

        foreach ($candidates as $ability => $routeName) {
            if ($this->can($ability)) {
                return $routeName;
            }
        }

        return match ($this->group) {
            'superadmin' => 'admin.dashboard',
            'admin' => 'admin.dashboard',
            default => null,
        };
    }

    public function preferredHomeRouteName(): string
    {
        return $this->preferredAdminRouteName() ?? 'home';
    }

    public function preferredHomeUrl(): string
    {
        return route($this->preferredHomeRouteName());
    }

    public function scopeManagedBy(Builder $query, User $admin): Builder
    {
        if ($admin->hasGlobalAdminScope()) {
            return $query;
        }

        $employee = $admin->employee;

        if (! $employee) {
            return $query->whereRaw('1 = 0');
        }

        $employeeTable = (new Employee)->getTable();
        $userTable = (new User)->getTable();
        $key = (new Employee)->getKeyName();

        $managedUserIds = DB::select("
            WITH RECURSIVE sub_tree AS (
                SELECT {$key}, user_id FROM {$employeeTable} WHERE parent_id = ?
                UNION ALL
                SELECT e.{$key}, e.user_id FROM {$employeeTable} e
                INNER JOIN sub_tree st ON e.parent_id = st.{$key}
            )
            SELECT DISTINCT user_id FROM sub_tree WHERE user_id IS NOT NULL
        ", [$employee->{$key}]);

        $ids = array_column($managedUserIds, 'user_id');

        if (empty($ids)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn("{$userTable}.id", $ids);
    }

    // Method canAccessAdminPanel (Added based on PasPapan)
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin; // Ketergantungan pada accessor isAdmin
    }

    // Method canViewAdminDashboard (Added based on PasPapan)
    public function canViewAdminDashboard(): bool
    {
        return $this->isAdmin; // Ketergantungan pada accessor isAdmin
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function division(): HasOneThrough
    {
        return $this->hasOneThrough(
            Division::class,
            Employee::class,
            'user_id',
            'id',
            'id',
            'division_id',
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getNipAttribute(): ?string
    {
        return $this->employee?->nip;
    }

    public function roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    public function cashAdvances(): HasMany
    {
        return $this->hasMany(CashAdvance::class, 'user_id');
    }

    public function hasValidPayslipPassword(): bool
    {
        return $this->employee?->hasValidPayslipPassword() ?? false;
    }

    public function hasFaceRegistered(): bool
    {
        if (! $this->employee) {
            return false;
        }

        return app(FaceRecognitionService::class)
            ->hasFaceEnrolled($this->employee);
    }

    // Accessors matching PasPapan
    final public function getIsUserAttribute(): bool
    {
        return $this->group === 'user';
    }

    final public function getIsAdminAttribute(): bool
    {
        return $this->group === 'admin' || $this->isSuperadmin;
    }

    final public function getIsSuperadminAttribute(): bool
    {
        return $this->group === 'superadmin';
    }

    final public function getIsNotAdminAttribute(): bool
    {
        return ! $this->isAdmin;
    }

    final public function getIsDemoAttribute(): bool
    {
        return in_array($this->email, [
            'admin123@paspapan.com', // Contoh email demo dari PasPapan
            'user123@paspapan.com',
        ]);
    }

    public function canAuthenticate(): bool
    {
        // Soft-deleted users (users.softDeletes) tidak boleh login. Eloquent
        // global scope sudah mengecualikan trashed dari query provider, tapi
        // guard ini menutup jalur login manual (AuthenticateLoginAttempt,
        // E2eLoginController) yang memuat user langsung.
        return ! $this->trashed();
    }

    // ═══════════════════════════════════════════════
    //  PROXY METHODS & ACCESSORS — Employee data
    // ═══════════════════════════════════════════════
    //
    // Kolom employment_status, phone, gender, address, birth_date, birth_place,
    // hourly_rate, basic_salary, division_id, position_id, provinsi/kabupaten/
    // kecamatan/kelurahan, account_deletion_*, dll SEMUA ada di tabel `employees`,
    // bukan `users`. Accessor/method di bawah ini nge-proxy lewat relasi
    // $this->employee (HasOne) agar blade view tetap bisa panggil $user->xxx
    // tanpa error.
    //

    // ── Status display methods ──

    public function employmentStatusTone(): string
    {
        return match ($this->employment_status) {
            self::EMPLOYMENT_STATUS_ACTIVE => 'success',
            self::EMPLOYMENT_STATUS_RESIGNED, self::EMPLOYMENT_STATUS_DELETED => 'neutral',
            self::EMPLOYMENT_STATUS_DELETION_REQUESTED => 'warning',
            default => 'neutral',
        };
    }

    public function employmentStatusLabel(): string
    {
        return Employee::employmentStatuses()[$this->employment_status] ?? __('Unknown');
    }

    public function hasPendingAccountDeletionRequest(): bool
    {
        return $this->employment_status === self::EMPLOYMENT_STATUS_DELETION_REQUESTED;
    }

    public function approveAccountDeletion(User $reviewer, ?string $notes = null): void
    {
        if (! $this->employee) {
            return;
        }

        $this->employee->update([
            'employment_status' => self::EMPLOYMENT_STATUS_DELETED,
            'account_deletion_reviewed_at' => now(),
            'account_deletion_reviewed_by' => $reviewer->id,
            'account_deletion_review_notes' => $notes,
        ]);
    }

    public function rejectAccountDeletion(User $reviewer, ?string $notes = null): void
    {
        if (! $this->employee) {
            return;
        }

        $this->employee->update([
            'employment_status' => self::EMPLOYMENT_STATUS_ACTIVE,
            'account_deletion_reviewed_at' => now(),
            'account_deletion_reviewed_by' => $reviewer->id,
            'account_deletion_review_notes' => $notes,
        ]);
    }

    // ── Scalar proxy accessors (kolom di employees) ──

    public function getEmploymentStatusAttribute(): ?string
    {
        return $this->employee?->employment_status?->value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->employee?->phone;
    }

    public function getGenderAttribute(): ?string
    {
        return match ($this->employee?->gender?->value) {
            'L' => 'male',
            'P' => 'female',
            default => null,
        };
    }

    public function getAddressAttribute(): ?string
    {
        return $this->employee?->address_detail;
    }

    public function getBirthPlaceAttribute(): ?string
    {
        return $this->employee?->birth_place;
    }

    public function getBirthDateAttribute(): mixed
    {
        return $this->employee?->birth_date;
    }

    public function getHourlyRateAttribute(): mixed
    {
        return $this->employee?->hourly_rate;
    }

    public function getProvinsiKodeAttribute(): ?string
    {
        return $this->employee?->provinsi_kode;
    }

    public function getKabupatenKodeAttribute(): ?string
    {
        return $this->employee?->kabupaten_kode;
    }

    public function getKecamatanKodeAttribute(): ?string
    {
        return $this->employee?->kecamatan_kode;
    }

    public function getKelurahanKodeAttribute(): ?string
    {
        return $this->employee?->kelurahan_kode;
    }

    // ── Lifecycle proxy accessors ──

    public function getAccountDeletionRequestedAtAttribute(): mixed
    {
        return $this->employee?->account_deletion_requested_at;
    }

    public function getAccountDeletionReasonAttribute(): ?string
    {
        return $this->employee?->account_deletion_reason;
    }

    public function getAccountDeletionReviewNotesAttribute(): ?string
    {
        return $this->employee?->account_deletion_review_notes;
    }

    // ── Relation proxy accessors (return Model instances from Employee relations) ──

    public function getDivisionIdAttribute(): ?int
    {
        return $this->employee?->division_id;
    }

    public function getDivisionAttribute()
    {
        return $this->employee?->division;
    }

    public function getJobTitleAttribute()
    {
        return $this->employee?->position?->jobTitle;
    }

    public function getEducationAttribute()
    {
        return $this->employee?->education_level;
    }

    public function getDirectManagerAttribute()
    {
        return $this->employee?->directManager?->user;
    }

    public function getProvinsiAttribute()
    {
        return $this->employee?->province;
    }

    public function getKabupatenAttribute()
    {
        return $this->employee?->city;
    }

    public function getKecamatanAttribute()
    {
        return $this->employee?->district;
    }

    public function getKelurahanAttribute()
    {
        return $this->employee?->village;
    }

    public function getReviewedAccountDeletionByAttribute()
    {
        return $this->employee?->accountDeletionReviewer;
    }
}
