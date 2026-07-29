<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'return_date' => ['nullable', 'date'],
            'condition' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_date.date' => 'Tanggal pengembalian tidak valid.',
            'condition.max' => 'Kondisi aset maksimal 255 karakter.',
        ];
    }
}
