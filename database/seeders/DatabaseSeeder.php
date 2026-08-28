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
        //
        // Opt-in demo VPS (skripsi): set SEED_DEMO=true untuk mengizinkan
        // seeder demo berjalan walau APP_ENV=production — default off (guard
        // produksi tetap berlaku). Kombinasi demo penuh di VPS:
        //   SEED_DEMO=true SEED_YEAR_ONE=true php artisan db:seed --force
        if (! app()->isProduction() || filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call([
                CompanyEmployeesSeeder::class,   // 50 karyawan demo (owner+manager+staff)
                DemoAttendanceSeeder::class,     // 30 hari absensi demo utk user demo
                E2eTestSeeder::class,            // akun test E2E (employee/hr/manager/finance)
                K6PerformanceTestSeeder::class,  // akun K6 performance testing (5 role, password seragam)
                IntegrationSampleSeeder::class,  // client integrasi palsu (pas-papan)
            ]);

            // Seeder 1 TAHUN (jadwal + absensi + cuti + lembur + payroll 12 periode)
            // OPSIONAL — ~33k baris, berat utk test suite (RefreshDatabase + transaction
            // kena PostgreSQL out of shared memory). Jalankan eksplisit:
            //   php artisan db:seed --class=YearOneDemoSeeder
            // atau aktifkan flag: SEED_YEAR_ONE=true
            if (filter_var(env('SEED_YEAR_ONE', false), FILTER_VALIDATE_BOOLEAN)) {
                $this->call([YearOneDemoSeeder::class]);
            }
        }
    }
}
