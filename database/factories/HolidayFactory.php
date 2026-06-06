<?php

namespace Database\Factories;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::parse(fake()->dateTimeBetween('first day of January', 'last day of December'))->toDateString(),
            'name' => fake()->randomElement([
                'Tahun Baru', 'Hari Raya Idul Fitri', 'Hari Kemerdekaan',
                'Hari Raya Natal', 'Tahun Baru Islam', 'Hari Pahlawan',
            ]),
            'is_active' => true,
        ];
    }
}
