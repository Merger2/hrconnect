<?php

namespace Database\Factories;

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Models\Company;
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
            // M14 AUDIT: knowledgeable sebelumnya di-morph ke Attendance (salah —
            // KB milik Company/User, lihat KnowledgeBaseSeeder + KnowledgeBaseService
            // yang memakai `$owner`). Company adalah pemilik KB yang paling umum.
            'knowledgeable_type' => Company::class,
            'knowledgeable_id' => Company::factory(),
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
        return $this->state(fn () => ['status' => KnowledgeBaseStatus::PROCESSING]);
    }

    public function error(): static
    {
        return $this->state(fn () => ['status' => KnowledgeBaseStatus::ERROR]);
    }
}
