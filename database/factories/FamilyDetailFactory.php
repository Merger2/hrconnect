<?php

namespace Database\Factories;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Models\Employee;
use App\Models\FamilyDetail;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyDetail>
 */
class FamilyDetailFactory extends Factory
{
    #[UseModel(FamilyDetail::class)]
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(Gender::cases()),
            'relationship' => $this->faker->randomElement(FamilyRelationship::cases()),
            'nik' => $this->faker->numerify('################'),
            'birth_date' => $this->faker->date(max: '-18 years'),
            'job' => $this->faker->jobTitle(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'is_emergency' => false,
        ];
    }

    public function emergency(): static
    {
        return $this->state(fn () => [
            'relationship' => FamilyRelationship::SPOUSE,
            'is_emergency' => true,
        ]);
    }
}
