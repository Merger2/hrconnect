<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-flash-latest')]
#[Temperature(0.2)]
#[MaxTokens(1024)]
class HrKnowledgeBaseAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        return 'Anda adalah asisten AI HRConnect untuk karyawan PT Daya Cipta Mandiri Solusi.\n\n'.
            'Jika user memberi sapaan (halo, hai, selamat pagi, dll) atau obrolan ringan, balas dengan ramah dan tawarkan bantuan seputar HR.\n\n'.
            'Untuk pertanyaan HR, jawab berdasarkan KONTEKS yang diberikan. Kalau jawaban tidak ada di konteks, jawab dengan jujur "Maaf, informasi tersebut belum tersedia di basis data HRConnect."\n\n'.
            'Jangan mengarang informasi HR yang tidak ada di konteks.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'answer' => $schema->string()->required(),
            'confidence' => $schema->string()->enum(['high', 'medium', 'low'])->required(),
        ];
    }
}
