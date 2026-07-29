<?php

namespace App\Http\Requests\Api;

use App\Enums\EmployeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'phone' => ['sometimes', 'string', 'max:20'],
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'division_id' => ['sometimes', 'integer', 'exists:divisions,id'],
            'position_id' => ['sometimes', 'integer', 'exists:positions,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:employees,id', function ($attribute, $value, $fail) {
                $employee = $this->route('employee');
                if ($value !== null && $employee && (int) $value === (int) $employee->id) {
                    $fail('Manager tidak boleh merujuk ke dirinya sendiri.');
                }
            }],
            'employment_type' => ['sometimes', 'in:permanent,contract,probation,intern'],
            'status' => ['sometimes', Rule::enum(EmployeeStatus::class)],
            'address_detail' => ['sometimes', 'nullable', 'string', 'max:500'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_number' => ['sometimes', 'nullable', 'regex:/^\d{8,18}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'company_id.exists' => 'Perusahaan tidak ditemukan.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'division_id.exists' => 'Divisi tidak ditemukan.',
            'position_id.exists' => 'Posisi tidak ditemukan.',
            'parent_id.exists' => 'Manager tidak ditemukan.',
            'employment_type.in' => 'Tipe karyawan harus permanent, contract, probation, atau intern.',
            'address_detail.max' => 'Alamat maksimal 500 karakter.',
            'bank_name.max' => 'Nama bank maksimal 100 karakter.',
            'bank_account_number.regex' => 'Nomor rekening harus terdiri dari 8-18 digit angka.',
        ];
    }
}
