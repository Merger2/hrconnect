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

        // Guard ganda (defense-in-depth): seeder demo/test di bawah berisi
        // data palsu (@hrconnect.local / pas-papan) + password & secret publik
        // ('password'/'ChangeMe!2026'/'owner12345'/'secret-key-123') — jangan
        // pernah di-seed di production. Masing-masing seeder juga punya guard
        // app()->isProduction() di dalam run() (lapis kedua, aman walau
        // dipanggil langsung via php artisan db:seed --class=...).
        //
        // Opt-in demo VPS (skripsi): set SEED_DEMO=true untuk mengizinkan
        // seeder demo berjalan walau APP_ENV=production — default off (guard
        // produksi tetap berlaku). Kombinasi demo penuh di VPS:
        //   SEED_DEMO=true php artisan db:seed --force
        if (! app()->isProduction() || filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call([
                CompanyEmployeesSeeder::class,   // 50 karyawan demo (owner+manager+staff)
                DemoAttendanceSeeder::class,     // 90 hari absensi demo utk user demo
                DemoUseCaseSeeder::class,        // use case demo (leave, overtime, reimbursement, dll)
                YearOneDemoSeeder::class,        // 1 tahun jadwal + absensi + cuti + lembur + 12 periode payroll (~33k baris)
                E2eTestSeeder::class,            // akun test E2E (employee/hr/manager/finance)
                E2eOperationalDataSeeder::class, // data operasional utk E2E testing
                K6PerformanceTestSeeder::class,  // akun K6 performance testing (5 role, password seragam)
                IntegrationSampleSeeder::class,  // client integrasi palsu (pas-papan)
            ]);
        }
    }
}
