<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('update', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+62|0)\d{9,12}$/'],
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'position_id' => ['sometimes', 'integer', 'exists:positions,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],
            'employment_type' => ['sometimes', 'in:permanent,contract,probation,intern'],
            'status' => ['sometimes', 'in:active,inactive,resigned'],
            'address_detail' => ['sometimes', 'nullable', 'string', 'max:500'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_number' => ['sometimes', 'nullable', 'regex:/^\d{8,18}$/'],
        ];
    }
}
