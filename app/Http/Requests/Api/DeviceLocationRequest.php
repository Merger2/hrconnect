<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'timestamp' => ['nullable', 'date'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'battery_level' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
