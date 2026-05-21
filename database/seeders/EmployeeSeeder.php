<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. JALUR BELAKANG: Bikin Master Data Dummy biar MariaDB gak marah (Foreign Key Aman)
        // Kita pakai DB::table biar nggak perlu mikirin Modelnya udah dibikin atau belum.

        $companyId = DB::table('companies')->insertGetId([
            'name' => 'PT Tech Nusantara',
            'code' => 'TECH',
            'phone' => '021-12345678',
            'email' => 'Perushaan@gmail.com',
            'npwp' => '123456789012345',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'HQ Depok',
            'address' => 'Margonda',
            'is_main' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deptId = DB::table('departments')->insertGetId([
            'branch_id' => $branchId,
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $positionId = DB::table('positions')->insertGetId([
            'department_id' => $deptId, 'name' => 'Software Engineer', 'code' => 'SE',
            'grade' => 3, 'basic_salary' => 12000000, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 2. Bikin 1 User Admin sekalian buat lu login nanti
        $userId = DB::table('users')->insertGetId([
            'name' => 'Fikih Admin',
            'email' => 'admin@hris.com',
            'password' => Hash::make('password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Karyawan ke-1: Akun lu sendiri yang nyambung ke User Fikih Admin
        Employee::factory()->create([
            'user_id' => $userId,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'department_id' => $deptId,
            'position_id' => $positionId,
            'full_name' => 'Fikih (Super Admin)',
        ]);

        // Bikin 20 Karyawan Random lainnya (Nggak pakai user_id dulu biar cepet)
        Employee::factory()->count(20)->create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'department_id' => $deptId,
            'position_id' => $positionId,
        ]);

        $this->command->info('✅ 21 Data Karyawan berhasil di-generate!');
    }
}
