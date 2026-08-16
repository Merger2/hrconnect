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
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Helper E2E untuk tests/e2e/twofa.spec.ts.
 *
 * OTP 2FA tidak bisa "dibaca dari email" seperti verifikasi email — kode
 * TOTP di-generate dari secret pengguna. Helper ini menyediakan apa yang
 * app authenticator (Google Authenticator) hasilkan:
 *   - setup   : buat/reset user 2FA e2e-2fa@hrconnect.test (secret base32
 *               valid + 8 recovery codes + confirmed) + employee record
 *               minimal + hapus session + reset rate limiter keys
 *   - otp     : cetak kode TOTP 6 digit SAAT INI (persis yang dihasilkan
 *               Google Authenticator dari secret pengguna)
 *   - recovery: cetak recovery code pertama yang valid (bisa dipakai login)
 *   - status  : cetak "2fa:yes|no"
 *
 * Usage:
 *   php tests/e2e/helpers/twofa-helper.php setup
 *   php tests/e2e/helpers/twofa-helper.php otp
 *   php tests/e2e/helpers/twofa-helper.php recovery
 *   php tests/e2e/helpers/twofa-helper.php status
 */

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const E2E_2FA_EMAIL = 'e2e-2fa@hrconnect.test';

function e2e2faUser(): User
{
    $user = User::firstOrCreate(
        ['email' => E2E_2FA_EMAIL],
        [
            'name' => 'E2E 2FA User',
            'password' => Hash::make('password'),
            'password_changed_at' => now(),
            'group' => 'user',
        ]
    );

    if (! $user->hasRole('employee')) {
        $user->assignRole('employee');
    }

    return $user;
}

/** Ensure minimal employee record so /home renders after 2FA login. */
function ensure2faEmployee(User $user): void
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
        'employee_number' => 'EMP-E2E-2FA',
        'full_name' => 'E2E 2FA User',
        'nik' => '3276010000000777',
        'npwp' => '77.777.777.7-777.077',
        'phone' => '081777777777',
        'gender' => Gender::LAKI_LAKI,
        'marital_status' => MaritalStatus::SINGLE,
        'blood_type' => BloodType::O_PLUS,
        'status' => EmployeeStatus::ACTIVE,
        'birth_date' => '1994-05-20',
        'join_date' => '2024-01-01',
        'education_level' => EducationLevel::BACHELOR,
        'institution_name' => 'Universitas Indonesia',
        'major' => 'Teknik Informatika',
        'graduation_year' => 2017,
        'salary_type' => SalaryType::MONTHLY,
        'address_detail' => 'Jl. E2E 2FA No. 1, Jakarta',
    ]);
}

/** Reset semua rate limiter yang kena alur 2FA (login + two-factor challenge). */
function reset2faRateLimiters(User $user): void
{
    // Login limiter (named 'login'): md5('login'.'email|ip') + plaintext.
    $throttleKey = Str::transliterate(Str::lower(E2E_2FA_EMAIL).'|127.0.0.1');
    $loginHash = md5('login'.$throttleKey);

    // Two-factor challenge limiter (named 'two-factor'): by = session login.id
    // (user id) → md5('two-factor'.user_id). Plus sha1(user_id) untuk anon
    // throttle yang mungkin ikut kena (verify-code dll).
    $twoFactorHash = md5('two-factor'.(string) $user->id);
    $userSig = sha1((string) $user->id);

    foreach ([$throttleKey, $loginHash, $twoFactorHash, $userSig] as $needle) {
        DB::table('cache')
            ->where('key', 'like', '%'.$needle.'%')
            ->delete();
    }
}

$command = $argv[1] ?? 'status';

// Guard keamanan: helper ini hanya boleh jalan di local/testing (mutasi DB:
// user, session, rate limiter). Konvensi sama dgn E2eLoginController.
if (! app()->environment(['local', 'testing'])) {
    fwrite(STDERR, 'Refusing to run outside local/testing environment.'.PHP_EOL);
    exit(1);
}

switch ($command) {
    case 'setup':
        $user = e2e2faUser();
        ensure2faEmployee($user);

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user->forceFill([
            'email_verified_at' => now(),
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'E2EFA-AAAA-1111',
                'E2EFA-BBBB-2222',
                'E2EFA-CCCC-3333',
                'E2EFA-DDDD-4444',
                'E2EFA-EEEE-5555',
                'E2EFA-FFFF-6666',
                'E2EFA-GGGG-7777',
                'E2EFA-HHHH-8888',
            ])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        // Hapus session aktif (ActiveSessionGuard memblokir login kedua) +
        // reset rate limiter.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        reset2faRateLimiters($user);

        echo 'OK '.$user->id.PHP_EOL;
        break;

    case 'otp':
        $user = e2e2faUser();
        $secret = decrypt($user->two_factor_secret);

        echo (new Google2FA)->getCurrentOtp($secret).PHP_EOL;
        break;

    case 'recovery':
        $user = e2e2faUser();

        echo $user->recoveryCodes()[0].PHP_EOL;
        break;

    case 'status':
        $user = e2e2faUser();

        echo '2fa:'.($user->two_factor_secret ? 'yes' : 'no').PHP_EOL;
        break;

    case 'disable':
        $user = e2e2faUser();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        reset2faRateLimiters($user);

        echo 'disabled'.PHP_EOL;
        break;

    default:
        fwrite(STDERR, "Unknown command: {$command}\n");
        exit(1);
}
