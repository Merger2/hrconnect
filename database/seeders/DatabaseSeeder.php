<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            \Laravolt\Indonesia\Seeds\DatabaseSeeder::class,
            RoleAndPermissionSeeder::class,
            SuperAdminSeeder::class,
            CompanyAndDivisionSeeder::class,
            CompanySettingSeeder::class,
            SettingSeeder::class,
            PayrollComponentSeeder::class,
            ShiftSeeder::class,
            LeaveTypeSeeder::class,
            HolidaySeeder::class,
            PayrollConfigSeeder::class,
            TarifTerSeeder::class,
            BranchSeeder::class,
            CompanyEmployeesSeeder::class,
            E2eTestSeeder::class,
            IntegrationSampleSeeder::class,
        ]);
<<<<<<< HEAD

        if (! app()->isProduction()) {
            $this->call(DemoDataSeeder::class);
            $this->call(E2eTestSeeder::class);
            $this->call(AttendanceSeeder::class);

            User::where('email', 'test@example.com')->firstOrCreate([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
=======
>>>>>>> main
    }
}
