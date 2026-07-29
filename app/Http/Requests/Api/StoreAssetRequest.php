<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:assets,serial_number'],
            'code' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama aset wajib diisi.',
            'name.max' => 'Nama aset maksimal 255 karakter.',
            'serial_number.required' => 'Nomor seri aset wajib diisi.',
            'serial_number.max' => 'Nomor seri aset maksimal 255 karakter.',
            'serial_number.unique' => 'Nomor seri aset sudah terdaftar.',
            'code.max' => 'Kode aset maksimal 255 karakter.',
            'category.max' => 'Kategori aset maksimal 255 karakter.',
            'company_id.exists' => 'Perusahaan tidak valid.',
            'company_id.integer' => 'Perusahaan harus berupa angka.',
        ];
    }
}
