<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class EmployeeForm extends Form
{
    #[Locked]
    public ?Employee $employee = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $employee_number = '';

    public string $full_name = '';

    public string $nik = '';

    public string $phone = '';

    public string $gender = '';

    public string $marital_status = '';

    public string $blood_type = '';

    public string $birth_date = '';

    public string $join_date = '';

    public ?string $province_id = null;

    public ?string $city_id = null;

    public ?string $district_id = null;

    public ?string $village_id = null;

    public string $address_detail = '';

    public string $company_id = '';

    public string $branch_id = '';

    public string $department_id = '';

    public string $position_id = '';

    public ?string $parent_id = null;

    public string $employment_type = '';

    public string $salary_type = '';

    public string $education_level = '';

    public string $institution_name = '';

    public string $graduation_year = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', $this->employee ? Rule::unique('users', 'email')->ignore($this->employee->user_id) : 'unique:users,email'],
            'password' => [$this->employee ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => [$this->employee ? 'nullable' : 'required', 'string', 'min:8'],

            'employee_number' => ['required', 'string', $this->employee ? Rule::unique('employees', 'employee_number')->ignore($this->employee->id) : 'unique:employees,employee_number'],
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', $this->employee ? Rule::encryptedUnique(Employee::class, 'nik_hash')->ignore($this->employee->id) : Rule::encryptedUnique(Employee::class, 'nik_hash')],
            'phone' => ['required', 'regex:/^(\+62|0)\d{9,12}$/'],
            'gender' => ['required', 'in:L,P'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'birth_date' => ['required', 'date'],
            'join_date' => ['required', 'date'],

            'province_id' => ['nullable', 'exists:indonesia_provinces,code'],
            'city_id' => ['nullable', 'exists:indonesia_cities,code'],
            'district_id' => ['nullable', 'exists:indonesia_districts,code'],
            'village_id' => ['nullable', 'exists:indonesia_villages,code'],
            'address_detail' => ['nullable', 'string', 'max:500'],

            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'parent_id' => ['nullable', 'integer', 'exists:employees,id', function ($attribute, $value, $fail) {
                if ($value !== null && $this->employee && (int) $value === (int) $this->employee->id) {
                    $fail('Manager tidak boleh merujuk ke dirinya sendiri.');
                }
            }],
            'employment_type' => ['required', 'in:permanent,contract,probation,intern'],
            'salary_type' => ['required', 'in:monthly,daily,hourly'],

            'education_level' => ['required', Rule::enum(EducationLevel::class)],
            'institution_name' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
        ];
    }

    public function store(): Employee
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'password_changed_at' => null,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'nik' => $this->nik,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'blood_type' => $this->blood_type ?: null,
            'birth_date' => $this->birth_date,
            'join_date' => $this->join_date,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'village_id' => $this->village_id,
            'address_detail' => $this->address_detail,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'parent_id' => $this->parent_id,
            'employment_type' => $this->employment_type,
            'salary_type' => $this->salary_type,
            'education_level' => $this->education_level,
            'institution_name' => $this->institution_name,
            'graduation_year' => $this->graduation_year,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user->assignRole('employee');
        event(new Registered($user));

        return $employee;
    }

    public function update(): Employee
    {
        $this->validate();

        $employee = $this->employee;
        $user = $employee->user;

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($this->password) {
            $user->update(['password' => Hash::make($this->password)]);
        }

        $employee->update([
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'nik' => $this->nik,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'blood_type' => $this->blood_type ?: null,
            'birth_date' => $this->birth_date,
            'join_date' => $this->join_date,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'village_id' => $this->village_id,
            'address_detail' => $this->address_detail,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'parent_id' => $this->parent_id,
            'employment_type' => $this->employment_type,
            'salary_type' => $this->salary_type,
            'education_level' => $this->education_level,
            'institution_name' => $this->institution_name,
            'graduation_year' => $this->graduation_year,
        ]);

        return $employee;
    }
}
