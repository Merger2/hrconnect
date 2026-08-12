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
            WilayahSeeder::class,
            RoleAndPermissionSeeder::class,
            SuperAdminSeeder::class,
            CompanyAndDivisionSeeder::class,
            CompanySettingSeeder::class,
            SettingSeeder::class,
            PayrollComponentSeeder::class,
            ShiftSeeder::class,
            ReimbursementCategorySeeder::class,
            LeaveTypeSeeder::class,
            HolidaySeeder::class,
            PayrollConfigSeeder::class,
            TarifTerSeeder::class,
            BranchSeeder::class,
            CompanyEmployeesSeeder::class,
            DemoAttendanceSeeder::class,
            IntegrationSampleSeeder::class,
            KnowledgeBaseSeeder::class,
        ]);

        // Guard ganda (defense-in-depth): E2eTestSeeder berisi akun test
        // dengan password publik ('password'/'ChangeMe!2026') — jangan pernah
        // di-seed di production. E2eTestSeeder sendiri juga sudah guard
        // app()->isProduction() di dalam run() (baris 39).
        if (! app()->isProduction()) {
            $this->call(E2eTestSeeder::class);
        }
    }
}
