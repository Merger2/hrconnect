<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Clock-in request with GPS location, face verification, and WFA support.
 *
 * - WFO (is_wfa=false): latitude, longitude required; GPS validated against branch geofence.
 * - WFA (is_wfa=true): note required (min 20 chars); GPS skipped.
 * - Verification: face_embedding (128D), pin (6-digit), or manual fallback.
 * - Anti-fake GPS: is_mocked flag + accuracy meter.
 */
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
            'gps_variance' => ['nullable', 'numeric', 'min:0'],
            'is_mocked' => ['nullable', 'boolean'],
            'embedding' => ['nullable', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-3,3'],
            'descriptor' => ['nullable', 'array', 'size:129'],
            'descriptor.*' => ['numeric', 'between:-3,3'],
            'pin' => ['nullable', 'string', 'digits:6'],
            'wfa_note' => ['required_if:is_wfa,true', 'string', 'min:20'],
            'photo_selfie' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['descriptor']) && ! isset($data['embedding'])) {
            $descriptor = $data['descriptor'];
            $data['embedding'] = array_slice($descriptor, 1);
            $data['_descriptor_version'] = $descriptor[0];
            $this->merge($data);
        }
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
