<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PendingApprovalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:leave,overtime,reimbursement,wfa'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'all' => ['nullable', 'in:1,true'],
            'scope' => ['nullable', 'in:own,team'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Tipe approval harus leave, overtime, reimbursement, atau wfa.',
            'page.integer' => 'Halaman harus berupa angka bulat.',
            'page.min' => 'Halaman minimal 1.',
            'per_page.integer' => 'Jumlah per halaman harus berupa angka bulat.',
            'per_page.min' => 'Jumlah per halaman minimal 1.',
            'per_page.max' => 'Jumlah per halaman maksimal 100.',
            'all.in' => 'Nilai all harus 1 atau true.',
            'scope.in' => 'Scope harus own atau team.',
        ];
    }
}
