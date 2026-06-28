<?php

namespace App\Livewire;

use Livewire\Component;

class KnowledgeBaseChat extends Component
{
    public array $messages = [];

    public string $input = '';

    public ?string $conversationId = null;

    public bool $isStreaming = false;

    public function render()
    {
        return view('livewire.knowledge-base-chat');
    }
}
