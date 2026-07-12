<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * E2eTestSeeder — seed test users untuk Playwright E2E tests.
 *
 * Users yang dibutuhkan:
 * - test@hrconnect.test        → auth.spec.js
 * - employee@hrconnect.test    → clock-in, face-enrollment, rag-chat
 * - hr@hrconnect.test          → face-enrollment.spec.ts
 *
 * Semua password: 'password'
 * Role: employee (default)
 *
 * Idempotent: aman jalan berkali-kali.
 */
class E2eTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $employeeRole = Role::where('name', 'employee')->first();

        $testUsers = [
            ['email' => 'employee@hrconnect.test',  'name' => 'Test Employee', 'role' => 'employee',   'password' => 'password'],
            ['email' => 'hr@hrconnect.test',        'name' => 'Test HR',       'role' => 'hr-manager', 'password' => 'password'],
            ['email' => 'test@hrconnect.test',      'name' => 'Test User',     'role' => 'employee',   'password' => 'password'],
            ['email' => 'admin@hrconnect.local',    'name' => 'Super Admin',   'role' => 'super-admin', 'password' => 'ChangeMe!2026'],
            ['email' => 'manager@hrconnect.test',   'name' => 'Test Manager',  'role' => 'manager',    'password' => 'password'],
            ['email' => 'finance@hrconnect.test',   'name' => 'Test Finance',  'role' => 'finance',    'password' => 'password'],
        ];

        foreach ($testUsers as $data) {
            /** @var User $user */
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'                => $data['name'],
                    'password'            => Hash::make($data['password'] ?? 'password'),
                    'email_verified_at'   => now(),
                    'password_changed_at' => now(),
                ]
            );

            // Ensure password is always correct (firstOrCreate tidak update existing)
            if (! Hash::check($data['password'] ?? 'password', $user->password)) {
                $user->password = Hash::make($data['password'] ?? 'password');
                $user->password_changed_at = now();
                $user->save();
            }

            $roleName = $data['role'];
            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $user->refresh();
            }
        }
    }
}