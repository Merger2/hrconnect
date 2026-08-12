#!/usr/bin/env bash
set -e
cd /home/merger/hrconnect

# Key baru dari .env (target re-encrypt)
NEW_KEY=$(grep '^CIPHERSWEET_KEY=' .env | cut -d= -f2 | tr -d '\n')
echo "NEW_KEY len: ${#NEW_KEY}"

# Model PII yang implements CipherSweetEncrypted
MODELS="Company Employee Site WorkFromHomeRequest FamilyDetail Branch"

# 1) Backup ciphertext mentah per model+field (rollback jika perlu) — DILARANG decrypt
#    (key lama tetap di env OS; cukup dump kolom apa adanya).
echo "=== BACKUP ciphertext mentah -> /tmp/pii-backup.json ==="
php scripts/backup-pii.php

# 2) Re-encrypt: jalankan DENGAN env OS (key lama) supaya decrypt OK, target = NEW_KEY
echo "=== RE-ENCRYPT ke key .env baru ==="
for m in $MODELS; do
    echo "--- $m ---"
    php artisan ciphersweet:encrypt "App\\Models\\$m" "$NEW_KEY" --no-interaction 2>&1 | tail -2
done

# 3) Verifikasi: decrypt DENGAN key .env baru (unset env OS) harus OK
echo "=== VERIFIKASI decrypt dengan key .env baru (unset env OS) ==="
unset CIPHERSWEET_KEY
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ok = 0; $fail = 0;
foreach (App\Models\Employee::limit(30)->get() as $e) {
    try { $x = $e->phone; $ok++; } catch (\Throwable $ex) { $fail++; }
}
echo "Employee decrypt: OK=$ok FAIL=$fail\n";
' 2>&1 | tail -2
