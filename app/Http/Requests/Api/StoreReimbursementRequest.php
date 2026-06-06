<?php

namespace App\Http\Requests\Api;

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
            'title' => ['nullable', 'string', 'max:200'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
