<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('hrconnect.super_admin_email');
        $password = config('hrconnect.super_admin_password');

        if (empty($password)) {
            throw new \RuntimeException('SUPER_ADMIN_PASSWORD wajib diisi di .env');
        }

        $name = config('hrconnect.super_admin_name', 'Super Admin');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'group' => 'superadmin',
            ]
        );

        if (! $user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        $this->command?->info("Super Admin ready: {$email}");
    }
}
