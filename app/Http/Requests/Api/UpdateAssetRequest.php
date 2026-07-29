<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', 'unique:assets,serial_number,'.$this->route('asset')?->id],
            'code' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:available,assigned,disposed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Nama aset maksimal 255 karakter.',
            'serial_number.max' => 'Nomor seri aset maksimal 255 karakter.',
            'serial_number.unique' => 'Nomor seri aset sudah terdaftar.',
            'code.max' => 'Kode aset maksimal 255 karakter.',
            'category.max' => 'Kategori aset maksimal 255 karakter.',
            'status.in' => 'Status aset harus available, assigned, atau disposed.',
            'company_id.exists' => 'Perusahaan tidak valid.',
        ];
    }
}
