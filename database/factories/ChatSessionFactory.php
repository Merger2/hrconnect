<?php

namespace Database\Factories;

use App\Models\ChatSession;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatSessionFactory extends Factory
{
    protected $model = ChatSession::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'title' => fake()->sentence(3),
            'status' => 'active',
        ];
    }
}
