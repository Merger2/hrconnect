<?php

namespace App\Livewire\Forms;

use App\Actions\Hr\SyncUserRoles;
use App\Enums\EducationLevel;
use App\Enums\MaritalStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\ManagerHierarchyGuard;
use App\Support\SecureUploadPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public $name = '';

    public $nip = '';

    public $email = '';

    public $phone = '';

    public $password = null;

    public $password_confirmation = null;

    public $gender = null;

    public $marital_status = 'single';

    public $address = '';

    public $provinsi_kode = null;

    public $kabupaten_kode = null;

    public $kecamatan_kode = null;

    public $kelurahan_kode = null;

    public $group = 'user';

    public $birth_date = null;

    public $birth_place = '';

    public $division_id = null;

    public $position_id = null;

    public $manager_id = null;

    public $photo = null;

    public $basic_salary = 0;

    public $employment_status = Employee::EMPLOYMENT_STATUS_ACTIVE;

    public $join_date = null;

    public $employment_type = 'permanent';

    public $education_level = null;

    public $institution_name = '';

    public $graduation_year = null;

    public array $role_ids = [];

    public ?string $role_id = null;

    public ?string $original_role_id = null;

    protected array $original_role_ids = [];

    public function rules()
    {
        $requiredOrNullable = $this->group === 'user' ? 'required' : 'nullable';
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'nip' => [$requiredOrNullable, 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user),
            ],
            'phone' => [$requiredOrNullable, 'string', 'min:5', 'max:255'],
            // Password wajib saat create; opsional saat update (hanya diubah
            // bila diisi). Kekuatan mengikuti Password::defaults() — prod:
            // min 12 + huruf/angka/simbol + uncompromised (AppServiceProvider).
            // 'confirmed' → wajib cocok dgn form.password_confirmation (re-verification
            // agar admin tidak salah ketik password karyawan dua kali berbeda).
            // Edit: nullable (kosong = tidak ganti), konfirmasi hanya dicek saat diisi.
            'password' => [$this->user ? 'nullable' : 'required', 'string', Password::defaults(), 'max:255', 'confirmed'],
            'gender' => [$requiredOrNullable, 'in:male,female'],
            'marital_status' => ['nullable', 'string', Rule::in(array_column(MaritalStatus::cases(), 'value'))],
            'address' => [$requiredOrNullable, 'string', 'max:255'],
            // Wilayah (provinsi→kelurahan) OPSIONAL: konsisten dengan kolom DB
            // nullable, StoreEmployeeRequest (API) dan UpdateUserProfileInformation
            // (Fortify) yang semuanya nullable. Sebelumnya dipaksa required untuk
            // group 'user' padahal tabel wilayah bisa kosong (tanpa seeder) dan
            // data karyawan lama tidak punya alamat wilayah → form edit/create
            // user-group TIDAK PERNAH bisa disimpan di UI (P1).
            'provinsi_kode' => ['nullable', 'string', 'max:13'],
            'kabupaten_kode' => ['nullable', 'string', 'max:13'],
            'kecamatan_kode' => ['nullable', 'string', 'max:13'],
            'kelurahan_kode' => ['nullable', 'string', 'max:13'],
            'group' => ['nullable', 'string', 'max:255', Rule::in(User::$groups)],
            // employees.birth_date NOT NULL di DB (migration awal) — 'nullable'
            // di sini membuat INSERT gagal QueryException 500 saat admin tidak
            // mengisi field. Samakan dengan StoreEmployeeRequest (API).
            'birth_date' => [$requiredOrNullable, 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_id' => [
                'nullable',
                'string',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('group', 'user')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->user !== null && $value === $this->user->id) {
                        $fail(__('An employee cannot be their own direct manager.'));
                    }
                },
            ],
            'photo' => ['nullable', ...app(SecureUploadPolicy::class)->rules('image')],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'employment_status' => ['required', 'string', Rule::in(array_keys(Employee::employmentStatuses()))],
            'join_date' => [$requiredOrNullable, 'date'],
            'employment_type' => [$requiredOrNullable, 'string', Rule::in(['permanent', 'contract', 'intern'])],
            'education_level' => [$requiredOrNullable, 'string', Rule::in(array_column(EducationLevel::cases(), 'value'))],
            'institution_name' => [$requiredOrNullable, 'string', 'max:255'],
            'graduation_year' => [$requiredOrNullable, 'integer', 'min:1970', 'max:'.now()->year],
            'role_id' => ['nullable', 'string', 'exists:roles,id'],
            'role_ids' => ['array', 'max:1'],
            'role_ids.*' => ['string', 'exists:roles,id'],
        ];

        return $rules;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->nip = $user->nip;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->password = null;
        $this->password_confirmation = null;
        $this->gender = $user->gender;
        $this->marital_status = $user->employee?->marital_status->value ?? 'single';
        $this->address = $user->address;
        $this->provinsi_kode = $user->provinsi_kode;
        $this->kabupaten_kode = $user->kabupaten_kode;
        $this->kecamatan_kode = $user->kecamatan_kode;
        $this->kelurahan_kode = $user->kelurahan_kode;
        $this->group = $user->group;
        $this->birth_date = $user->birth_date
            ? Carbon::parse($user->birth_date)->format('Y-m-d')
            : null;
        $this->birth_place = $user->birth_place;
        $this->division_id = $user->division_id;
        $this->position_id = $user->employee?->position_id;
        $this->manager_id = $user->manager_id;
        $this->basic_salary = $user->basic_salary;
        $this->employment_status = $user->employment_status ?: Employee::EMPLOYMENT_STATUS_ACTIVE;
        $this->join_date = $user->employee?->join_date?->format('Y-m-d');
        $this->employment_type = $user->employee?->employment_type->value ?? 'permanent';
        $this->education_level = $user->employee?->education_level?->value;
        $this->institution_name = $user->employee->institution_name ?? '';
        $this->graduation_year = $user->employee?->graduation_year;
        $this->role_ids = $user->roles()
            ->orderByDesc('roles.is_super_admin')
            ->orderBy('roles.name')
            ->limit(1)
            ->pluck('roles.id')
            ->all();
        $this->role_id = $this->role_ids[0] ?? null;
        $this->original_role_id = $this->role_id;
        $this->original_role_ids = $this->role_ids;

        return $this;
    }

    public function store()
    {
        $this->authorizeMutation();
        $this->authorizeEmploymentStatusChange(null);
        $this->original_role_ids = [];
        $this->normalizeSingleRoleSelection();
        $this->validate();
        $this->ensureManagerDoesNotCreateCycle();
        $this->sanitize();

        $companyId = $this->resolveCompanyId();

        $user = DB::transaction(function () use ($companyId) {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'group' => $this->group,
                'manager_id' => $this->manager_id,
                'company_id' => $companyId,
            ]);

            $employeeData = $this->employeePayload();
            $employeeData['user_id'] = $user->id;
            $employeeData['company_id'] = $companyId;
            $employeeData['branch_id'] = $this->getDefaultBranchId($employeeData['company_id']);
            $employeeData['employee_number'] = $this->generateEmployeeNumber();
            $employeeData['salary_type'] = 'monthly';
            // Form tidak punya field nik (hanya nip); kolom employees.nik NOT NULL tanpa default.
            $employeeData['nik'] = $this->nip ?: 'NIK-'.Str::random(12);
            $employeeData['manager_id'] = $this->manager_id
                ? User::find($this->manager_id)?->employee?->id
                : null;

            Employee::create($employeeData);

            return $user;
        });

        $this->syncRoles($user);
        if (isset($this->photo)) {
            $user->updateProfilePhoto($this->photo);
        }
        $this->reset();
    }

    public function update()
    {
        $this->authorizeMutation();
        $this->authorizeEmploymentStatusChange($this->user);
        $this->normalizeSingleRoleSelection();

        if ($this->user !== null && auth()->id() === $this->user->id) {
            if ($this->group !== $this->user->group) {
                throw new AuthorizationException(__('You cannot change your own account group.'));
            }

            // Normalize both sides to strings: normalizeSingleRoleSelection() casts
            // role ids to string, while pluck() returns native types (int on pgsql,
            // string on sqlite). Strict === comparison would false-fail otherwise.
            $requestedRoleIds = array_map('strval', array_values(array_unique($this->role_ids)));
            $originalRoleIds = array_map('strval', $this->user
                ->roles()
                ->pluck('roles.id')
                ->all());
            sort($requestedRoleIds);
            sort($originalRoleIds);

            if ($requestedRoleIds !== $originalRoleIds) {
                throw new AuthorizationException(__('You cannot change your own role assignment.'));
            }
        }

        // Demo User Protection: Cannot update password of Demo User
        if ($this->user->is_demo && $this->password) {
            throw ValidationException::withMessages([
                'form.password' => __('Demo user password cannot be changed.'),
            ]);
        }

        // Demo User Protection: Protect default employee account
        if (auth()->user()?->is_demo && $this->user->email === 'user123@paspapan.com') {
            throw ValidationException::withMessages([
                'form.email' => __('Default user profile cannot be modified in demo mode.'),
            ]);
        }

        $this->validate();
        $this->ensureManagerDoesNotCreateCycle();
        $newPassword = filled($this->password) ? (string) $this->password : null;
        $this->sanitize();

        $companyId = $this->resolveCompanyId();

        DB::transaction(function () use ($companyId) {
            $this->user->update([
                'name' => $this->name,
                'email' => $this->email,
                'group' => $this->group,
                'manager_id' => $this->manager_id,
            ]);

            $employeePayload = $this->employeePayload();
            $employeePayload['manager_id'] = $this->manager_id
                ? User::find($this->manager_id)?->employee?->id
                : null;

            if ($this->user->employee) {
                $this->user->employee->update($employeePayload);
            } elseif ($this->group === 'user') {
                $employeePayload['user_id'] = $this->user->id;
                $employeePayload['company_id'] = $companyId;
                $employeePayload['branch_id'] = $this->getDefaultBranchId($employeePayload['company_id']);
                $employeePayload['employee_number'] = $this->generateEmployeeNumber();
                $employeePayload['salary_type'] = 'monthly';
                $this->user->employee()->create($employeePayload);
            }
        });

        $this->syncRoles($this->user);

        if ($newPassword !== null) {
            $this->user->forceFill([
                'password' => Hash::make($newPassword),
            ])->save();
        }

        if (isset($this->photo)) {
            $this->user->updateProfilePhoto($this->photo);
        }
        $this->reset();
    }

    protected function sanitize()
    {
        $this->division_id = $this->division_id ?: null;
        $this->position_id = $this->position_id ?: null;
        $this->manager_id = $this->manager_id ?: null;
        $this->employment_status = $this->employment_status ?: Employee::EMPLOYMENT_STATUS_ACTIVE;
        $this->marital_status = $this->marital_status ?: 'single';
        $this->provinsi_kode = $this->provinsi_kode ?: null;
        $this->kabupaten_kode = $this->kabupaten_kode ?: null;
        $this->kecamatan_kode = $this->kecamatan_kode ?: null;
        $this->kelurahan_kode = $this->kelurahan_kode ?: null;
        $this->birth_date = $this->birth_date ?: null;
        $this->join_date = $this->join_date ?: null;
        $this->graduation_year = $this->graduation_year ?: null;
        $this->address = trim((string) $this->address);
        $this->birth_place = trim((string) $this->birth_place);
        $this->institution_name = trim((string) $this->institution_name);
    }

    public function deleteProfilePhoto(): void
    {
        $this->authorizeMutation();

        if (auth()->user()?->is_demo && $this->user->email === 'user123@paspapan.com') {
            throw new AuthorizationException(__('Default user profile cannot be modified in demo mode.'));
        }

        $this->user->deleteProfilePhoto();
    }

    public function delete()
    {
        $this->authorizeMutation();

        if (auth()->user()?->is_demo && $this->user->email === 'user123@paspapan.com') {
            throw new AuthorizationException(__('Default user profile cannot be deleted in demo mode.'));
        }

        $this->user->delete();
        $this->deleteProfilePhoto();
        $this->reset();
    }

    private function authorizeMutation(): void
    {
        Gate::authorize('manageUserRecord', [$this->user, $this->group]);
    }

    private function authorizeEmploymentStatusChange(?User $subject): void
    {
        $currentStatus = $subject?->employment_status ?: Employee::EMPLOYMENT_STATUS_ACTIVE;
        $requestedStatus = $this->employment_status ?: Employee::EMPLOYMENT_STATUS_ACTIVE;

        if ($subject !== null && in_array($currentStatus, [
            Employee::EMPLOYMENT_STATUS_DELETION_REQUESTED,
            Employee::EMPLOYMENT_STATUS_DELETED,
        ], true) && $requestedStatus !== $currentStatus) {
            throw ValidationException::withMessages([
                'form.employment_status' => __('Use the account deletion review action to resolve deletion requests.'),
            ]);
        }

        if ($requestedStatus !== $currentStatus && ! $subject?->employee?->canTransitionEmploymentStatusTo($requestedStatus) && ! in_array($requestedStatus, Employee::manuallyManagedEmploymentStatuses(), true)) {
            throw ValidationException::withMessages([
                'form.employment_status' => __('This employee status must be managed through the account lifecycle flow.'),
            ]);
        }

        if ($requestedStatus !== $currentStatus && ! Gate::allows('manageEmployeeStatuses')) {
            throw new AuthorizationException(__('You do not have permission to manage employee status.'));
        }
    }

    private function employeePayload(): array
    {
        return [
            'full_name' => $this->name,
            'nip' => $this->nip,
            'phone' => $this->phone,
            'gender' => $this->gender === 'male' ? 'L' : 'P',
            'marital_status' => $this->marital_status,
            'address_detail' => $this->address,
            'provinsi_kode' => $this->provinsi_kode,
            'kabupaten_kode' => $this->kabupaten_kode,
            'kecamatan_kode' => $this->kecamatan_kode,
            'kelurahan_kode' => $this->kelurahan_kode,
            'birth_date' => $this->birth_date,
            'birth_place' => $this->birth_place,
            'division_id' => $this->division_id,
            'position_id' => $this->position_id,
            'basic_salary' => $this->basic_salary ?: 0,
            'employment_status' => $this->employment_status,
            'join_date' => $this->join_date,
            'employment_type' => $this->employment_type,
            'education_level' => $this->education_level,
            'institution_name' => $this->institution_name,
            'graduation_year' => $this->graduation_year,
        ];
    }

    /**
     * Resolve company_id untuk karyawan baru/ter-update. Null-safe: super admin
     * bootstrap (SuperAdminSeeder) tidak terikat company — company_id NULL, dan
     * sebelumnya dioper langsung ke getDefaultBranchId(int) → TypeError 500 di
     * setiap submit form tambah karyawan oleh super admin. PRD: single-company
     * (non-goal multi-tenant) → fallback ke satu-satunya company terdaftar.
     */
    private function resolveCompanyId(): int
    {
        $companyId = auth()->user()->company_id
            ?? Company::query()->orderBy('id')->value('id');

        if ($companyId === null) {
            // Fail loud via validasi — bukan 500, bukan data yatim.
            throw ValidationException::withMessages([
                'form.email' => __('No company is configured. Create the company first.'),
            ]);
        }

        return (int) $companyId;
    }

    private function getDefaultBranchId(int $companyId): int
    {
        // Main branch diprioritaskan; tanpa fallback fake id=1 — branch salah
        // membuat absensi/payroll karyawan nyasar company lain (lebih buruk
        // daripada gagal simpan).
        $branchId = Branch::where('company_id', $companyId)
            ->orderByDesc('is_main')
            ->value('id');

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'form.email' => __('No branch is configured for this company. Create a main branch first.'),
            ]);
        }

        return (int) $branchId;
    }

    private function generateEmployeeNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "EMP-{$year}-";

        $latest = Employee::where('employee_number', 'like', "{$prefix}%")
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->employee_number, strlen($prefix));
            $seq = str_pad((string) ($lastSeq + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return "{$prefix}{$seq}";
    }

    private function ensureManagerDoesNotCreateCycle(): void
    {
        if ($this->user === null || blank($this->manager_id)) {
            return;
        }

        $manager = $this->manager_id ? User::find($this->manager_id) : null;

        if ($manager && app(ManagerHierarchyGuard::class)->wouldCreateCycle($this->user, $manager)) {
            throw ValidationException::withMessages([
                'form.manager_id' => __('This direct manager would create a circular reporting line.'),
            ]);
        }
    }

    private function normalizeSingleRoleSelection(): void
    {
        $selectedRoleIds = array_values(array_unique(array_filter($this->role_ids)));
        $firstRoleId = $selectedRoleIds[0] ?? null;
        $roleIdChanged = $this->role_id !== $this->original_role_id;
        $selectedRoleId = $roleIdChanged
            ? $this->role_id
            : ($firstRoleId ?: $this->role_id);

        $this->role_id = filled($selectedRoleId) ? (string) $selectedRoleId : null;
        $this->role_ids = $this->role_id ? [$this->role_id] : [];
    }

    private function syncRoles(User $subject): void
    {
        $result = app(SyncUserRoles::class)->handle(
            $subject,
            auth()->user(),
            $this->role_ids,
            $this->original_role_ids,
        );

        $this->role_ids = $result['role_ids'];
        $this->original_role_ids = $result['original_role_ids'];
        $this->group = $result['group'];
    }
}
