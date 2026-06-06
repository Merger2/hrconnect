<?php

namespace App\Http\Requests\Api;

use App\Enums\EducationLevel;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('create', Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'employee_number' => ['required', 'string', 'unique:employees,employee_number'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'nik' => ['required', 'string', 'max:16', Rule::encryptedUnique(Employee::class, 'nik_hash')],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'parent_id' => ['nullable', 'integer', 'exists:employees,id'],
            'gender' => ['required', 'in:L,P'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'employment_type' => ['required', 'in:permanent,contract,probation,intern'],
            'birth_date' => ['required', 'date'],
            'join_date' => ['required', 'date'],
            'salary_type' => ['required', 'in:monthly,daily,hourly'],
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'education_level' => ['required', Rule::enum(EducationLevel::class)],
            'institution_name' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
        ];
    }
}
