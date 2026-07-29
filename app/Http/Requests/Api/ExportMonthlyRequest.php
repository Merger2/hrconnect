<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ExportMonthlyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Periode ekspor wajib diisi.',
            'period.regex' => 'Format periode harus YYYY-MM (contoh: 2026-07).',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
        ];
    }
}
