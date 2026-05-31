<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SuperAdminSeeder — bootstrap default super admin user.
 *
 * Credentials di-load dari env:
 * - SUPER_ADMIN_EMAIL       (default: admin@hrconnect.local)
 * - SUPER_ADMIN_PASSWORD    (default: ChangeMe!2026)
 * - SUPER_ADMIN_NAME        (default: Super Admin)
 *
 * Idempotent: pakai firstOrCreate + assignRole. Aman jalan berkali-kali.
 *
 * Catatan keamanan: password default WAJIB diganti setelah login pertama
 * via flow Force Change Password (REQ-AUTH-11). Untuk production, set
 * env SUPER_ADMIN_PASSWORD ke nilai kuat sebelum deploy.
 *
 * Depends on: RoleAndPermissionSeeder harus jalan dulu supaya role
 * 'super-admin' ada di database.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@hrconnect.local');
        $password = env('SUPER_ADMIN_PASSWORD', 'ChangeMe!2026');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        $this->command?->info("Super Admin ready: {$email}");
    }
}
