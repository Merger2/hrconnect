<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Helper E2E untuk tests/e2e/register.spec.ts.
 *
 * Alur register mandiri: form → user baru (TANPA employee record — ini yang
 * diaudit) → /email/verify → link dari email → verified → /home.
 *
 *   - reset           : hapus user e2e-register@hrconnect.test + session +
 *                       cache rate limiter (supaya bisa register ulang)
 *   - url EMAIL       : cetak signed URL verification.verify — PERSIS isi
 *                       email (URL::temporarySignedRoute id + sha1(email),
 *                       sama dengan QueuedVerifyEmail::verificationUrl())
 *   - status EMAIL    : verified:yes|no, employee:yes|no, role:<slug>,
 *                       group:<group>
 *
 * Usage:
 *   php tests/e2e/helpers/register-helper.php reset
 *   php tests/e2e/helpers/register-helper.php url e2e-register@hrconnect.test
 *   php tests/e2e/helpers/register-helper.php status e2e-register@hrconnect.test
 */

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const REGISTER_EMAIL = 'e2e-register@hrconnect.test';

function registerUserByEmail(string $email): ?User
{
    return User::where('email', $email)->first();
}

$command = $argv[1] ?? 'status';
$email = $argv[2] ?? REGISTER_EMAIL;

// Guard keamanan: helper ini hanya boleh jalan di local/testing (mutasi DB).
if (! app()->environment(['local', 'testing'])) {
    fwrite(STDERR, 'Refusing to run outside local/testing environment.'.PHP_EOL);
    exit(1);
}

switch ($command) {
    case 'reset':
        $user = registerUserByEmail($email);

        if ($user) {
            // Hapus session aktif (ActiveSessionGuard memblokir login kedua).
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // Hapus cache rate limiter login (key ter-hash md5 di middleware).
            $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');
            foreach ([$throttleKey, md5('login'.$throttleKey)] as $needle) {
                DB::table('cache')->where('key', 'like', '%'.$needle.'%')->delete();
            }

            $user->forceDelete();
        }

        echo 'reset-done'.PHP_EOL;
        break;

    case 'url':
        $user = registerUserByEmail($email);

        if (! $user) {
            fwrite(STDERR, "User not found: {$email}\n");
            exit(1);
        }

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

    case 'status':
        $user = registerUserByEmail($email);

        if (! $user) {
            echo 'exists:no'.PHP_EOL;

            break;
        }

        echo 'exists:yes'.PHP_EOL;
        echo 'id:'.$user->id.PHP_EOL;
        echo 'group:'.($user->group ?? '-').PHP_EOL;
        echo 'verified:'.($user->email_verified_at ? 'yes' : 'no').PHP_EOL;
        echo 'employee:'.($user->employee !== null ? 'yes' : 'no').PHP_EOL;
        echo 'role:'.($user->roles()->pluck('slug')->implode(',') ?: '-').PHP_EOL;
        break;

    default:
        fwrite(STDERR, "Unknown command: {$command}\n");
        exit(1);
}
