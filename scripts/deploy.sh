#!/usr/bin/env bash
# HRConnect — Deploy sekali-jalan ke production.
#
# Menjalankan seluruh urutan deploy ulang (setelah kode baru di-push):
#   git pull → composer install → build asset → migrate → seed KB → cache → restart worker
#
# Pemakaian:
#   bash scripts/deploy.sh                 # deploy biasa (migrate + seed KB idempotent)
#   bash scripts/deploy.sh --fresh         # + seed master data (role, TER, BPJS, dll) — hanya untuk INSTALL PERTAMA
#   bash scripts/deploy.sh --no-build      # skip npm build (mis. asset tidak berubah)
#   DEPLOY_BRANCH=main bash scripts/deploy.sh   # branch kustom (default: branch aktif)
#
# ⚠️ Asumsi: dijalankan DI SERVER PRODUKSI (user dengan akses git + php + npm),
#    bukan di laptop dev. Jangan jalankan di .env APP_ENV=local.
#
# Exit code: 0 = sukses, non-zero = gagal (berhenti di langkah pertama yang error).

set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BRANCH="${DEPLOY_BRANCH:-$(git -C "$APP_DIR" branch --show-current)}"
DO_FRESH_SEED=false
DO_BUILD=true

for arg in "$@"; do
    case "$arg" in
        --fresh) DO_FRESH_SEED=true ;;
        --no-build) DO_BUILD=false ;;
        *) echo "⚠️  Argumen tidak dikenal: $arg (abaikan)" >&2 ;;
    esac
done

# ── 0. Guard: jangan jalankan di env non-produksi ────────────────────────
if [ -f "$APP_DIR/.env" ] && grep -q '^APP_ENV=local' "$APP_DIR/.env"; then
    echo "❌ APP_ENV=local terdeteksi — script ini untuk PRODUKSI." >&2
    echo "   Jalankan di server produksi (APP_ENV=production)." >&2
    exit 1
fi

echo "🚀 Deploy HRConnect ($(date '+%Y-%m-%d %H:%M:%S'))"
echo "   Dir:   $APP_DIR"
echo "   Branch: $BRANCH"

cd "$APP_DIR"

# ── 1. Maintenance mode (buka lalu tutup di akhir) ───────────────────────
php artisan down --retry=10 2>/dev/null || true
trap 'php artisan up 2>/dev/null || true' EXIT

# ── 2. Pull kode terbaru ─────────────────────────────────────────────────
echo "── git pull ($BRANCH)"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

# ── 3. Dependencies ──────────────────────────────────────────────────────
echo "── composer install --no-dev --optimize-autoloader"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ "$DO_BUILD" = true ]; then
    echo "── npm ci && npm run build"
    npm ci --no-audit --no-fund
    npm run build
fi

# ── 4. Database ──────────────────────────────────────────────────────────
echo "── php artisan migrate --force"
php artisan migrate --force

if [ "$DO_FRESH_SEED" = true ]; then
    echo "── seed master data (--fresh) — sekali saja"
    php artisan db:seed --force
fi

echo "── seed KnowledgeBase (idempotent — 36 entry + embedding 768D)"
php artisan db:seed --class=KnowledgeBaseSeeder --force

# ── 5. Cache produksi (WAJIB SETELAH .env final & migrate) ───────────────
echo "── cache config/route/view/event"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer dump-autoload --optimize

# ── 6. Restart queue worker (bila supervisor tersedia) ───────────────────
if command -v supervisorctl >/dev/null 2>&1 && supervisorctl status hrconnect-worker >/dev/null 2>&1; then
    echo "── restart supervisor hrconnect-worker"
    sudo supervisorctl restart hrconnect-worker:* 2>/dev/null || supervisorctl restart hrconnect-worker:* || true
fi

# ── 7. Buka maintenance + verifikasi ─────────────────────────────────────
php artisan up
trap - EXIT

echo "✅ Deploy selesai. Cek:"
echo "   curl -s https://$(grep '^APP_URL=' .env | cut -d= -f2 | sed 's|https\?://||; s|/$||')/api/v1/health"
