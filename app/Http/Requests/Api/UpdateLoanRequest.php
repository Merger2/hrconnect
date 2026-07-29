<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:1'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenor_months' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.numeric' => 'Jumlah pinjaman harus berupa angka.',
            'amount.min' => 'Jumlah pinjaman minimal 1.',
            'interest_rate.numeric' => 'Suku bunga harus berupa angka.',
            'interest_rate.min' => 'Suku bunga tidak boleh negatif.',
            'interest_rate.max' => 'Suku bunga maksimal 100%.',
            'tenor_months.integer' => 'Tenor pinjaman harus berupa angka bulat.',
            'tenor_months.min' => 'Tenor pinjaman minimal 1 bulan.',
            'tenor_months.max' => 'Tenor pinjaman maksimal 120 bulan.',
        ];
    }
}
