<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // RBAC harus jalan duluan supaya role 'super-admin' ada
        // sebelum SuperAdminSeeder coba assignRole('super-admin').
        $this->call([
            RoleAndPermissionSeeder::class,
            SuperAdminSeeder::class,
            EmployeeSeeder::class,

            // Nanti kalau lu bikin Seeder lain, tinggal tambahin di bawahnya:
            // LeaveSeeder::class,
            // AttendanceSeeder::class,
        ]);

        // Test user — hanya kalau APP_ENV bukan production
        if (! app()->isProduction()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
