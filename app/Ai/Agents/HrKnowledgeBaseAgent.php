<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-2.5-flash')]
#[Temperature(0.2)]
#[MaxTokens(1024)]
class HrKnowledgeBaseAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'Anda adalah asisten AI HRConnect untuk karyawan PT 521 Teknologi Indonesia. Jawab pertanyaan user dalam Bahasa Indonesia berdasarkan KONTEKS yang diberikan. Kalau jawaban tidak ada di konteks, jawab "Maaf, informasi tersebut belum tersedia di basis data HRConnect." Jangan mengarang atau menggunakan pengetahuan eksternal.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'answer' => $schema->string()->required(),
            'confidence' => $schema->string()->enum(['high', 'medium', 'low'])->required(),
        ];
    }
}
