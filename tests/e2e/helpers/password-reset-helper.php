<?php

declare(strict_types=1);

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SalaryType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Helper E2E untuk tests/e2e/password-reset.spec.ts.
 *
 * Mail di dev memakai SMTP (tidak bisa dibaca dari Playwright), jadi helper
 * ini menyediakan apa yang email would contain:
 *   - setup : buat/reset user e2e-reset@hrconnect.test (password lama
 *             "OldPass!2026"), verified, employee record minimal, bersihkan
 *             session + rate limiter + token lama
 *   - token : generate token reset via Password::broker()->getRepository()
 *             (createNewToken = hash_hmac sha256 Str::random(40) persis
 *             DatabaseTokenRepository) lalu simpan bcrypt di tabel
 *             password_reset_tokens — cetak URL reset PERSIS isi email
 *             (route('password.reset', [token, email]))
 *   - status: cetak password_changed_at:yes|no + verified:yes|no + token
 *
 * Usage:
 *   php tests/e2e/helpers/password-reset-helper.php setup
 *   php tests/e2e/helpers/password-reset-helper.php token
 *   php tests/e2e/helpers/password-reset-helper.php status
 */

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const E2E_RESET_EMAIL = 'e2e-reset@hrconnect.test';
const E2E_RESET_OLD = 'OldPass!2026';
const E2E_RESET_NEW = 'NewPass!2026';

function e2eResetUser(): User
{
    $user = User::firstOrCreate(
        ['email' => E2E_RESET_EMAIL],
        [
            'name' => 'E2E Reset User',
            'password' => Hash::make(E2E_RESET_OLD),
            'password_changed_at' => now()->subDays(10),
            'email_verified_at' => now(),
            'group' => 'user',
        ]
    );

    if (! $user->hasRole('employee')) {
        $user->assignRole('employee');
    }

    return $user;
}

/** Ensure minimal employee record so /home renders after login. */
function ensureResetEmployee(User $user): void
{
    if ($user->employee !== null) {
        return;
    }

    $company = Company::query()->first() ?? Company::factory()->create();
    $branch = Branch::where('company_id', $company->id)->where('is_main', true)->first()
        ?? Branch::factory()->for($company)->create(['is_main' => true]);
    $division = Division::where('branch_id', $branch->id)->first()
        ?? Division::factory()->for($branch)->create();
    $position = Position::where('division_id', $division->id)->first()
        ?? Position::factory()->for($division)->create();

    Employee::factory()->for($company)->for($branch)->for($division)->for($position)->create([
        'user_id' => $user->id,
        'employee_number' => 'EMP-E2E-RESET',
        'full_name' => 'E2E Reset User',
        'nik' => '3276010000000777',
        'npwp' => '77.777.777.7-777.077',
        'phone' => '081777777777',
        'gender' => Gender::LAKI_LAKI,
        'marital_status' => MaritalStatus::SINGLE,
        'blood_type' => BloodType::O_PLUS,
        'status' => EmployeeStatus::ACTIVE,
        'birth_date' => '1994-03-20',
        'join_date' => '2024-02-01',
        'education_level' => EducationLevel::BACHELOR,
        'institution_name' => 'Universitas Indonesia',
        'major' => 'Manajemen',
        'graduation_year' => 2017,
        'salary_type' => SalaryType::MONTHLY,
        'address_detail' => 'Jl. E2E Reset No. 7, Jakarta',
    ]);
}

$command = $argv[1] ?? 'status';

// Guard keamanan: helper ini hanya boleh jalan di local/testing.
if (! app()->environment(['local', 'testing'])) {
    fwrite(STDERR, 'Refusing to run outside local/testing environment.'.PHP_EOL);
    exit(1);
}

switch ($command) {
    case 'setup':
        $user = e2eResetUser();
        ensureResetEmployee($user);

        // Kembalikan ke kondisi: password lama, verified, bukan expired
        // (password_changed_at = 10 hari lalu, di bawah 90 hari default).
        $user->forceFill([
            'password' => Hash::make(E2E_RESET_OLD),
            'password_changed_at' => now()->subDays(10),
            'email_verified_at' => now(),
        ])->save();

        // Hapus session aktif (ActiveSessionGuard memblokir login) + token lama.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', E2E_RESET_EMAIL)->delete();

        // Reset rate limiter (Laravel 13 hashes keys — hapus langsung dari cache).
        $throttleKey = Str::transliterate(Str::lower(E2E_RESET_EMAIL).'|127.0.0.1');
        $loginHash = md5('login'.$throttleKey);

        foreach (array_unique([$throttleKey, $loginHash]) as $needle) {
            DB::table('cache')->where('key', 'like', '%'.$needle.'%')->delete();
        }

        echo 'OK '.$user->id.PHP_EOL;
        break;

    case 'token':
        $user = e2eResetUser();

        // Reproduksi isi email reset PERSIS:
        // DatabaseTokenRepository::createNewToken = hash_hmac('sha256',
        // Str::random(40), hashKey); token disimpan bcrypt; URL di email =
        // url(route('password.reset', ['token' => $token, 'email' => $email])).
        $token = Password::broker()->getRepository()->createNewToken($user);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => E2E_RESET_EMAIL],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        echo route('password.reset', ['token' => $token, 'email' => E2E_RESET_EMAIL]).PHP_EOL;
        break;

    case 'status':
        $user = e2eResetUser();

        echo 'verified:'.($user->email_verified_at ? 'yes' : 'no').PHP_EOL;
        echo 'password_changed_at:'.($user->password_changed_at ? 'yes' : 'no').PHP_EOL;
        // Usia (menit) sejak password terakhir diubah — untuk memverifikasi
        // reset benar-benar menyegarkan tanggal (bukan cuma field ada isinya).
        echo 'changed_minutes_ago:'.($user->password_changed_at
            ? (int) $user->password_changed_at->diffInMinutes(now())
            : -1).PHP_EOL;
        echo 'token:'.(DB::table('password_reset_tokens')->where('email', E2E_RESET_EMAIL)->exists() ? 'yes' : 'no').PHP_EOL;
        break;

    default:
        fwrite(STDERR, "Unknown command: {$command}\n");
        exit(1);
}
