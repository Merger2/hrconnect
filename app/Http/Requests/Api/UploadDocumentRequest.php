<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'in:hr_policy,it_guide,general,finance,other'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul dokumen wajib diisi.',
            'title.max' => 'Judul dokumen maksimal 255 karakter.',
            'category.in' => 'Kategori dokumen tidak valid.',
            'file.required' => 'File dokumen wajib diunggah.',
            'file.mimes' => 'File dokumen harus berupa PDF.',
            'file.max' => 'File dokumen maksimal 10 MB.',
        ];
    }
}
