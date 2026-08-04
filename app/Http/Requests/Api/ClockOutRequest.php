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
            'embedding' => ['nullable', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-1.5,1.5'],
            'verification_method' => ['nullable', 'in:face_verified,pin_verified,manual'],
            'photo_selfie' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'accuracy.numeric' => 'Akurasi GPS harus berupa angka.',
            'accuracy.min' => 'Akurasi GPS tidak boleh negatif.',
            'is_mocked.boolean' => 'Status GPS palsu tidak valid.',
            'embedding.size' => 'Data wajah harus 128 dimensi.',
            'embedding.*.between' => 'Nilai embedding wajah tidak valid.',
            'verification_method.in' => 'Metode verifikasi tidak valid.',
        ];
    }
}
