<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class HandoverAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'handover_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:handover_date'],
            'condition' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'in:document,asset,data,access,responsibility'],
        ];
    }
}
