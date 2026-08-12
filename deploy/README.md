# 🚀 HRConnect — Konfigurasi Deploy Produksi

Runbook operasional: **supervisor worker + scheduler + cache + HTTPS**. File di folder ini siap di-copy ke server. Semua nilai placeholder (`your-domain`, `/var/www/hrconnect`, user) WAJIB disesuaikan.

**Referensi:** `docs/PRODUCTION-READINESS.md` (readiness report) · `docs/SECURITY-CHECKLIST.md` (rotasi secret — **baca & eksekusi SEBELUM deploy**).

---

## 0. Prasyarat Server

| Komponen | Versi |
|---|---|
| Ubuntu/Debian | 22.04+ (LTS) |
| PHP | 8.5+ (`php-cli`, `php-fpm`, `php-pgsql`, `php-mbstring`, `php-xml`, `php-zip`, `php-intl`, `php-bcmath`, `php-gd`) |
| PostgreSQL | 15+ + ekstensi `pgvector`, `pg_trgm`, `pgcrypto` |
| Nginx | 1.24+ |
| Supervisor | 4.x |
| Composer 2 | + Node 20+ (build asset di CI/server) |
| Certbot | Let's Encrypt (HTTPS) |

```bash
sudo apt update && sudo apt install -y nginx supervisor certbot python3-certbot-nginx \
  php8.5-cli php8.5-fpm php8.5-pgsql php8.5-mbstring php8.5-xml php8.5-zip php8.5-intl php8.5-bcmath php8.5-gd
```

---

## 1. Supervisor — Queue Worker (WAJIB)

App memakai `QUEUE_CONNECTION=database` → butuh worker persisten. Satu worker menerima queue `maintenance` (backup pipeline) + `default` (notifikasi, PDF, import/export).

```bash
sudo cp deploy/supervisor/hrconnect-worker.conf /etc/supervisor/conf.d/
sudo sed -i 's|/var/www/hrconnect|/PATH/KE/APP|g' /etc/supervisor/conf.d/hrconnect-worker.conf
# jika user proses berbeda (mis. deploy), ganti "user=www-data"
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status          # harap: RUNNING (2 proses)
```

**Cek log worker:**
```bash
sudo supervisorctl tail -100 hrconnect-worker:hrconnect-worker_00
```

**Catatan penting:** `routes/console.php` punya fallback `queue:work --stop-when-empty` tiap menit (gate `SCHEDULE_QUEUE_WORKER`). Dengan supervisor aktif, **set `SCHEDULE_QUEUE_WORKER=false` di `.env`** supaya tidak double-consume antrean.

### Reverb WebSocket (OPSIONAL — default nonaktif)
Hanya jika realtime dipakai (`BROADCAST_CONNECTION=reverb`). Lihat komentar di `deploy/supervisor/hrconnect-reverb.conf`. Jika tidak dipakai, **jangan copy file-nya**.

---

## 2. Scheduler (crontab — WAJIB)

Satu baris cron menjalankan 10+ job terjadwal (absensi auto-detect, backup 02:00, reminder, reset kuota cuti, heartbeat health):

```bash
crontab -e
# tambahkan:
* * * * * cd /PATH/KE/APP && php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi:
```bash
php artisan schedule:list          # daftar semua job + jadwal
# setelah 1-2 menit (CACHE_STORE=database):
php artisan tinker --execute="echo Illuminate\\Support\\Facades\\Cache::get('health:scheduler_heartbeat_at');"
curl -s https://your-domain/api/v1/health | jq .   # scheduler.ok harus true
```

---

## 3. Cache Konfigurasi (WAJIB setiap deploy)

```bash
# SETELAH .env final & semua migration jalan:
php artisan config:cache    # membekukan semua nilai env — TTFB turun drastis
php artisan route:cache     # kompilasi route
php artisan view:cache      # kompilasi blade
php artisan event:cache     # kompilasi listener (Laravel 11+)
composer dump-autoload --optimize
```

⚠️ **Urutan penting:** `config:cache` SETELAH `.env` final. Jika env berubah (mis. ganti DB), **ulangi** `php artisan config:cache`. Verifikasi: `ls bootstrap/cache/` harus ada `config.php` + `routes-v7.php`. Untuk rollback: `php artisan config:clear` + `route:clear`.

**Deploy script reference (deploy.sh di server — bukan di repo):**
```bash
#!/usr/bin/env bash
set -euo pipefail
cd /PATH/KE/APP
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
sudo supervisorctl restart hrconnect-worker:*
echo "✅ Deploy selesai $(date)"
```

---

## 4. HTTPS — Checklist (WAJIB sebelum traffic dibuka)

### 4.1 Setup TLS (Let's Encrypt)
```bash
# 1. Copy Nginx block & sesuaikan server_name/root/fastcgi_pass
sudo cp deploy/nginx/hrconnect-ssl.conf /etc/nginx/sites-available/hrconnect
sudo sed -i 's|your-domain|DOMAIN_ANDA|g; s|/var/www/hrconnect|/PATH/KE/APP|g' /etc/nginx/sites-available/hrconnect
sudo ln -sf /etc/nginx/sites-available/hrconnect /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 2. Sertifikat
sudo certbot --nginx -d DOMAIN_ANDA -d www.DOMAIN_ANDA
# auto-renew otomatis via systemd timer certbot.timer (cek: systemctl status certbot.timer)

# 3. .env
APP_URL=https://DOMAIN_ANDA        # WAJIB — URL absolut dipakai email/PWA/redirect
SESSION_SECURE_COOKIE=true
```

### 4.2 Checklist verifikasi HTTPS

| # | Item | Perintah verifikasi | ✅ |
|---|---|---|---|
| 1 | Redirect HTTP→HTTPS | `curl -sI http://DOMAIN_ANDA | grep -i location` → `301 https://…` | ☐ |
| 2 | Sertifikat valid + chain | `curl -sI https://DOMAIN_ANDA | grep -i 'HTTP/'` → `200`; `echo \| openssl s_client -connect DOMAIN_ANDA:443 2>/dev/null \| openssl x509 -noout -dates` | ☐ |
| 3 | TLS 1.2/1.3 (tanpa TLS 1.0/1.1) | `openssl s_client -tls1_1 -connect DOMAIN_ANDA:443 </dev/null 2>&1 \| grep -c "alert protocol version"` → `1` (ditolak) | ☐ |
| 4 | HSTS header | `curl -sI https://DOMAIN_ANDA \| grep -i strict-transport` | ☐ |
| 5 | Security headers | `curl -sI https://DOMAIN_ANDA \| grep -iE 'x-frame|x-content|referrer'` | ☐ |
| 6 | SSL Labs skor A/A+ | https://www.ssllabs.com/ssltest/analyze.html?d=DOMAIN_ANDA | ☐ |
| 7 | `APP_DEBUG=false` | `curl -s https://DOMAIN_ANDA/tidak-ada | grep -c 'Whoops\|Stack trace'` → `0` | ☐ |
| 8 | Mixed content bersih | Console browser: 0 request `http://` (semua `https://`) | ☐ |
| 9 | HSTS preload (opsional, stabilitas) | https://hstspreload.org | ☐ |

> HSTS (`Strict-Transport-Security`) sudah di-set Nginx + middleware `EnsureSecurityHeaders` saat HTTPS. Jangan aktifkan `preload` sampai HTTPS stabil beberapa minggu — sekali preload, browser wajib HTTPS selamanya.

---

## 5. Pasca-Deploy — Verifikasi Menyeluruh

```bash
# 1. Health endpoint (harus "ok")
curl -s https://DOMAIN_ANDA/api/v1/health | jq .

# 2. Backup drill (RTO 4 jam terukur)
php artisan maintenance:backup-restore-drill

# 3. AI KB gate (>= 90% relevan)
php artisan kb:eval

# 4. Cache aktif
ls bootstrap/cache/ | grep -E 'config|routes'

# 5. Supervisor + scheduler
sudo supervisorctl status
crontab -l | grep schedule:run

# 6. Suite (di CI/pre-deploy, bukan prod)
composer run ci:check
```

---

## 6. Rollback Plan

| Situasi | Aksi |
|---|---|
| Deploy merusak halaman | `git revert HEAD` (atau checkout tag lama) → `composer install --no-dev --optimize` → `php artisan config:cache` → reload |
| DB migration bermasalah | Restore dari backup terakhir (`php artisan backup:restore` / drill) — backup harian 02:00, retensi 14 hari |
| Worker hang | `sudo supervisorctl restart hrconnect-worker:*` |
| Cache stale | `php artisan config:clear && php artisan route:clear && php artisan view:clear` lalu cache ulang |
