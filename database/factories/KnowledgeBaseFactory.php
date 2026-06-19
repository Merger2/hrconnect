<?php

namespace Database\Factories;

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeBase>
 */
class KnowledgeBaseFactory extends Factory
{
    #[UseModel(KnowledgeBase::class)]
    public function definition(): array
    {
        return [
            'knowledgeable_type' => \App\Models\Attendance::class,
            'knowledgeable_id' => 1,
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => KnowledgeBaseStatus::READY,
            'category' => $this->faker->randomElement(KnowledgeBaseCategory::cases()),
            'metadata' => ['source' => 'factory'],
            'page_number' => $this->faker->numberBetween(1, 50),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => KnowledgeBaseStatus::DRAFT]);
    }

    public function error(): static
    {
        return $this->state(fn () => ['status' => KnowledgeBaseStatus::ERROR]);
    }
}
