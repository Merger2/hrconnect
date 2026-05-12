<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_wfa' => ['boolean'],
            'latitude' => ['required_if:is_wfa,false', 'numeric'],
            'longitude' => ['required_if:is_wfa,false', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'is_mocked' => ['nullable', 'boolean'],
            'face_embedding' => ['nullable', 'array'],
            'pin' => ['nullable', 'string', 'digits:6'],
            'wfa_note' => ['required_if:is_wfa,true', 'string', 'min:20'],
            'photo_selfie' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required_if' => 'Koordinat latitude wajib diisi untuk absen WFO.',
            'longitude.required_if' => 'Koordinat longitude wajib diisi untuk absen WFO.',
            'wfa_note.required_if' => 'Catatan pekerjaan wajib diisi untuk absen WFA.',
            'wfa_note.min' => 'Catatan WFA minimal 20 karakter.',
            'pin.digits' => 'PIN harus terdiri dari 6 digit angka.',
        ];
    }
}
