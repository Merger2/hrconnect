<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\MaritalStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Enums\Gender;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * BulkEmployeeSeeder — generate 50 karyawan dummy + absensi 30 hari.
 * Idempoten: aman dijalankan berkali-kali (skip kalau sudah ada).
 *
 * Cara jalan:
 *   php artisan db:seed --class=BulkEmployeeSeeder
 */
class BulkEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'HRCONNECT')->firstOrFail();
        $branches = Branch::where('company_id', $company->id)->whereHas('departments')->get();
        $departments = collect($branches)->flatMap(fn ($b) => $b->departments)->unique('id');
        $positions = Position::whereHas('department.branch', fn ($q) => $q->where('company_id', $company->id))->get();
        $shift = Shift::firstOrFail();

        if ($branches->isEmpty() || $departments->isEmpty() || $positions->isEmpty()) {
            $this->command?->error('Branch / Department / Position kosong. Jalankan CompanyAndDepartmentSeeder dulu.');

            return;
        }

        $targetCount = 50;
        $existing = Employee::count();
        $toCreate = max(0, $targetCount - $existing);

        if ($toCreate === 0) {
            $this->command?->info("✅ Sudah ada {$existing} karyawan. Skip create.");
        }

        $firstNames = ['Budi', 'Siti', 'Andi', 'Rina', 'Dedi', 'Maya', 'Joko', 'Sari', 'Agus', 'Dewi', 'Eko', 'Nita', 'Rian', 'Lina', 'Fajar', 'Yuni', 'Bayu', 'Tuti', 'Hendra', 'Wati', 'Adi', 'Nur', 'Rudi', 'Eka', 'Santo', 'Rika', 'Arif', 'Indah', 'Bambang', 'Sinta', 'Darma', 'Rini', 'Tri', 'Yoga', 'Ani', 'Joko', 'Surya', 'Fitri', 'Heri', 'Yanti', 'Slamet', 'Ita', 'Bima', 'Sri', 'Anto', 'Mira', 'Wawan', 'Putri', 'Tono', 'Lestari'];
        $lastNames = ['Saputra', 'Wijaya', 'Kusuma', 'Pratama', 'Utami', 'Hidayat', 'Santoso', 'Lestari', 'Ramadan', 'Anggraini', 'Nugroho', 'Permata', 'Setiawan', 'Maharani', 'Gunawan', 'Putra', 'Sari', 'Wibowo', 'Kurniawan', 'Puspita'];

        $genders = [Gender::LAKI_LAKI, Gender::PEREMPUAN];
        $marital = [MaritalStatus::SINGLE, MaritalStatus::MARRIED];

        $created = 0;
        for ($i = 0; $i < $toCreate; $i++) {
            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[($i * 7) % count($lastNames)];
            $fullName = "{$firstName} {$lastName}";
            $email = 'emp' . str_pad((string) ($existing + $i + 1), 3, '0', STR_PAD_LEFT) . '@hrconnect.test';
            $employeeNumber = 'BULK-' . str_pad((string) ($existing + $i + 1), 4, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $fullName,
                    'password' => Hash::make('Employee1234'),
                    'email_verified_at' => now(),
                    'password_changed_at' => now(),
                ]
            );
            $user->assignRole('employee');

            $branch = $branches->random();
            $department = $departments->random();
            $position = $positions->random();

            Employee::firstOrCreate(
                ['employee_number' => $employeeNumber],
                [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'shift_id' => $shift->id,
                    'nik' => '3276' . str_pad((string) (100000000000 + $existing + $i + 1), 12, '0', STR_PAD_LEFT),
                    'npwp' => rand(10, 99) . '.' . rand(100, 999) . '.' . rand(100, 999) . '.' . rand(1, 9) . '-' . rand(100, 999) . '.' . rand(100, 999),
                    'full_name' => $fullName,
                    'phone' => '0812' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'bank_account_number' => (string) rand(1000000000, 9999999999),
                    'bank_name' => fake()->randomElement(['Bank Central Asia (BCA)', 'Bank Mandiri', 'Bank Rakyat Indonesia (BRI)', 'Bank Negara Indonesia (BNI)']),
                    'gender' => $genders[array_rand($genders)],
                    'marital_status' => $marital[array_rand($marital)],
                    'blood_type' => fake()->randomElement(['A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'O-']),
                    'status' => EmployeeStatus::ACTIVE,
                    'birth_date' => fake()->dateTimeBetween('-40 years', '-22 years')->format('Y-m-d'),
                    'join_date' => fake()->dateTimeBetween('-5 years', '-1 years')->format('Y-m-d'),
                    'education_level' => fake()->randomElement(['sma', 'smk', 'diploma', 'bachelor', 'master']),
                    'institution_name' => 'Universitas ' . fake()->city(),
                    'major' => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi', 'Manajemen', 'Akuntansi', 'Ilmu Komunikasi', 'Hukum']),
                    'graduation_year' => fake()->numberBetween(2010, 2022),
                    'salary_type' => 'monthly',
                    'address_detail' => fake()->streetAddress(),
                ]
            );
            $created++;
        }

        $this->command?->info("✅ {$created} karyawan baru dibuat (total: " . Employee::count() . ')');

        // --- Absensi 30 hari terakhir untuk semua karyawan ---
        $this->seedAttendances();
    }

    private function seedAttendances(): void
    {
        $shift = Shift::firstOrFail();
        $employees = Employee::all();
        $days = 30;
        $startDate = now()->subDays($days - 1);
        $totalCreated = 0;

        foreach ($employees as $emp) {
            for ($d = 0; $d < $days; $d++) {
                $date = $startDate->copy()->addDays($d);

                // Skip weekend (Sabtu/Minggu)
                if ($date->isWeekend()) {
                    continue;
                }

                // Skip kalau sudah ada (idempoten)
                if (Attendance::where('employee_id', $emp->id)->where('date', $date->toDateString())->exists()) {
                    continue;
                }

                $rand = rand(1, 100);
                if ($rand <= 5) {
                    // 5% absen (alfa)
                    Attendance::create([
                        'employee_id' => $emp->id,
                        'shift_id' => $shift->id,
                        'date' => $date->toDateString(),
                        'status' => AttendanceStatus::ABSENT,
                        'is_wfa' => false,
                        'verification_method' => VerificationMethod::MANUAL,
                    ]);
                    $totalCreated++;

                    continue;
                }

                $late = $rand <= 20; // 15% terlambat
                $clockInHour = $late ? fake()->numberBetween(8, 10) : fake()->numberBetween(7, 8);
                $clockInMin = $late ? fake()->numberBetween(1, 45) : fake()->numberBetween(45, 59);
                $clockOutHour = fake()->numberBetween(16, 19);
                $clockOutMin = fake()->numberBetween(0, 59);

                $clockIn = $date->copy()->setTime($clockInHour, $clockInMin, 0);
                $clockOut = $date->copy()->setTime($clockOutHour, $clockOutMin, 0);

                Attendance::create([
                    'employee_id' => $emp->id,
                    'shift_id' => $shift->id,
                    'date' => $date->toDateString(),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'lat_in' => fake()->latitude(-6.4, -6.1),
                    'long_in' => fake()->longitude(106.7, 106.9),
                    'lat_out' => fake()->latitude(-6.4, -6.1),
                    'long_out' => fake()->longitude(106.7, 106.9),
                    'status' => $late ? AttendanceStatus::LATE : AttendanceStatus::ON_TIME,
                    'late_minutes' => $late ? rand(1, 45) : 0,
                    'is_wfa' => false,
                    'verification_method' => VerificationMethod::FACE_VERIFIED,
                    'face_similarity_score' => fake()->randomFloat(2, 85, 99),
                ]);
                $totalCreated++;
            }
        }

        $this->command?->info("✅ {$totalCreated} record absensi baru dibuat (30 hari kerja).");
    }
}