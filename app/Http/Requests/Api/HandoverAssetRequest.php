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

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan penerima wajib dipilih.',
            'employee_id.exists' => 'Karyawan tidak ditemukan.',
            'handover_date.required' => 'Tanggal serah terima wajib diisi.',
            'handover_date.date' => 'Format tanggal serah terima tidak valid.',
            'return_date.date' => 'Format tanggal pengembalian tidak valid.',
            'return_date.after_or_equal' => 'Tanggal pengembalian harus setelah atau sama dengan tanggal serah terima.',
            'condition.required' => 'Kondisi aset wajib diisi.',
            'condition.max' => 'Kondisi aset maksimal 255 karakter.',
            'category.in' => 'Kategori serah terima harus document, asset, data, access, atau responsibility.',
        ];
    }
}
