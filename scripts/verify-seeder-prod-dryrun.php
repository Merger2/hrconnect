<?php

/**
 * verify-seeder-prod-dryrun.php — Dry-run verifikasi guard isProduction seeder demo.
 *
 * Menjalankan command `db:seed` untuk 4 seeder demo/test (E2eTestSeeder,
 * CompanyEmployeesSeeder, DemoAttendanceSeeder, IntegrationSampleSeeder) di
 * environment PRODUCTION, dibungkus transaksi + rollback:
 *   - 0 perubahan persisten apa pun (dry-run murni)
 *   - Konfirmasi guard app()->isProduction() bekerja (0 baris baru)
 *
 * Cara pakai:
 *   php scripts/verify-seeder-prod-dryrun.php
 *
 * Exit code:
 *   0 = sukses — semua seeder ter-skip (0 baris baru) & rollback bersih
 *   1 = gagal  — environment bukan production ATAU ada seeder yang insert
 *
 * Catatan penting:
 *   - Environment di-set via $_SERVER['argv'] = ['artisan', '--env=production']
 *     (mekanisme yang sama dengan `php artisan --env=production`).
 *     Simulasi $_ENV['APP_ENV']/putenv saja TIDAK dihormati Laravel 11+.
 *   - Jalankan dengan `unset CIPHERSWEET_KEY` bila env OS masih menyimpan key
 *     lama (meng-override .env) — lihat docs/SECURITY-CHECKLIST item 1.1.
 *   - Aman dijalankan kapan pun: transaksi di-rollback di finally.
 */

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$_SERVER['argv'] = ['artisan', '--env=production'];
$_ENV['APP_ENV'] = 'production';
putenv('APP_ENV=production');

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$SEEDERS = [
    'Database\Seeders\E2eTestSeeder',
    'Database\Seeders\CompanyEmployeesSeeder',
    'Database\Seeders\DemoAttendanceSeeder',
    'Database\Seeders\IntegrationSampleSeeder',
];

echo 'ENVIRONMENT='.app()->environment().PHP_EOL;
echo 'isProduction='.(app()->isProduction() ? 'true' : 'FALSE').PHP_EOL.PHP_EOL;

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
            echo "   ⚠️  INSERT DETEKSI! Seeder ini TIDAK ter-skip di production.\n";
        }
    }
} finally {
    DB::rollBack();
}

$after = $counts();
$persistOk = $after === $before;

echo "\nSESUDAH ROLLBACK: $after".PHP_EOL;
echo 'PERSISTEN TIDAK BERUBAH: '.($persistOk ? 'YA ✅' : 'TIDAK ❌').PHP_EOL;
echo 'SEMUA SEEDER TER-SKIP: '.($insertDetected ? 'TIDAK ❌' : 'YA ✅ (0 baris baru)').PHP_EOL;

exit($insertDetected || ! $persistOk ? 1 : 0);
