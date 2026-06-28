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
}
