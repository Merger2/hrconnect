# 🔐 Runbook Rotasi Secret — Go-Live HRConnect

Checklist eksekusi di **server produksi** untuk menutup item **1.2 (DB_PASSWORD), 1.3 (APP_KEY), 1.4 (SUPER_ADMIN_PASSWORD + 2FA)** di `docs/SECURITY-CHECKLIST.md`.

> ⚠️ **Jalankan saat maintenance window** (`php artisan down`) — rotasi APP_KEY menonaktifkan semua session (semua user logout).
> ⚠️ **Jangan pernah** menampilkan/commit nilai secret ke repo, log, atau chat. Simpan semua nilai baru di password manager.

---

## 0. Prasyarat & Safety

```bash
cd /var/www/hrconnect

# 1. Maintenance mode (selama window rotasi)
php artisan down

# 2. Backup .env lama (permission 600)
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)
chmod 600 .env.backup-*

# 3. Generate password kuat (jangan pakai manual — pakai random)
openssl rand -base64 24        # untuk DB_PASSWORD / SUPER_ADMIN_PASSWORD
# APP_KEY pakai `php artisan key:generate --force` (bukan manual)

# 4. Catat semua nilai baru di password manager SEKARANG sebelum eksekusi
```

**Urutan wajib setelah semua edit `.env`** (config:cache membekukan env — urutan salah = nilai lama tetap terbaca):

```bash
php artisan config:clear && php artisan config:cache && php artisan route:cache
sudo systemctl reload php8.5-fpm        # sesuaikan versi PHP
sudo supervisorctl restart hrconnect-worker:   # queue worker (deploy/supervisor/hrconnect-worker.conf)
```

---

## 1. 🔴 DB_PASSWORD (Postgres) — SECURITY-CHECKLIST 1.2

Latar: password asli pernah ada di history commit `6a24cc4` (repo private). Wajib dirotasi.

```bash
# 1a. Baca user DB dari .env (jangan print password lama)
DB_USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2)

# 1b. Generate password baru
NEW_DB_PASS=$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)   # aman untuk URI
# simpan $NEW_DB_PASS di password manager

# 1c. Rotasi di PostgreSQL
sudo -u postgres psql -c "ALTER USER \"$DB_USER\" WITH PASSWORD '$NEW_DB_PASS';"

# 1d. Update .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$NEW_DB_PASS|" .env
```

**Catatan backup/drill**: `SystemBackupService` membangun `.pgpass` dinamis dari config saat runtime → **tidak ada file terpisah yang perlu diupdate**. Setelah `.env` baru + `config:cache`, backup harian 02:00 & drill otomatis pakai password baru.

**⚠️ Wajib: `pg_hba.conf` produksi harus `scram-sha-256`, BUKAN `trust`.**
Validasi dry-run di dev (2026-08-12) membuktikan: dengan `trust`, koneksi pakai password **lama** tetap berhasil — password tidak pernah dicek → rotasi tanpa efek. Sebelum go-live, pastikan:
```
# /var/lib/postgres/data/pg_hba.conf (produksi) — contoh:
local   all   all                                scram-sha-256
host    all   all   127.0.0.1/32   scram-sha-256
host    all   all   ::1/128        scram-sha-256
```
Lalu `SELECT pg_reload_conf();` + `SHOW password_encryption;` → `scram-sha-256`.

**Verifikasi** (tahan-trust — jangan andalkan coba-connect):
```bash
# 1. Hash rol berubah setelah ALTER USER (bukti password benar-benar diganti):
sudo -u postgres psql -tAc "SELECT rolpassword FROM pg_authid WHERE rolname='<user>';"   # catat SEBELUM
# ... jalankan ALTER USER ...
sudo -u postgres psql -tAc "SELECT rolpassword FROM pg_authid WHERE rolname='<user>';"   # harus BERUBAH sesudah

# 2. App membaca kredensial baru:
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'DB OK' : 'FAIL';"
# + login app normal
# + jalankan drill restore (menguji backup pipeline + kredensial):
php artisan maintenance:backup-restore-drill
```

**Rollback**: `ALTER USER ... WITH PASSWORD '<password-lama>'` + restore `.env.backup-*`.

---

## 2. 🔴 APP_KEY — SECURITY-CHECKLIST 1.3

Latar: key dev ada di history (28 commit). ⚠️ APP_KEY meng-encrypt **session cookie + data `Crypt::encryptString`** (mis. `integration_clients.secret_encrypted`). Rotasi tanpa persiapan = data terenkripsi lama tidak terbaca.

```bash
# 2a. Simpan APP_KEY LAMA dulu (krusial — urutan ini WAJIB)
OLD_KEY=$(grep '^APP_KEY=' .env | cut -d= -f2)
# simpan $OLD_KEY di password manager

# 2b. Daftarkan key lama sebagai previous key (Laravel 11+ mencoba key lama utk decrypt)
#     Tambahkan ke .env DI BAWAH baris APP_KEY:
#     APP_PREVIOUS_KEYS=<OLD_KEY>     ← nilai persis dari langkah 2a

# 2c. Generate + tulis key baru
php artisan key:generate --force
```

**Verifikasi:**
```bash
# + login ulang berhasil (session baru)
# + data terenkripsi lama masih terbaca (contoh: integration client render OK):
php artisan tinker --execute="echo App\Models\IntegrationClient::first()?->exists ? 'enc-read OK' : 'n/a';"
```

**Rollback**: restore `APP_KEY` lama ke `.env` + hapus `APP_PREVIOUS_KEYS` → `config:clear` → `config:cache` → restart php-fpm.

---

## 3. 🔴 SUPER_ADMIN_PASSWORD — SECURITY-CHECKLIST 1.4

Latar: `.env` kosong → `SuperAdminSeeder` **throw** kalau kosong (guard bawaan). Akun `admin@hrconnect.local` sudah dirotasi password-nya di dev (24-char); langkah ini menyiapkan password untuk **seed pertama di production**.

```bash
# 3a. Generate + set di .env (3 var di bawah — cek SUPER_ADMIN_EMAIL benar)
#     SUPER_ADMIN_PASSWORD=<password-kuat-24+char>
#     SUPER_ADMIN_EMAIL=admin@your-domain.com
#     SUPER_ADMIN_NAME=Super Admin

# 3b. Seed akun super admin (updateOrCreate — idempotent, aman dijalankan ulang)
php artisan db:seed --class=Database\Seeders\SuperAdminSeeder --force
```

> ⚠️ **Jangan** jalankan `db:seed` penuh di production — seeder demo sudah di-guard `app()->isProduction()`, dan master data tidak perlu di-seed ulang (gunakan seeder spesifik saja: `SuperAdminSeeder`, `KnowledgeBaseSeeder`).

**Verifikasi**: login dengan `SUPER_ADMIN_EMAIL` + password baru → dashboard admin OK.

---

## 4. 🟠 Aktifkan 2FA di Akun Super Admin

Latar: item 1.4 menuntut 2FA aktif (alur 2FA sudah diaudit & berfungsi penuh — commit `d1ab7dc`).

1. Login sebagai super admin → buka **`/user/profile`**
2. Bagian **Two Factor Authentication** → klik **Enable**
3. Konfirmasi password di modal (Jetstream) → muncul QR code
4. Scan QR dengan authenticator app (Google Authenticator / Aegis / 1Password)
5. Ketik kode 6 digit → **Confirm**
6. **Simpan recovery codes** (ditampilkan sekali) ke password manager — wajib untuk recovery bila HP hilang
7. Uji: logout → login → challenge OTP → sukses · uji 1 recovery code

---

## 5. ✅ Verifikasi Akhir & Bersih

```bash
# Env production & debug mati (APP_DEBUG=false — item 2.2)
php artisan about | grep -E 'Environment|Debug'

# Health endpoint (bila route health ada) → HTTP 200
curl -s -o /dev/null -w '%{http_code}' https://your-domain.com/up

# Scheduler & queue hidup
php artisan schedule:list
sudo supervisorctl status hrconnect-worker:

# Backup pipeline sehat (RPO/RTO teruji)
php artisan maintenance:backup-restore-drill

# Guard seeder demo terverifikasi (0 baris — tools ini di repo)
php scripts/verify-seeder-prod-dryrun.php

# Bersihkan backup .env dari server setelah stabil
# (atau pindah ke lokasi aman 0600 — jangan biarkan di webroot)
```

**Checklist ringkas (tempel ke ticket go-live):**
- [ ] `DB_PASSWORD` dirotasi (ALTER USER + .env + restart) — verifikasi hash rol berubah + `DB OK` + drill pass
- [ ] `pg_hba.conf` produksi `scram-sha-256` (BUKAN trust) + `password_encryption=scram-sha-256`
- [ ] `APP_KEY` dirotasi + `APP_PREVIOUS_KEYS` key lama — verifikasi login + decrypt
- [ ] `SUPER_ADMIN_PASSWORD` di-set + `SuperAdminSeeder` — login admin OK
- [ ] 2FA aktif di akun admin + recovery codes tersimpan
- [ ] `config:cache` + `route:cache` + `view:cache` + restart php-fpm/queue
- [ ] Health 200 · queue OK · backup drill OK · `verify-seeder-prod-dryrun` exit 0
- [ ] Update `docs/SECURITY-CHECKLIST.md`: 1.2/1.3/1.4 → ☑

---

## Referensi

- `docs/SECURITY-CHECKLIST.md` — item 1.1–1.12 (kebijakan + latar)
- `deploy/README.md` — install server + checklist HTTPS + `config:cache` urutan
- `deploy/supervisor/hrconnect-worker.conf` — queue worker
- `scripts/verify-seeder-prod-dryrun.php` — dry-run guard seeder demo (exit 0 = aman)
- Rotasi `CIPHERSWEET_KEY` (item 1.1, sudah selesai di dev): production harus generate key **sendiri** + re-encrypt data bila sudah ada — ikuti pola `ciphersweet:encrypt` (lihat catatan di SECURITY-CHECKLIST 1.1).
