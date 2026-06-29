<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenor_months' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }
}
