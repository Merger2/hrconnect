<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Agent streaming untuk chat KB (jalur SSE endpoint).
 *
 * Berbeda dari HrKnowledgeBaseAgent (yang memakai HasStructuredOutput
 * {answer, confidence}) — agent ini TANPA schema karena laravel/ai
 * menolak streaming untuk agent ber-structured output:
 * `Streaming structured output is not currently supported.`
 * (Providers/Concerns/StreamsText).
 *
 * Output Gemini berupa teks natural per TextDelta; jawaban di-stream ke
 * browser kata-per-kata lewat endpoint SSE, bukan $this->stream() Livewire.
 */
#[Provider(Lab::Gemini)]
#[Model('gemini-2.5-flash')]
#[Temperature(0.2)]
#[MaxTokens(1024)]
class HrKnowledgeBaseChatAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        return 'Anda adalah asisten AI untuk karyawan PT Daya Cipta Mandiri Solusi.\\n\\n'.
            'Jika user memberi sapaan (halo, hai, selamat pagi, dll) atau obrolan ringan, balas dengan ramah dan tawarkan bantuan seputar HR.\\n\\n'.
            'Untuk pertanyaan HR, jawab berdasarkan KONTEKS yang diberikan. Kalau jawaban tidak ada di konteks, jawab dengan jujur "Maaf, informasi tersebut belum tersedia di basis pengetahuan perusahaan."\\n\\n'.
            'Tulis jawaban ringkas, praktis, dan sertakan citation inline seperti [Sumber 1] atau [Sumber 2] pada klaim yang diambil dari konteks.\\n\\n'.
            'Jangan mengarang informasi HR yang tidak ada di konteks.\\n\\n'.
            'Jawab langsung dalam bahasa Indonesia tanpa markup JSON.';
    }
}
