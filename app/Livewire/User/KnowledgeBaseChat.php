<?php

namespace App\Livewire\User;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shell halaman KB Chat.
 *
 * Semua logika chat (kirim pesan dan streaming jawaban) dijalankan di
 * CLIENT lewat Alpine + fetch ke endpoint SSE `knowledge-base.chat.stream`
 * (pola ship-ai-with-laravel) — komponen ini hanya otorisasi + prefill ?q=
 * + welcome message. Streaming TIDAK lewat Livewire $this->stream()
 * (bug vendor Livewire 4.2.x + Symfony 8, lihat AUDIT.md #60).
 */
#[Layout('layouts.app')]
class KnowledgeBaseChat extends Component
{
    use AuthorizesRequests;

    public string $initialQuestion = '';

    public string $welcomeMessage = '';

    public function mount(): void
    {
        $this->authorize('view_knowledgebase');

        // Prefill dari ?q= (link "Tanya AI" di index/detail dokumen) —
        // user tinggal menekan kirim.
        $this->initialQuestion = (string) request()->query('q', '');

        $this->welcomeMessage = __('Halo! Saya asisten AI PT Daya Cipta Mandiri Solusi. Tanyakan apa saja seputar kebijakan dan prosedur kepegawaian.');
    }

    public function render()
    {
        return view('livewire.user.knowledge-base-chat');
    }
}
