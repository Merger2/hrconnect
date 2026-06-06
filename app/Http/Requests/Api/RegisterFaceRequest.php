<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-1.5,1.5'],
        ];
    }
}
