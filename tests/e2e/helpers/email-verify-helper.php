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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Helper E2E untuk tests/e2e/email-verify.spec.ts.
 *
 * Mail di dev memakai SMTP (tidak bisa dibaca dari Playwright), jadi helper
 * ini menyediakan apa yang email would contain:
 *   - setup  : buat/reset user unverified e2e-verify@hrconnect.test
 *              (hapus session aktif + code lama + employee record minimal)
 *   - url    : cetak signed URL verification.verify — PERSIS isi email
 *              (URL::temporarySignedRoute id + sha1(email), sama dgn
 *              QueuedVerifyEmail::verificationUrl())
 *   - code N : set email_verification_code_hash = hash(N) + expiry 15 menit
 *   - status : cetak "verified:yes|no" dan "code:yes|no"
 *
 * Usage:
 *   php tests/e2e/helpers/email-verify-helper.php setup
 *   php tests/e2e/helpers/email-verify-helper.php url
 *   php tests/e2e/helpers/email-verify-helper.php code 424242
 *   php tests/e2e/helpers/email-verify-helper.php status
 */

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const E2E_VERIFY_EMAIL = 'e2e-verify@hrconnect.test';

function e2eVerifyUser(): User
{
    $user = User::firstOrCreate(
        ['email' => E2E_VERIFY_EMAIL],
        [
            'name' => 'E2E Verify User',
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

/** Ensure minimal employee record so /home renders after verification. */
function ensureVerifyEmployee(User $user): void
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
        'employee_number' => 'EMP-E2E-VERIFY',
        'full_name' => 'E2E Verify User',
        'nik' => '3276010000000999',
        'npwp' => '99.999.999.9-999.099',
        'phone' => '081999999999',
        'gender' => Gender::LAKI_LAKI,
        'marital_status' => MaritalStatus::SINGLE,
        'blood_type' => BloodType::O_PLUS,
        'status' => EmployeeStatus::ACTIVE,
        'birth_date' => '1995-06-15',
        'join_date' => '2024-01-01',
        'education_level' => EducationLevel::BACHELOR,
        'institution_name' => 'Universitas Indonesia',
        'major' => 'Teknik Informatika',
        'graduation_year' => 2018,
        'salary_type' => SalaryType::MONTHLY,
        'address_detail' => 'Jl. E2E Verify No. 1, Jakarta',
    ]);
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
        $user = e2eVerifyUser();
        ensureVerifyEmployee($user);

        // Reset ke kondisi unverified + bersihkan session (ActiveSessionGuard
        // memblokir login jika masih ada session aktif) + code lama.
        $user->forceFill([
            'email_verified_at' => null,
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Reset SEMUA rate limiter yang bisa kena alur email-verification.
        // Laravel 13 hashes keys ($shouldHashKeys=true) di middleware:
        //   - login (named 'login'):            md5('login'.'email|ip')
        //   - resend + verify-code (anon 6,1):  sha1(user_id)  [authenticated]
        // RateLimiter::clear($raw) TIDAK cocok dengan key ter-hash, jadi
        // hapus langsung dari tabel cache (key plaintext di DatabaseStore).
        $throttleKey = Str::transliterate(Str::lower(E2E_VERIFY_EMAIL).'|127.0.0.1');
        $loginHash = md5('login'.$throttleKey);
        $userSig = sha1((string) $user->id);

        foreach ([$throttleKey, $loginHash, $userSig] as $needle) {
            DB::table('cache')
                ->where('key', 'like', '%'.$needle.'%')
                ->delete();
        }

        echo 'OK '.$user->id.PHP_EOL;
        break;

    case 'url':
        $user = e2eVerifyUser();

        // Identik dgn QueuedVerifyEmail::verificationUrl() — isi email sungguhan.
        echo URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        ).PHP_EOL;
        break;

    case 'code':
        $code = (string) ($argv[2] ?? '424242');
        $user = e2eVerifyUser();

        $user->forceFill([
            'email_verified_at' => null,
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        echo 'code-set'.PHP_EOL;
        break;

    case 'status':
        $user = e2eVerifyUser();

        echo 'verified:'.($user->email_verified_at ? 'yes' : 'no').PHP_EOL;
        echo 'code:'.($user->email_verification_code_hash ? 'yes' : 'no').PHP_EOL;
        break;

    default:
        fwrite(STDERR, "Unknown command: {$command}\n");
        exit(1);
}
