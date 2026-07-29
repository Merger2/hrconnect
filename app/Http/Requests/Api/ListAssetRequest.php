<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ListAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.max' => 'Pencarian maksimal 100 karakter.',
            'page.integer' => 'Halaman harus berupa angka bulat.',
            'page.min' => 'Halaman minimal 1.',
            'per_page.integer' => 'Jumlah per halaman harus berupa angka bulat.',
            'per_page.min' => 'Jumlah per halaman minimal 1.',
            'per_page.max' => 'Jumlah per halaman maksimal 100.',
        ];
    }
}
