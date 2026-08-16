# 🚀 HRConnect — Panduan Deploy Production (Langkah demi Langkah)

Panduan end-to-end: dari VPS kosong sampai aplikasi live + HTTPS + seed data.
Ini **eksekusi** dari checklist (`docs/SECURITY-CHECKLIST.md`) + runbook
(`deploy/ROTATION-RUNBOOK.md`). Ikuti urut — jangan lompat.

**Referensi cepat:** `deploy/README.md` (konfigurasi supervisor/scheduler/HTTPS)
· `deploy/nginx/hrconnect-ssl.conf` (Nginx siap pakai) · `deploy/supervisor/*.conf`
· `.env.production.example` (template env produksi) · `scripts/deploy.sh` (deploy ulang).

---

## Fase 0 — Sebelum mulai (di laptop, sekali)

```bash
# 1. Commit semua perubahan + push (lakukan dari laptop Anda)
git add -A && git commit -m "feat: persiapan go-live" && git push

# 2. Pastikan repo PRIVATE di GitHub (Settings → General → Danger Zone → Change visibility)
#    🔴 WAJIB: history berisi kredensial lama (commit 6a24cc4) — jangan publikasikan.
```

---

## Fase 1 — Pilih & beli VPS

| Opsi | Biaya | Cocok untuk |
|---|---|---|
| **Hetzner CX22** (2 vCPU / 4 GB / 40 GB) | ~€3.5–4/bln | Demo skripsi + produksi kecil — **rekomendasi** |
| DigitalOcean droplet basic | ~$6/bln | Alternatif, UI familiar |
| IDCloudHost / lokal ID | ~Rp75–150rb/bln | Latensi Indonesia lebih baik |

**Syarat minimum:** 2 vCPU, 4 GB RAM, 40 GB disk, Ubuntu 22.04/24.04 LTS.
(4 GB cukup untuk 1 user demo + PostgreSQL + queue worker.)

Setelah VPS aktif:
1. **SSH key** — generate di laptop: `ssh-keygen -t ed25519`, lalu tambah ke panel VPS.
2. Catat **IP server** + buka port 22/80/443 di firewall.
3. (Opsional) Beli domain + A record → IP server. Tanpa domain HTTPS tetap bisa via IP,
   tapi kamera/GPS butuh HTTPS — **sangat disarankan pakai domain** (bisa pakai subdomain
   gratis dari DuckDNS untuk demo).

---

## Fase 2 — Setup server (sekali, ±30 menit)

SSH ke server, lalu jalankan baris demi baris:

```bash
# 2.1 Update + install paket dasar (PHP 8.5, Nginx, PostgreSQL, supervisor, certbot)
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx supervisor certbot python3-certbot-nginx \
  php8.5-cli php8.5-fpm php8.5-pgsql php8.5-mbstring php8.5-xml php8.5-zip \
  php8.5-intl php8.5-bcmath php8.5-gd php8.5-curl php8.5-zip php8.5-zip \
  postgresql postgresql-contrib composer unzip git curl

# 2.2 PostgreSQL + ekstensi (pgvector, pg_trgm, pgcrypto)
sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS vector;" template1
sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;" template1
sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;" template1
# ⚠️ Install di template1 → setiap database baru (termasuk temp DB drill restore)
#    otomatis punya extension. Ini WAJIB untuk drill restore non-superuser.
```

> Jika `php8.5-*` tidak tersedia di repo default Ubuntu, tambah PPA
> (ondrej/php): `sudo add-apt-repository ppa:ondrej/php -y && sudo apt update`.

---

## Fase 3 — Buat user & database PostgreSQL

```bash
# 3.1 User aplikasi (password dirotasi — bukan dari history!)
sudo -u postgres psql <<'SQL'
CREATE USER hrconnect_app WITH PASSWORD 'GANTI_PASSWORD_STRONG';
CREATE DATABASE hrconnect OWNER hrconnect_app;
-- role drill (CREATEDB, BUKAN superuser) — untuk backup restore drill
CREATE USER drill_hrconnect WITH PASSWORD 'GANTI_PASSWORD_STRONG' CREATEDB;
GRANT CONNECT ON DATABASE hrconnect TO drill_hrconnect;
SQL

# 3.2 WAJIB keamanan: ganti metode auth ke scram-sha-256 (bukan trust/md5)
sudo nano /etc/postgresql/15/main/pg_hba.conf
# ganti baris host all all 127.0.0.1/32 → scram-sha-256 (dan ::1/128)
sudo systemctl restart postgresql

# 3.3 Verifikasi (harus bisa login dengan password baru)
PGPASSWORD='GANTI_PASSWORD_STRONG' psql -h 127.0.0.1 -U hrconnect_app -d hrconnect -c 'SELECT 1;'
```

---

## Fase 4 — Deploy kode (pertama kali)

```bash
# 4.1 Clone + permission
sudo mkdir -p /var/www && sudo chown $USER:$USER /var/www
cd /var/www && git clone git@github.com:USER/REPO.git hrconnect
cd hrconnect

# 4.2 Env + key (WAJIB ikuti — jangan skip)
cp .env.production.example .env
nano .env        # isi SEMUA placeholder (lihat .env.production.example komentar)
php artisan key:generate --force
php artisan ciphersweet:generate-key   # paste hasilnya ke CIPHERSWEET_KEY di .env

# 4.3 Dependencies + asset
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

# 4.4 Migrate + seed (KB WAJIB — RAG tidak jalan tanpa embedding)
php artisan migrate --force
php artisan db:seed --force                    # master data (role, TER, BPJS, dll)
php artisan db:seed --class=KnowledgeBaseSeeder --force   # KB + 768D embedding

# 4.5 Cache (WAJIB SETELAH .env final)
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
composer dump-autoload --optimize
```

---

## Fase 5 — Nginx + HTTPS (WAJIB sebelum traffic)

```bash
# 5.1 Copy config & sesuaikan (domain, path, user)
sudo cp deploy/nginx/hrconnect-ssl.conf /etc/nginx/sites-available/hrconnect
sudo sed -i 's|your-domain|YOUR_DOMAIN|g; s|/var/www/hrconnect|/var/www/hrconnect|g' /etc/nginx/sites-available/hrconnect
sudo ln -sf /etc/nginx/sites-available/hrconnect /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 5.2 Sertifikat Let's Encrypt (auto-renew via certbot.timer)
sudo certbot --nginx -d YOUR_DOMAIN -d www.YOUR_DOMAIN

# 5.3 .env final + cache ulang
#   APP_URL=https://YOUR_DOMAIN
#   SESSION_SECURE_COOKIE=true
php artisan config:cache
```

**Verifikasi HTTPS (9 poin — `deploy/README.md` §4):**
```bash
curl -sI http://YOUR_DOMAIN | grep -i location        # 301 https://
curl -sI https://YOUR_DOMAIN | grep -iE 'strict-transport|HTTP/'
curl -s https://YOUR_DOMAIN/tidak-ada | grep -c 'Whoops'   # 0
```

---

## Fase 6 — Supervisor + Scheduler (WAJIB)

```bash
# 6.1 Queue worker
sudo cp deploy/supervisor/hrconnect-worker.conf /etc/supervisor/conf.d/
sudo sed -i 's|/var/www/hrconnect|/var/www/hrconnect|g' /etc/supervisor/conf.d/hrconnect-worker.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status

# 6.2 Scheduler (crontab — satu baris menjalankan SEMUA job: backup 02:00, reminder, dll)
crontab -e
# tambahkan:
* * * * * cd /var/www/hrconnect && php artisan schedule:run >> /dev/null 2>&1
```

> ⚠️ Dengan supervisor aktif, `.env` WAJIB `SCHEDULE_QUEUE_WORKER=false` (sudah di template).

---

## Fase 7 — Rotasi secret (🔴 WAJIB — lihat `deploy/ROTATION-RUNBOOK.md`)

| Item | Status sebelum | Aksi |
|---|---|---|
| `DB_PASSWORD` | 🔴 pernah di history git | Rotasi `ALTER USER` + `scram-sha-256` (runbook §1) |
| `APP_KEY` | 🔴 key dev di history | `key:generate --force` + `APP_PREVIOUS_KEYS` (runbook §2) |
| `MAIL_PASSWORD` | 🔴 di history | Rotasi di provider email |
| `GEMINI_API_KEY` | 🟠 key dev | Key produksi + cost alert |
| `BACKUP_ARCHIVE_PASSWORD` | 🟠 kosong | Sudah di template — isi kuat |
| 2FA admin | ☐ | Aktifkan di profil admin + simpan recovery codes |

---

## Fase 8 — Pasca-deploy: verifikasi final

```bash
# 8.1 Health endpoint (harus "ok" — backup fresh, queue up, storage up)
curl -s https://YOUR_DOMAIN/api/v1/health | jq .

# 8.2 Backup drill (RTO 4 jam terukur — butuh role drill + extension di template1)
php artisan maintenance:backup-restore-drill      # expect: RESTORE DRILL PASSED

# 8.3 AI KB gate (>= 90% relevan)
php artisan kb:eval

# 8.4 Smoke test — buka di browser:
#   - https://YOUR_DOMAIN/login → login admin → cek dashboard
#   - Knowledge Base → tanya "apa itu cuti tahunan?" → jawaban AI + source
#   - /scan → coba flow absen (kamera butuh HTTPS)

# 8.5 Deploy ulang ke depan cukup satu perintah:
bash scripts/deploy.sh
```

---

## Rollback cepat

| Situasi | Aksi |
|---|---|
| Deploy merusak halaman | `git revert HEAD` → `composer install --no-dev --optimize` → `php artisan config:cache` |
| DB migration bermasalah | Restore backup terakhir (`php artisan maintenance:backup-restore-drill` / restore UI) |
| Worker hang | `sudo supervisorctl restart hrconnect-worker:*` |
| Cache stale | `php artisan config:clear && route:clear && view:clear` → cache ulang |

---

## Checklist akhir go-live

- [ ] Repo PRIVATE, semua secret dirotasi (runbook §1–§3)
- [ ] `APP_ENV=production` · `APP_DEBUG=false` · `APP_URL=https://...`
- [ ] PostgreSQL `scram-sha-256` + extension di template1
- [ ] HTTPS aktif (redirect 301, HSTS, sertifikat valid)
- [ ] Supervisor worker RUNNING + crontab schedule:run
- [ ] `migrate --force` + seed KB (36 entry, 768D embedding)
- [ ] Health `/api/v1/health` = ok · drill restore PASS · `kb:eval` ≥ 90%
- [ ] Backup harian 02:00 berjalan (cek `storage/app/private/maintenance-backups/`)
- [ ] 2FA admin aktif + recovery codes tersimpan
