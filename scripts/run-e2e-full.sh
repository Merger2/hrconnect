#!/usr/bin/env bash
# E2E Playwright penuh — server + suite dalam SATU proses (pola terbukti stabil).
# Pelajaran 2026-08-12: server harus di-launch dengan CIPHERSWEET_KEY dari .env
# (env OS lama meng-override -> halaman PII 500 Invalid ciphertext).
set -e
cd /home/merger/hrconnect

pkill -f 'artisan serve' 2>/dev/null || true
sleep 2

export CIPHERSWEET_KEY=$(grep '^CIPHERSWEET_KEY=' .env | cut -d= -f2 | tr -d '\n')
echo "server key len: ${#CIPHERSWEET_KEY}"

# Bersihkan sesi aktif (ActiveSessionGuard menolak login ganda)
php artisan tinker --execute="Illuminate\Support\Facades\DB::table('sessions')->delete();" > /dev/null 2>&1 || true

(setsid php artisan serve --port=8000 > /tmp/artisan-serve-e2e.log 2>&1 < /dev/null &)

for i in $(seq 1 25); do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 2 http://localhost:8000/login 2>/dev/null || true)
  if [ "$code" = "200" ]; then
    echo "SERVER READY try=$i"
    break
  fi
  sleep 1
done

echo '=== FULL E2E SUITE ==='
timeout 570 npx playwright test --reporter=list 2>&1 | tail -45
echo "e2e-exit=$?"
