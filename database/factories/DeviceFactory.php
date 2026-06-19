<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    #[UseModel(Device::class)]
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'device_uuid' => $this->faker->uuid(),
            'device_type' => $this->faker->randomElement(['mobile', 'desktop', 'tablet']),
            'device_name' => $this->faker->word(),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => $this->faker->randomElement(['Android', 'iOS', 'Windows', 'macOS', 'Linux']),
            'is_verified' => false,
            'last_used_at' => now(),
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
