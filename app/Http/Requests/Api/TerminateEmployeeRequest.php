<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TerminateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('manage_employees') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:resign,dismissed,deceased,contract_end'],
            'reason' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date'],
        ];
    }
}
