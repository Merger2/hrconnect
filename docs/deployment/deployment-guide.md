# HRConnect - Deployment Guide

> **Dokumen ini berisi panduan deployment step-by-step untuk HRConnect.**
> Ikuti setiap langkah secara berurutan.

---

## 1. ARCHITECTURE OVERVIEW

```
┌──────────────┐     HTTPS      ┌─────────────────┐
│   PWA Client │ ◄────────────► │                 │
│   (Mobile)   │                │   VPS Server    │
└──────────────┘                │   (Cloud/Vultr) │
                                │                 │
┌──────────────┐     HTTPS      │  ┌───────────┐  │
│ Desktop Browser│ ◄──────────► │  │  Nginx    │  │
│   (Admin)    │                │  └─────┬─────┘  │
└──────────────┘                │        │        │
                                │  ┌─────▼─────┐  │
┌──────────────┐                │  │  PHP-FPM  │  │
│ SMTP Mail    │ ◄────────────► │  │  Laravel  │  │
└──────────────┘                │  └─────┬─────┘  │
                                │        │        │
┌──────────────┐                │  ┌─────▼─────┐  │
│ Gemini API   │ ◄────────────► │  │  Queue    │  │
└──────────────┘                │  │  Worker   │  │
                                │  └─────┬─────┘  │
┌──────────────┐                │        │        │
│ Gemini API   │ ◄────────────► │  ┌─────▼─────┐  │
│ (Embedding + │                │  │  Queue    │  │
│  2.5 Flash)  │                │  │  Worker   │  │
└──────────────┘                │  │ (database)│  │
                                │  └─────┬─────┘  │
                                │        │        │
                                │  ┌─────▼─────┐  │
                                │  │ PostgreSQL│  │
                                │  │  (Neon)   │  │
                                │  └───────────┘  │
                                └─────────────────┘
```

---

## 2. INFRASTRUCTURE REQUIREMENTS

### 2.1 VPS Specifications
| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 cores | 4 cores |
| RAM | 2 GB | 4 GB |
| Storage | 25 GB SSD | 50 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| Provider | Vultr, DigitalOcean | Cloud provider with auto-scaling |

### 2.2 Database (Neon PostgreSQL)
| Component | Specification |
|-----------|--------------|
| Provider | Neon.tech (Serverless) |
| Version | PostgreSQL 15+ |
| Extensions | pgvector, pg_trgm, pgcrypto |
| Storage | Auto-scaling |
| Backup | Automatic daily |
| SSL | Required |

### 2.3 External Services
| Service | Purpose | Cost |
|---------|---------|------|
| Google Cloud Console | OAuth authentication | Free |
| Gemini Embedding API | Text embeddings (text-embedding-004, 768D) | Free tier available |
| Gemini 2.5 Flash API | LLM chat (RAG) | Free tier available |
| Mail Service | Email notifications | ~$10/bulan (Mailgun) |

---

## 3. SERVER SETUP

### 3.1 Initial Server Setup
```bash
# Login to VPS
ssh root@your-server-ip

# Update system
apt update && apt upgrade -y

# Install dependencies (NO Redis — cache & queue use database driver)
apt install -y php8.5 php8.5-fpm php8.5-cli php8.5-pgsql php8.5-mbstring \
    php8.5-xml php8.5-curl php8.5-zip php8.5-gd php8.5-sodium \
    php8.5-bcmath php8.5-intl php8.5-fileinfo \
    nginx supervisor git curl unzip composer nodejs npm

# Enable PHP-FPM
systemctl enable php8.5-fpm
systemctl start php8.5-fpm
```

### 3.2 PHP Configuration
```bash
# Edit php.ini
nano /etc/php/8.5/fpm/php.ini

# Set values
upload_max_filesize = 20M
post_max_size = 20M
memory_limit = 256M
max_execution_time = 120
max_input_time = 120

# Restart PHP-FPM
systemctl restart php8.5-fpm
```

### 3.3 Nginx Configuration

File lengkap: `docs/deployment/nginx.conf`

```bash
# Install default nginx config
cp docs/deployment/nginx.conf /etc/nginx/sites-available/hrconnect
ln -s /etc/nginx/sites-available/hrconnect /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

> ⚠️ **Catatan:** Security headers (CSP, HSTS, XFO) di-handle oleh Laravel middleware `EnsureSecurityHeaders`. Nginx hanya handle asset caching dan PWA routing. Jangan duplikasi header yang bisa menyebabkan konflik.

### 3.4 SSL with Let's Encrypt
```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Get SSL certificate
certbot --nginx -d hrconnect.company.com

# Auto-renewal (already configured by certbot)
crontab -l | grep certbot || echo "0 0 * * * certbot renew --quiet" | crontab -
```

---

## 4. APPLICATION DEPLOYMENT

### 4.1 Clone Repository
```bash
# Create directory
mkdir -p /var/www/hrconnect
cd /var/www/hrconnect

# Clone (or upload via SFTP)
git clone https://github.com/your-repo/hrconnect.git .
# OR upload manually

# Set permissions
chown -R www-data:www-data /var/www/hrconnect
chmod -R 755 /var/www/hrconnect/storage
chmod -R 755 /var/www/hrconnect/bootstrap/cache
```

### 4.2 Environment Setup
```bash
# Copy env file
cp .env.example .env

# Edit environment
nano .env

# Generate app key
php artisan key:generate

# Generate CipherSweet key
php artisan ciphersweet:generate-key
# Copy the key to .env CIPHERSWEET_KEY
```

### 4.3 Database Setup
```bash
# Install Sanctum (REQUIRED — not installed by default)
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations (includes personal_access_tokens from Sanctum)
php artisan migrate --force

# Seed database
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=PositionSeeder
php artisan db:seed --class=ShiftSeeder
php artisan db:seed --class=LeaveTypeSeeder
php artisan db:seed --class=HolidaySeeder
php artisan db:seed --class=CompanySettingSeeder
php artisan db:seed --class=EmployeeSeeder

# OR seed all at once
php artisan db:seed --force
```

### 4.4 Install Dependencies & Build
```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node dependencies
npm install

# Build assets
npm run build

# Link storage
php artisan storage:link

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4.5 Queue Worker Setup
```ini
; /etc/supervisor/conf.d/hrconnect-worker.conf
; Lihat docs/deployment/hrconnect-worker.conf untuk file lengkap

[program:hrconnect-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hrconnect/artisan queue:work --queue=default,payroll_high,notifications --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasuser=false
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hrconnect/storage/logs/worker.log
stopwaitsecs=3600

# Restart supervisor
supervisorctl reread
supervisorctl update
supervisorctl start hrconnect-worker:*
```

### 4.6 Scheduler Setup
```bash
# Cron job for Laravel scheduler
crontab -e

# Add this line
* * * * * cd /var/www/hrconnect && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. POST-DEPLOYMENT

### 5.1 Create Super Admin
```bash
# Run tinker
php artisan tinker

# Create user
>>> $user = \App\Models\User::create([
...     'name' => 'Super Admin',
...     'email' => 'admin@company.com',
...     'password' => bcrypt('secure-password-here'),
...     'email_verified_at' => now(),
...     'force_password_change' => true,
... ]);

>>> $user->assignRole('super-admin');

>>> exit
```

### 5.2 Verify Deployment
```bash
# Check routes
php artisan route:list

# Check config
php artisan config:show

# Check database
php artisan db:show

# Test queue
php artisan queue:work --once

# Test scheduler
php artisan schedule:run

# Check logs
tail -f storage/logs/laravel.log
```

### 5.3 PWA Setup
```bash
# Ensure PWA files are accessible
ls -la public/manifest.json
ls -la public/sw.js
ls -la public/icons/

# Test PWA installability
# Open https://hrconnect.company.com in Chrome
# Check Lighthouse PWA audit
```

---

## 6. MONITORING & MAINTENANCE

### 6.1 Log Monitoring
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View queue worker logs
tail -f storage/logs/worker.log

# View Nginx logs
tail -f /var/log/nginx/hrconnect-access.log
tail -f /var/log/nginx/hrconnect-error.log
```

### 6.2 Backup Strategy

HRConnect uses `spatie/laravel-backup` v10 for automated daily backups.

```bash
# Manual backup (test)
php artisan backup:run

# List backups
php artisan backup:list

# Check health of backups
php artisan backup:monitor
```

**Schedule (auto):**
| Command | Time |
|---------|------|
| `backup:clean` | Daily 01:00 |
| `backup:run` | Daily 01:30 |

**Included in backup:**
- Full PostgreSQL database dump (Gzip compressed)
- Application code + storage files
- Excluded: vendor, node_modules, .git, cache

**Encryption:** AES-256 archive encryption via `BACKUP_ARCHIVE_PASSWORD`.

**Retention:**
- All backups: 7 days
- Daily: 16 days
- Weekly: 8 weeks
- Monthly: 4 months
- Yearly: 2 years

**Restore procedure:**
```bash
# 1. Find the latest backup
ls -la storage/app/backups/

# 2. Restore database
#    2a. Extract DB dump from archive
#    2b. Run pg_restore
pg_restore -h <host> -U hrconnect -d hrconnect --clean --if-exists \
  <backup-path>/db-dumps/pgsql-hrconnect-*.sql

# 3. Restore files
#    Extract zip archive to application root

# 4. Post-restore
php artisan cache:clear
php artisan config:cache
php artisan migrate --status

# 5. Smoke test
curl -s https://hrconnect.company.com/api/v1/health | jq .
```

**Neon auto-backup:** Provider also takes daily snapshots (fallback).

**Off-site (S3):** Set `BACKUP_DISK=s3` in `.env` with valid AWS credentials.

### 6.3 Health Check
```bash
# Create health check endpoint
# routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::get('health_check') === null ? 'working' : 'error',
        'queue' => Cache::get('health_check') === null ? 'working' : 'error',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

---

## 7. DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All tests pass (`php artisan test`)
- [ ] Code formatted (`vendor/bin/pint`)
- [ ] No debug code left
- [ ] `.env.example` updated with new variables
- [ ] Database migrations tested locally
- [ ] Assets built (`npm run build`)
- [ ] PWA manifest valid

### Deployment
- [ ] Code uploaded to server
- [ ] `composer install --optimize-autoloader --no-dev`
- [ ] `npm install && npm run build`
- [ ] `.env` configured
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --force`
- [ ] `php artisan storage:link`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] Queue workers restarted
- [ ] Nginx reloaded
- [ ] SSL certificate valid

### Post-Deployment
- [ ] Application accessible via HTTPS
- [ ] Login works
- [ ] 2FA setup works
- [ ] Attendance clock in works
- [ ] Queue processing works
- [ ] Scheduler running
- [ ] PWA installable
- [ ] Email notifications sent
- [ ] Logs clean (no errors)
- [ ] Performance acceptable

---

## 8. ROLLBACK PLAN

### If Deployment Fails
```bash
# 1. Check logs
tail -f storage/logs/laravel.log

# 2. Rollback last migration
php artisan migrate:rollback --force

# 3. Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Restore previous code version
git checkout previous-tag

# 5. Re-run optimizations
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers
supervisorctl restart hrconnect-worker:*
```

---

## 9. ENVIRONMENT VARIABLES (Production)

```env
APP_NAME=HRConnect
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://hrconnect.company.com

DB_CONNECTION=pgsql
DB_HOST=ep-xxx.neon.tech
DB_PORT=5432
DB_DATABASE=hrconnect
DB_USERNAME=hrconnect
DB_PASSWORD=xxx
DB_SSLMODE=require

CIPHERSWEET_KEY=base64:...

# Cache & Queue: database driver (KISS, no Redis)
CACHE_STORE=database
QUEUE_CONNECTION=database

# Backup
BACKUP_DISK=backups
BACKUP_ARCHIVE_PASSWORD=xxx  # 32+ char AES-256 password

MAIL_MAILER=smtp
MAIL_HOST=smtp.company.com
MAIL_PORT=587
MAIL_USERNAME=hr@company.com
MAIL_PASSWORD=xxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hrconnect@company.com"
MAIL_FROM_NAME="${APP_NAME}"

# AI/RAG (Gemini for both embedding and LLM)
GEMINI_API_KEY=xxx
GEMINI_EMBEDDING_MODEL=text-embedding-004
GEMINI_MODEL=gemini-2.5-flash

FACE_THRESHOLD=0.85
GEOFENCE_DEFAULT_RADIUS=100
```

---

*Dokumen ini harus diikuti saat deployment.*
*Terakhir diupdate: 2026-05-31 — G2: Hapus Redis (cache & queue pakai database driver), G1: OpenAI→Gemini*
