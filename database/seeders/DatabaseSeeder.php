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
            DemoAttendanceSeeder::class,
            E2eTestSeeder::class,
            IntegrationSampleSeeder::class,
            KnowledgeBaseSeeder::class,
        ]);
    }
}
