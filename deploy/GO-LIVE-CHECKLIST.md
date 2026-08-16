# ✅ HRConnect — Checklist Go-Live Terverifikasi

> **Sumber:** `deploy/DEPLOY-GUIDE.md` (alur) + `deploy/ROTATION-RUNBOOK.md` (rotasi secret).
> **Status verifikasi:** setiap item di bawah telah dicek **langsung ke kode** (artisan command, config,
> seeder guard, route, scheduler) pada **2026-08-16** — lihat [Lampiran A](#lampiran-a-bukti-verifikasi-kode-2026-08-16).
> Item yang terverifikasi ditandai **[T]**; item yang hanya bisa diverifikasi **di server** ditandai **[S]**.

**Cara pakai:** jalankan urut per fase. Setiap baris = 1 aksi + 1 verifikasi. Jangan lompat.
**Sebelum mulai:** simpan semua nilai secret baru di password manager, jangan pernah di commit/log/chat.

---

## Fase 0 — Di laptop (sekali)

- [ ] **[S]** Repo GitHub **PRIVATE** (Settings → Danger Zone). 🔴 History berisi kredensial lama
      (commit `6a24cc4`) — jangan publikasikan sampai rotasi selesai.
- [ ] **[S]** Commit + push semua perubahan (`git push` ke branch `main` produksi).

## Fase 1 — VPS (sekali, ±30 menit)

- [ ] **[S]** VPS ≥ 2 vCPU / 4 GB RAM / 40 GB, Ubuntu 22.04/24.04 LTS. Buka port 22/80/443.
- [ ] **[S]** Paket dasar: `nginx supervisor certbot python3-certbot-nginx php8.5-{cli,fpm,pgsql,mbstring,xml,zip,intl,bcmath,gd,curl} postgresql postgresql-contrib composer unzip git curl` (repo default Ubuntu; bila `php8.5-*` tidak ada → PPA `ondrej/php`).
- [ ] **[S]** Domain + A record → IP server (HTTPS wajib untuk kamera/GPS absensi).

## Fase 2 — PostgreSQL (sekali)

- [ ] **[T]** Extension di **`template1`** (WAJIB — setiap DB baru termasuk temp drill otomatis dapat extension):
      ```bash
      sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS vector;" template1
      sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;" template1
      sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;" template1
      ```
- [ ] **[S]** User & DB: `hrconnect_app` (owner `hrconnect`) + `drill_hrconnect` (**CREATEDB, bukan superuser** — dipakai `maintenance:backup-restore-drill`, terverifikasi di deskripsi command).
- [ ] **[S]** 🔴 `pg_hba.conf` → **`scram-sha-256`** untuk semua koneksi (BUKAN `trust`/`md5`) + `SHOW password_encryption;` = `scram-sha-256`. (Runbook §1: dengan `trust`, rotasi password tidak ada efeknya.)
- [ ] **[S]** Verifikasi login: `PGPASSWORD='...' psql -h 127.0.0.1 -U hrconnect_app -d hrconnect -c 'SELECT 1;'`

## Fase 3 — Deploy kode (pertama kali)

- [ ] **[T]** Clone ke `/var/www/hrconnect`, lalu `cp .env.production.example .env` (template terverifikasi: berisi semua placeholder `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SCHEDULE_QUEUE_WORKER=false`, `DB_DRILL_*`, `BACKUP_ARCHIVE_PASSWORD`, `CIPHERSWEET_KEY` 64-hex, `GEMINI_API_KEY`, `MAIL_PASSWORD`).
- [ ] **[T]** `php artisan key:generate --force` (command ada) + `php artisan ciphersweet:generate-key` (command ada) → paste ke `CIPHERSWEET_KEY`.
- [ ] **[T]** `composer install --no-dev --optimize-autoloader` + `npm ci && npm run build`.
- [ ] **[T]** Migrate + seed (urutan aman — `DatabaseSeeder` sudah guard `app()->isProduction()` untuk seeder demo, terverifikasi):
      ```bash
      php artisan migrate --force
      php artisan db:seed --force                       # master data saja di produksi (role, TER, BPJS, wilayah, dll)
      php artisan db:seed --class=KnowledgeBaseSeeder --force   # KB + embedding 768D nyata — RAG mati tanpa ini
      ```
      > ⚠️ JANGAN `db:seed` penuh di produksi untuk user demo — gunakan seeder spesifik (`SuperAdminSeeder`, `KnowledgeBaseSeeder`).
      > **VPS demo skripsi:** set `SEED_DEMO=true` di .env (opt-in, default off) lalu `SEED_DEMO=true SEED_YEAR_ONE=true php artisan db:seed --force` untuk demo 1 tahun penuh. Verifikasi guard: `php scripts/verify-seeder-prod-dryrun.php` (skip) / `--demo` (bypass bekerja).
- [ ] **[T]** Cache: `config:cache && route:cache && view:cache && event:cache` + `composer dump-autoload --optimize`.

## Fase 4 — Nginx + HTTPS (WAJIB sebelum traffic)

- [ ] **[T]** `deploy/nginx/hrconnect-ssl.conf` ada → copy ke `/etc/nginx/sites-available/hrconnect`, sesuaikan domain/path, `nginx -t && systemctl reload nginx`.
- [ ] **[S]** `sudo certbot --nginx -d YOUR_DOMAIN -d www.YOUR_DOMAIN` (auto-renew via certbot.timer).
- [ ] **[S]** `.env`: `APP_URL=https://YOUR_DOMAIN` + `SESSION_SECURE_COOKIE=true` → `php artisan config:cache`.
- [ ] **[S]** Verifikasi HTTPS: `curl -sI http://...` → 301 https · `curl -sI https://...` → HSTS · `curl -s https://.../tidak-ada | grep -c 'Whoops'` → `0`.

## Fase 5 — Supervisor + Scheduler (WAJIB)

- [ ] **[T]** Queue worker: `deploy/supervisor/hrconnect-worker.conf` ada (command `queue:work database --queue=maintenance,default --sleep=3 --tries=3 --timeout=90 --max-time=3600`, numprocs=2) → copy ke `/etc/supervisor/conf.d/`, sesuaikan path/user, `supervisorctl reread && update && status` → **RUNNING**.
- [ ] **[T]** Scheduler: satu baris crontab → `* * * * * cd /var/www/hrconnect && php artisan schedule:run >> /dev/null 2>&1`. Jadwal terverifikasi di `routes/console.php`: **backup 02:00** (`maintenance:scheduled-backups`), deteksi missed-clock 00:15, auto-approve WFA 06:30, reminder 09:00 (weekdays), prune import-export hourly, deteksi alpha 20:00, chronic-late 23:30, cache:warm hourly, reset quota cuti 1 Jan, heartbeat scheduler+queue tiap menit.
- [ ] **[T]** `.env` `SCHEDULE_QUEUE_WORKER=false` (template sudah) — hindari double-consume queue (worker supervisor + drain scheduler).
- [ ] **[S]** Reverb: **OPSIONAL** — hanya jika `BROADCAST_CONNECTION=reverb` (default `log` = realtime nonaktif). Jika dipakai: `deploy/supervisor/hrconnect-reverb.conf` + proxy Nginx ke 127.0.0.1:8080.

## Fase 6 — Rotasi secret (🔴 WAJIB — `deploy/ROTATION-RUNBOOK.md`)

> Jalankan saat maintenance window (`php artisan down`). Urutan edit `.env` → `config:clear && config:cache && route:cache` → reload php-fpm → restart supervisor — kalau urutan salah, nilai lama tetap terbaca.

- [ ] **[T]** **`DB_PASSWORD`** (ada di history `6a24cc4`): `ALTER USER "hrconnect_app" WITH PASSWORD '<baru>'` + `sed` di `.env`. **Verifikasi tahan-trust:** hash `rolpassword` di `pg_authid` BERUBAH sebelum/sesudah + `php artisan tinker --execute="echo DB::connection()->getPdo() ? 'DB OK' : 'FAIL';"`. Backup/drill otomatis pakai password baru (`.pgpass` dinamis — `SystemBackupService`, terverifikasi).
- [ ] **[T]** **`APP_KEY`** (dev di history): simpan key lama → tambah `APP_PREVIOUS_KEYS=<key-lama>` di `.env` (Laravel decrypt key lama) → `php artisan key:generate --force`. Verifikasi: login ulang OK + data terenkripsi lama terbaca (`integration_clients` render OK).
- [ ] **[T]** **`MAIL_PASSWORD`** (di history): rotasi di provider email.
- [ ] **[S]** **`GEMINI_API_KEY`**: key produksi (`GEMINI_API_KEY`; `GOOGLE_AI_API_KEY` kosongkan) + cost alert. AI RAG mati tanpa ini.
- [ ] **[T]** **`BACKUP_ARCHIVE_PASSWORD`**: isi kuat 32+ char (dibaca `config/backup.php`).
- [ ] **[T]** **`CIPHERSWEET_KEY`**: generate baru 64-hex (`ciphersweet:generate-key`) — jangan pakai nilai dev `0123...`; bila sudah ada data terenkripsi → re-encrypt via `ciphersweet:encrypt` (pola SECURITY-CHECKLIST 1.1).
- [ ] **[T]** **`SUPER_ADMIN_PASSWORD`** (+`SUPER_ADMIN_EMAIL`): `php artisan db:seed --class=Database\Seeders\SuperAdminSeeder --force` — seeder **throw** `RuntimeException` bila password kosong (guard terverifikasi di kode), idempotent (`updateOrCreate`).
- [ ] **[S]** **2FA admin**: login super admin → `/user/profile` → Two Factor Authentication → Enable → scan QR → Confirm → **simpan recovery codes** → uji logout/login OTP + 1 recovery code.

## Fase 7 — Verifikasi final pasca-deploy

- [ ] **[T]** Health: `curl -s https://YOUR_DOMAIN/api/v1/health | jq .` → `"status": "ok"` (HTTP 200; 503 = degraded). Cek komponen: database, cache, **queue** (tabel jobs), storage, **backup freshness** (maks 26h via `BACKUP_MAX_AGE_HOURS`, default 26 — terverifikasi `config/backup.php`).
- [ ] **[T]** Backup drill (RTO): `php artisan maintenance:backup-restore-drill` → `RESTORE DRILL PASSED` (butuh `DB_DRILL_*` + extension di template1).
- [ ] **[T]** AI KB gate: `php artisan kb:eval` → relevansi **≥ 90%** (PRD §6; command ada, eval dataset ≥ 20 Q&A).
- [ ] **[S]** Smoke UI: login admin → dashboard · Knowledge Base → "apa itu cuti tahunan?" → jawaban AI + citation · `/scan` → flow absen (kamera butuh HTTPS).
- [ ] **[T]** 🔒 E2E/route dev mati di produksi: `curl -s -o /dev/null -w '%{http_code}' https://YOUR_DOMAIN/__e2e-login` → **404** (guard `abort(404)` di luar `local/testing` — terverifikasi di `E2eLoginController` + `E2eDocumentUploadController`). Sama untuk `test-error`, `__auth-debug`, `_boost/browser-logs`.
- [ ] **[T]** Guard seeder demo: `php scripts/verify-seeder-prod-dryrun.php` (ada di repo) → exit 0.
- [ ] **[S]** `php artisan about | grep -E 'Environment|Debug'` → `production` / `false`.
- [ ] **[S]** Backup harian: cek `storage/app/private/maintenance-backups/` ada file hari ini + `supervisorctl status hrconnect-worker:` RUNNING.
- [ ] **[S]** Deploy ulang berikutnya: `bash scripts/deploy.sh` (git pull → composer → build → migrate → seed KB → cache → restart worker; `--fresh` hanya untuk install pertama).

---

## Lampiran A — Bukti Verifikasi Kode (2026-08-16)

Semua dicek langsung di source tree (bukan dari dokumen):

| Klaim | Bukti |
|---|---|
| `maintenance:scheduled-backups`, `maintenance:backup-restore-drill`, `kb:eval`, `ciphersweet:generate-key`, `ciphersweet:encrypt` | `php artisan list --raw` — semua ada |
| Scheduler backup 02:00 + 11 jadwal lain | `routes/console.php` (dibaca penuh) — commandnya semua ada di `app/Console/Commands/` |
| Health endpoint | `routes/api.php:53` → `HealthController` (database, cache, queue, storage, backup freshness ≤26h, 200/503) |
| Backup retention 14 + archive password | `config/backup.php:28` (`BACKUP_RETENTION_DAYS` default 14), `:106` (`BACKUP_ARCHIVE_PASSWORD`), `:43` (`health_backup_max_age_hours` default 26) |
| `DatabaseSeeder` aman di produksi | guard `if (! app()->isProduction())` untuk 4 seeder demo — dibaca langsung |
| `SuperAdminSeeder` guard | throw `RuntimeException` bila `SUPER_ADMIN_PASSWORD` kosong; baca `config('hrconnect.super_admin_*')` |
| E2E routes 404 di produksi | `E2eLoginController` + `E2eDocumentUploadController` → `abort(404)` di luar `local/testing`; **2026-08-16 ditutup total**: `_boost/browser-logs` (sebelumnya tanpa guard), `test-error/{code}` + `__auth-debug` (sebelumnya bocor saat `APP_DEBUG=true`) kini env-only — regression test `tests/Feature/SystemDevRoutesGuardTest` 3/3 |
| Supervisor conf + template env | `deploy/supervisor/hrconnect-worker.conf` (queue:work maintenance,default, numprocs 2), `.env.production.example` (semua placeholder + checklist komentar §1–§9) |
| `deploy.sh` | `scripts/deploy.sh` — pull → composer → build → migrate → seed KB → cache → restart worker; flag `--fresh`/`--no-build` |
| Regresi terakhir | Full suite 2026-08-16: **916 passed / 0 failed / 1 skipped** (12.733 assertions) + pint lint pass |

## Lampiran B — Catatan non-blocking

- **Sqlite portability smoke pre-existing broken** (`ALTER TABLE ADD CONSTRAINT` tidak didukung SQLite) — stack resmi PostgreSQL-only, tidak relevan untuk production; ditandai `KNOWN LIMITATION` di `scripts/database-portability-smoke.sh`.
- **`services.e2e.login_token`** tidak terdefinisi di `config/services.php` → selalu fallback `local-apk-e2e`, tapi **tidak relevan** karena route e2e env-gated (404 di produksi). Opsional: definisikan token random di config untuk defense-in-depth.
- Alur mysql di `database-portability-smoke.sh` belum diverifikasi (tidak relevan — production pakai pgsql).
- `AUDIT.md` gate produksi terakhir ditutup 2026-08-08 (0 open P0/P1); seluruh lane fix setelahnya regression-verified (916/0/1).
