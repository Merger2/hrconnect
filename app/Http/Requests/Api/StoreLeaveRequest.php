<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'day_type' => ['required', 'in:full_day,morning,afternoon'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Jenis cuti wajib dipilih.',
            'leave_type_id.exists' => 'Jenis cuti yang dipilih tidak valid.',
            'start_date.required' => 'Tanggal mulai cuti wajib diisi.',
            'start_date.date' => 'Tanggal mulai cuti tidak valid.',
            'end_date.required' => 'Tanggal selesai cuti wajib diisi.',
            'end_date.date' => 'Tanggal selesai cuti tidak valid.',
            'day_type.required' => 'Tipe hari cuti wajib dipilih.',
            'day_type.in' => 'Tipe hari cuti harus full_day, morning, atau afternoon.',
            'reason.required' => 'Alasan cuti wajib diisi.',
            'reason.min' => 'Alasan cuti minimal 10 karakter.',
            'reason.max' => 'Alasan cuti maksimal 1000 karakter.',
            'proof_file.mimes' => 'File bukti harus berupa JPG, JPEG, PNG, atau PDF.',
            'proof_file.max' => 'File bukti maksimal 5 MB.',
        ];
    }
}
