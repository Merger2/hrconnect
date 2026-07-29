<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceOfflineAttendanceSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.event_type' => ['required', 'string', 'in:clock_in,clock_out'],
            'items.*.occurred_at' => ['required', 'date'],
            'items.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'items.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'items.*.accuracy' => ['nullable', 'numeric', 'min:0'],
            'items.*.device_id' => ['nullable', 'string', 'max:120'],
            'items.*.photo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Data absensi wajib diisi.',
            'items.array' => 'Data absensi harus berupa array.',
            'items.min' => 'Minimal 1 data absensi.',
            'items.max' => 'Maksimal 50 data absensi dalam satu kiriman.',
            'items.*.event_type.required' => 'Tipe event absensi wajib diisi.',
            'items.*.event_type.in' => 'Tipe event harus clock_in atau clock_out.',
            'items.*.occurred_at.required' => 'Waktu kejadian absensi wajib diisi.',
            'items.*.occurred_at.date' => 'Format waktu kejadian tidak valid.',
            'items.*.latitude.numeric' => 'Latitude harus berupa angka.',
            'items.*.latitude.between' => 'Latitude harus antara -90 dan 90.',
            'items.*.longitude.numeric' => 'Longitude harus berupa angka.',
            'items.*.longitude.between' => 'Longitude harus antara -180 dan 180.',
            'items.*.accuracy.numeric' => 'Akurasi GPS harus berupa angka.',
            'items.*.accuracy.min' => 'Akurasi GPS tidak boleh negatif.',
            'items.*.device_id.max' => 'ID perangkat maksimal 120 karakter.',
        ];
    }
}
