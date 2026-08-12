#!/usr/bin/env bash
set -e
cd /home/merger/hrconnect

pkill -f 'artisan serve' 2>/dev/null || true
sleep 2

export CIPHERSWEET_KEY=$(grep '^CIPHERSWEET_KEY=' .env | cut -d= -f2 | tr -d '\n')

# Bersihkan sesi aktif (guard login ganda)
php artisan tinker --execute="Illuminate\Support\Facades\DB::table('sessions')->delete();" > /dev/null 2>&1 || true

(setsid php artisan serve --port=8000 > /tmp/artisan-serve-e2e.log 2>&1 < /dev/null &)

for i in $(seq 1 20); do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 2 http://localhost:8000/login 2>/dev/null || true)
  if [ "$code" = "200" ]; then
    echo "SERVER READY try=$i"
    break
  fi
  sleep 1
done

echo '=== VERIFIKASI DATA 1 TAHUN ==='
node scripts/verify-yearone.mjs 2>&1 | tail -18
