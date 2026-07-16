<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // Accepts both 'question' (BE standard) and 'message' (FE legacy) with conditional validation
            'question' => ['required_without:message', 'required_if:message,null', 'string', 'min:5', 'max:500'],
            'message' => ['required_without:question', 'nullable', 'string', 'min:5', 'max:500'],
            'conversation_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('message') && ! $this->has('question') && $this->input('message') !== null) {
            $this->merge(['question' => $this->input('message')]);
        }
    }
}
