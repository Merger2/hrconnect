<?php

namespace App\Http\Requests\Api;

use App\Support\SecureUploadPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreReimbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:reimbursement_categories,id'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'receipt' => array_merge(
                ['required'],
                (new SecureUploadPolicy)->rules('receipt'),
            ),
        ];
    }
}
