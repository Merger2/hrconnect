<?php

namespace Database\Factories;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SalaryType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[UseModel(Employee::class)]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'division_id' => Division::factory(),
            'position_id' => Position::factory(),
            'nik' => $this->faker->unique()->numerify('3276############'),
            'npwp' => $this->faker->unique()->numerify('##.###.###.#-###.###'),
            'employee_number' => 'EMP-'.$this->faker->unique()->numberBetween(1000, 9999),
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'bank_account_number' => $this->faker->unique()->bankAccountNumber(),
            'bank_name' => $this->faker->randomElement(['Bank Central Asia (BCA)', 'Bank Mandiri', 'Bank Rakyat Indonesia (BRI)', 'Bank Negara Indonesia (BNI)', 'Bank Danamon']),
            'bank_account_name' => $this->faker->unique()->company(),
            'bank_account_holder' => $this->faker->name(),
            'basic_salary' => $this->faker->numberBetween(3_000_000, 15_000_000),
            'ptkp_status' => $this->faker->randomElement(['TK/0', 'K/0', 'K/1', 'K/2', 'K/3']),
            'nip' => $this->faker->numerify('3276############'),
            'payslip_password' => $this->faker->password(),
            'payslip_password_set_at' => now(),
            'gender' => $this->faker->randomElement(Gender::cases()),
            'marital_status' => $this->faker->randomElement(MaritalStatus::cases()),
            'blood_type' => $this->faker->randomElement(BloodType::cases()),
            'status' => EmployeeStatus::ACTIVE,
            'birth_date' => $this->faker->dateTimeBetween('-40 years', '-22 years')->format('Y-m-d'),
            'join_date' => $this->faker->dateTimeBetween('-5 years', '-1 years')->format('Y-m-d'),
            'education_level' => $this->faker->randomElement(EducationLevel::cases()),
            'institution_name' => 'Universitas '.$this->faker->city(),
            'major' => $this->faker->randomElement([
                'Teknik Informatika',
                'Sistem Informasi',
                'Manajemen',
                'Akuntansi',
                'Ilmu Komunikasi',
                'Teknik Industri',
                'Hukum',
                'Desain Komunikasi Visual',
            ]),
            'graduation_year' => $this->faker->numberBetween(2010, 2022),
            'salary_type' => SalaryType::MONTHLY,
            'address_detail' => $this->faker->streetAddress(),
        ];
    }
}
