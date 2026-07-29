<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'chat_thread_id' => ChatThread::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'attachment_path' => null,
        ];
    }
}
