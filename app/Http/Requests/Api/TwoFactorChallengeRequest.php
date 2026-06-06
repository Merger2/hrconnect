<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^(\d{6}|[a-zA-Z0-9]{8})$/'],
        ];
    }
}
