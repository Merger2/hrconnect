<?php

namespace App\Http\Requests\Api;

use App\Support\SecureUploadPolicy;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReimbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:reimbursement_categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:1000'],
            'description' => ['sometimes', 'required', 'string', 'min:10', 'max:1000'],
            'expense_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'receipt' => array_merge(
                ['sometimes'],
                (new SecureUploadPolicy)->rules('receipt'),
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori reimbursemen wajib dipilih.',
            'category_id.exists' => 'Kategori reimbursemen tidak valid.',
            'title.required' => 'Judul reimbursemen wajib diisi.',
            'title.max' => 'Judul reimbursemen maksimal 255 karakter.',
            'amount.required' => 'Jumlah reimbursemen wajib diisi.',
            'amount.numeric' => 'Jumlah reimbursemen harus berupa angka.',
            'amount.min' => 'Jumlah reimbursemen minimal Rp1.000.',
            'description.required' => 'Deskripsi reimbursemen wajib diisi.',
            'description.min' => 'Deskripsi reimbursemen minimal 10 karakter.',
            'description.max' => 'Deskripsi reimbursemen maksimal 1000 karakter.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.before_or_equal' => 'Tanggal pengeluaran tidak boleh melebihi hari ini.',
        ];
    }
}
