#!/usr/bin/env bash
# E2E Playwright — server + suite dalam SATU proses (pola terbukti stabil).
#
# ⚠️ Kenapa satu proses? Di env tool/harness, child process DIKILL saat command
# shell selesai — `nohup`/`setsid`/`disown` TIDAK cukup (gejala:
# `net::ERR_CONNECTION_REFUSED` di /__e2e-login saat suite dijalankan dari
# command terpisah). Server harus hidup SELAMA Playwright jalan, dan mati
# bersama script (trap cleanup).
#
# Pemakaian:
#   bash scripts/run-e2e.sh                                   # full suite (semua project)
#   bash scripts/run-e2e.sh --project=chromium-employee       # satu project role
#   bash scripts/run-e2e.sh tests/e2e/twofa.spec.ts           # satu spec file
#   E2E_PORT=8001 bash scripts/run-e2e.sh                     # port kustom
#
# Exit code = exit code Playwright (0 hijau, 1 gagal, 2 no-tests).

set -u
cd "$(dirname "$0")/.."

PORT="${E2E_PORT:-8000}"
BASE_URL="http://localhost:${PORT}"
LOG_FILE="${E2E_LOG:-/tmp/artisan-serve-e2e.log}"

# ── 1. Bersihkan server lama + sesi/throttle bekas E2E ─────────
pkill -f "artisan serve --port=${PORT}" 2>/dev/null || true
sleep 1

CIPHERSWEET_KEY=$(grep '^CIPHERSWEET_KEY=' .env | cut -d= -f2 | tr -d '\n' || true)
if [ -z "${CIPHERSWEET_KEY}" ]; then
    echo "ERROR: CIPHERSWEET_KEY tidak ada di .env" >&2
    exit 1
fi
export CIPHERSWEET_KEY

# ActiveSessionGuard menolak login ganda + login throttle (cache DB) —
# bersihkan supaya suite fresh tiap run.
php artisan tinker --execute="
    Illuminate\Support\Facades\DB::table('sessions')->delete();
    Illuminate\Support\Facades\DB::table('cache')->delete();
" > /dev/null 2>&1 || true

# ── 2. Start server (background, setsid = process group sendiri) ─
setsid php artisan serve --port="${PORT}" > "${LOG_FILE}" 2>&1 < /dev/null &
SERVER_PID=$!

cleanup() {
    kill "${SERVER_PID}" 2>/dev/null || true
    pkill -f "artisan serve --port=${PORT}" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

# ── 3. Tunggu siap (deteksi dini kalau server crash) ───────────
READY=0
for i in $(seq 1 30); do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 2 "${BASE_URL}/login" 2>/dev/null || true)
    if [ "${code}" = "200" ]; then
        READY=1
        echo "server ready (try=${i}, port=${PORT})"
        break
    fi
    if ! kill -0 "${SERVER_PID}" 2>/dev/null; then
        echo "ERROR: server mati sebelum siap — log:" >&2
        tail -20 "${LOG_FILE}" >&2
        exit 1
    fi
    sleep 1
done

if [ "${READY}" != "1" ]; then
    echo "ERROR: server tidak siap setelah 30s — log:" >&2
    tail -20 "${LOG_FILE}" >&2
    exit 1
fi

# ── 4. Jalankan suite (argumen diteruskan ke Playwright) ────────
if [ "$#" -gt 0 ]; then
    echo "=== PLAYWRIGHT: $* ==="
    npx playwright test --reporter=list "$@"
else
    echo "=== PLAYWRIGHT: full suite ==="
    npx playwright test --reporter=list
fi
EXIT_CODE=$?

echo "=== E2E exit: ${EXIT_CODE} ==="
exit "${EXIT_CODE}"
