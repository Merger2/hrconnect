<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'is_mocked' => ['nullable', 'boolean'],
            'embedding' => ['nullable', 'array'],
            'pin' => ['nullable', 'string', 'digits:6'],
            'verification_method' => ['nullable', 'in:face_verified,pin_verified,manual'],
            'photo_selfie' => ['nullable', 'string'],
        ];
    }
}
