<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'regex:/^(\+62|0)\d{9,12}$/'],
            'address_detail' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'regex:/^\d{8,18}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Format nomor telepon tidak valid. Gunakan 08xx atau +62xx (10-13 digit).',
            'address_detail.max' => 'Alamat maksimal 500 karakter.',
            'bank_name.max' => 'Nama bank maksimal 100 karakter.',
            'bank_account_number.regex' => 'Nomor rekening harus terdiri dari 8-18 digit angka.',
        ];
    }
}
