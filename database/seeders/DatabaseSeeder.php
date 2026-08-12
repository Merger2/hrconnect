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
            KnowledgeBaseSeeder::class,
        ]);

        // Guard ganda (defense-in-depth): 4 seeder demo/test di bawah berisi
        // data palsu (@hrconnect.local / pas-papan) + password & secret publik
        // ('password'/'ChangeMe!2026'/'owner12345'/'secret-key-123') — jangan
        // pernah di-seed di production. Masing-masing seeder juga punya guard
        // app()->isProduction() di dalam run() (lapis kedua, aman walau
        // dipanggil langsung via php artisan db:seed --class=...).
        if (! app()->isProduction()) {
            $this->call([
                CompanyEmployeesSeeder::class,   // 50 karyawan demo (owner+manager+staff)
                DemoAttendanceSeeder::class,     // 30 hari absensi demo utk user demo
                E2eTestSeeder::class,            // akun test E2E (employee/hr/manager/finance)
                IntegrationSampleSeeder::class,  // client integrasi palsu (pas-papan)
            ]);
        }
    }
}
