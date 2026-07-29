<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-3,3'],
            'embeddings' => ['sometimes', 'array', 'min:2', 'max:10'],
            'embeddings.*' => ['array', 'size:128'],
            'embeddings.*.*' => ['numeric', 'between:-3,3'],
            'captures' => ['sometimes', 'array'],
            'captures.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'embedding.required' => 'Data wajah wajib diisi.',
            'embedding.size' => 'Data wajah harus 128 dimensi.',
            'embedding.*.between' => 'Nilai embedding wajah harus antara -3 dan 3.',
            'embeddings.min' => 'Minimal 2 sampel wajah diperlukan.',
            'embeddings.max' => 'Maksimal 10 sampel wajah.',
            'embeddings.*.size' => 'Setiap sampel wajah harus 128 dimensi.',
            'embeddings.*.*.between' => 'Nilai embedding harus antara -3 dan 3.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // Legacy geometry descriptor (129D with [version, ...coords]) — no longer supported
        if (isset($data['descriptor']) && ! isset($data['embedding'])) {
            $descriptor = $data['descriptor'];
            $data['embedding'] = array_slice($descriptor, 1);
            $this->merge($data);
        }

        if (isset($data['embeddings']) && ! isset($data['embedding'])) {
            $embeddings = $data['embeddings'];
            $dim = count($embeddings[0] ?? []);
            $avg = array_fill(0, $dim, 0.0);
            foreach ($embeddings as $emb) {
                foreach ($emb as $i => $v) {
                    $avg[$i] += $v;
                }
            }
            $data['embedding'] = array_map(fn ($v) => $v / count($embeddings), $avg);
            $this->merge($data);
        }
    }
}
