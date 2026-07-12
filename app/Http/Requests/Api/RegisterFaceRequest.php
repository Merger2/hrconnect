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
            'embedding' => ['sometimes', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-3,3'],
            'descriptor' => ['sometimes', 'array', 'size:129'],
            'descriptor.*' => ['numeric', 'between:-3,3'],
            'embeddings' => ['sometimes', 'array', 'min:2', 'max:10'],
            'embeddings.*' => ['array', 'size:128'],
            'embeddings.*.*' => ['numeric', 'between:-3,3'],
            'captures' => ['sometimes', 'array'],
            'captures.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['descriptor']) && ! isset($data['embedding'])) {
            $descriptor = $data['descriptor'];
            $data['embedding'] = array_slice($descriptor, 1);
            $data['_descriptor_version'] = $descriptor[0];
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
