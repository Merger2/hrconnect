<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Teknologi Informasi', 'Sumber Daya Manusia', 'Keuangan',
            'Operasional', 'Pemasaran', 'Penjualan', 'Legal', 'Umum',
        ]);

        return [
            'branch_id' => Branch::factory(),
            'name' => $name,
            'code' => strtoupper(substr(str_replace(' ', '', $name), 0, 5)),
            'description' => 'Divisi '.$name,
            'is_active' => true,
        ];
    }
}
