<?php

/**
 * verify-seeder-prod-dryrun.php — Dry-run verifikasi guard isProduction seeder demo.
 *
 * DUA MODE:
 *   default          — verifikasi guard produksi: 4 seeder demo/test
 *                     (E2eTestSeeder, CompanyEmployeesSeeder, DemoAttendanceSeeder,
 *                     IntegrationSampleSeeder) TER-SKIP di APP_ENV=production
 *                     (0 baris baru, rollback bersih). Exit 0 = guard bekerja.
 *   --demo           — verifikasi flag opt-in SEED_DEMO=true: seeder demo HARUS
 *                     BENAR-BENAR insert baris di production (bukti bypass berfungsi),
 *                     tetap dibungkus transaksi + rollback (0 perubahan persisten).
 *                     Exit 0 = flag bekerja + rollback bersih.
 *
 * Cara pakai:
 *   php scripts/verify-seeder-prod-dryrun.php          # guard default (skip)
 *   php scripts/verify-seeder-prod-dryrun.php --demo   # flag SEED_DEMO=true (insert)
 *
 * Exit code:
 *   0 = sukses — perilaku sesuai mode (skip / insert-dalam-transaksi) + rollback bersih
 *   1 = gagal  — environment bukan production ATAU perilaku menyimpang
 *
 * Catatan penting:
 *   - Environment di-set via $_SERVER['argv'] = ['artisan', '--env=production']
 *     (mekanisme yang sama dengan `php artisan --env=production`).
 *     Simulasi $_ENV['APP_ENV']/putenv saja TIDAK dihormati Laravel 11+.
 *   - Mode --demo memakai putenv('SEED_DEMO=true') SEBELUM bootstrap (sama dgn
 *     `SEED_DEMO=true php artisan ...` di shell).
 *   - Jalankan dengan `unset CIPHERSWEET_KEY` bila env OS masih menyimpan key
 *     lama (meng-override .env) — lihat docs/SECURITY-CHECKLIST item 1.1.
 *   - Aman dijalankan kapan pun: transaksi di-rollback di finally.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$demoMode = in_array('--demo', $argv, true);

$_SERVER['argv'] = ['artisan', '--env=production'];
$_ENV['APP_ENV'] = 'production';
putenv('APP_ENV=production');

if ($demoMode) {
    $_ENV['SEED_DEMO'] = 'true';
    putenv('SEED_DEMO=true');
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$SEEDERS = [
    'Database\Seeders\E2eTestSeeder',
    'Database\Seeders\CompanyEmployeesSeeder',
    'Database\Seeders\DemoAttendanceSeeder',
    'Database\Seeders\IntegrationSampleSeeder',
];

echo 'ENVIRONMENT='.app()->environment().PHP_EOL;
echo 'isProduction='.(app()->isProduction() ? 'true' : 'FALSE').PHP_EOL;
echo 'SEED_DEMO='.($demoMode ? 'true (mode --demo)' : '(tidak diset — default off)').PHP_EOL.PHP_EOL;

if (! app()->isProduction()) {
    echo "❌ GAGAL: environment bukan production — dry-run dibatalkan.\n";

    exit(1);
}

$counts = function (): string {
    return 'users='.DB::table('users')->count()
        .' employees='.DB::table('employees')->count()
        .' attendances='.DB::table('attendances')->count()
        .' integration_clients='.DB::table('integration_clients')->count();
};

$before = $counts();
echo "SEBELUM (baseline): $before\n\n";

$insertDetected = false;
DB::beginTransaction();

try {
    foreach ($SEEDERS as $class) {
        Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
        $out = trim((string) Artisan::output());
        $now = $counts();

        echo sprintf('%-52s output=[%s]', $class, $out !== '' ? substr($out, 0, 60) : '(kosong — skip dini)').PHP_EOL;
        echo '   dalam transaksi: '.$now.PHP_EOL;

        if ($now !== $before) {
            $insertDetected = true;
        }
    }
} finally {
    DB::rollBack();
}

$after = $counts();
$persistOk = $after === $before;

echo "\nSESUDAH ROLLBACK: $after".PHP_EOL;
echo 'PERSISTEN TIDAK BERUBAH: '.($persistOk ? 'YA ✅' : 'TIDAK ❌').PHP_EOL;

if ($demoMode) {
    echo 'SEEDER MENJALANKAN INSERT (mode --demo): '.($insertDetected ? 'YA ✅ — flag SEED_DEMO=true mem-bypass guard produksi' : 'TIDAK ❌ — guard masih memblokir').PHP_EOL;

    exit($insertDetected && $persistOk ? 0 : 1);
}

echo 'SEMUA SEEDER TER-SKIP: '.($insertDetected ? 'TIDAK ❌' : 'YA ✅ (0 baris baru)').PHP_EOL;

exit($insertDetected || ! $persistOk ? 1 : 0);
