<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChatStreamRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'question.required_without' => 'Pertanyaan wajib diisi.',
            'question.min' => 'Pertanyaan minimal 5 karakter.',
            'question.max' => 'Pertanyaan maksimal 500 karakter.',
            'message.required_without' => 'Pesan wajib diisi.',
            'message.min' => 'Pesan minimal 5 karakter.',
            'message.max' => 'Pesan maksimal 500 karakter.',
        ];
    }
}
