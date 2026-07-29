<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Periode penggajian wajib diisi.',
            'period.regex' => 'Format periode harus YYYY-MM (contoh: 2026-07).',
            'employee_ids.array' => 'Daftar karyawan harus berupa array.',
            'employee_ids.*.exists' => 'Karyawan dengan ID tersebut tidak ditemukan.',
        ];
    }
}
