# Operations Readiness — HRConnect

> Status: **O-1 through O-7 audited and remediated**  
> Date: 2026-06-19  
> Audit scope: Queue, Scheduler, Cache, Storage, Health, Backup, Deployment

---

## O-1: Queue

**Status: ✅ FIXED (HIGH)**

| Check | Finding | Action |
|-------|---------|--------|
| Supervisor config | Was missing `--queue=default,payroll_high,notifications` | Updated `docs/deployment/deployment-guide.md` line 269 |
| Queue priority | `GenerateEmployeePayrollJob` uses `public string $queue = 'payroll_high'` | Works correctly via job-level property |
| Retry-until logged | Not documented in deployment guide | **LOW**: add retry-until to guide on next update |
| Failed-job alert | No sentry/flare configured | **LOW**: consider `filament/filament` or `oh-dear` for production |
| Jobs dispatched | 9 queues dispatch across 3 routes (payroll, kb-embed, notifications) | All verified |

**Command:** `queue:work --queue=default,payroll_high,notifications --sleep=3 --tries=3 --max-time=3600`

---

## O-2: Scheduler

**Status: ✅ FIXED (HIGH)**

| Check | Finding | Action |
|-------|---------|--------|
| Environment guard | 0/6 commands had `->environments(['production'])` | Added to all 6 commands in `routes/console.php` |
| Output appending | 0/6 commands logged to file | Added `->appendOutputTo(storage_path('logs/schedule.log'))` to all 6 |
| Run in background | `attendance:detect-alpha` is heaviest (parses all attendances) | Added `->runInBackground()` |
| Without overlapping | All 6 had it | Already correct |
| Success/failure logging | 4/6 had onSuccess, 5/6 had onFailure | Added missing ones |

**Scheduled commands (6):**
| Command | Schedule | Notes |
|---------|----------|-------|
| `attendance:detect-alpha` | Daily 23:59 | runInBackground, heaviest |
| `attendance:detect-chronic-late` | Fri 18:00 | — |
| `leave:reset-quota` | Jan 1 00:00 | No onSuccess (yearly, low traffic) |
| `cache:warm` | Daily 05:00 | — |
| `attendance:auto-approve-wfa` | Daily 02:00 | — |
| `attendance:detect-missed-clock` | Daily 00:01 | — |
| `attendance:send-reminders` | Weekdays 09:00 | — |

**Crontab entry:**
```
* * * * * cd /var/www/hrconnect && php artisan schedule:run >> /dev/null 2>&1
```

---

## O-3: Cache

**Status: ✅ FIXED (MEDIUM)**

| Check | Finding | Action |
|-------|---------|--------|
| `CompanySetting` cache invalidation | Missing observer | Created `CompanySettingObserver` + registered in `AppServiceProvider` |
| Other observers exist | TaxConfig, BpjsConfig, Holiday, Employee, Attendance, Leave, Payroll | All verified |
| `CACHE_STORE=database` | `Cache::tags()` throws `BadMethodCallException` | Documented in AGENTS.md |
| Cache warm command | `cache:warm` runs daily 05:00 | Verified |

**All observers:**
| Model | Observer | Cache Key(s) |
|-------|----------|-------------|
| CompanySetting | `CompanySettingObserver` | `settings:{key}` |
| TaxConfig | `TaxConfigObserver` | `tax_configs` |
| BpjsConfig | `BpjsConfigObserver` | `bpjs_configs` |
| Holiday | `HolidayObserver` | `holidays:{year}` |
| Employee | `EmployeeObserver` | `employee:{id}`, `employee_pii:{id}` |
| Attendance | `AttendanceObserver` | `attendance:{employee_id}:{date}` |
| Leave | `LeaveObserver` | `leave_quota:{employee_id}:{year}` |
| Payroll | `PayrollObserver` | `payroll:{id}`, `payroll_{status}:*` |

**LOW remaining:** No TTL audit policy; no cache hit-ratio monitoring.

---

## O-4: Storage

**Status: ✅ AUDITED (LOW)**

| Check | Finding | Action |
|-------|---------|--------|
| S3 config | `config/filesystems.php` has S3 disk with `env()` values | Configured correctly |
| .env.example | AWS credentials are empty | Documented in deployment guide (fill before prod) |
| `throw => false` | Both `local` and `s3` have `report => false` | Intentional — prevents 500 on temporary IO failures |
| File upload validation | 3 endpoints (KB + leave proof + reimbursement receipt) | Validated with size/type/mime checks |
| Orphan cleanup | All 3 uploads wrapped in try/catch + orphan cleanup | **S-7 fix applied** |
| Public disk | `public/storage` -> `storage/app/public` symlink | Documented in deployment guide |

**LOW remaining:** Document S3 migration path (currently all storage is local).

---

## O-5: Health Endpoint

**Status: ✅ AUDITED (LOW)**

| Check | Finding | Action |
|-------|---------|--------|
| Route | `GET /api/v1/health` | No changes needed |
| Services checked | database, cache, queue, storage | All 4 covered |
| Response format | `{ status, timestamp, version, environment, services }` | Consistent with API contract |
| HTTP status | 200 if all up, 503 if any down | Correct |

**Controller:** `app/Http/Controllers/Api/HealthController.php` (invokable)

**LOW remaining:** No pgvector extension check (application-specific dependency).

---

## O-6: Backup

**Status: ✅ SETUP (MEDIUM)**

| Check | Finding | Action |
|-------|---------|--------|
| Backup package | `spatie/laravel-backup` v10.3 installed | Done |
| Config | `config/backup.php` customized for HRConnect | Done |
| Files included | `base_path()` + `storage_path()` (excludes vendor/node_modules/.git/framework/backup-temp/backups) | Done |
| Database | `pgsql` connection with Gzip compression | Done |
| Destination disk | `backups` disk at `storage/app/backups` (configurable via `BACKUP_DISK` env) | Done |
| Off-site optional | Set `BACKUP_DISK=s3` with AWS creds for off-site storage | Documented |
| Encryption | AES-256 via `BACKUP_ARCHIVE_PASSWORD` env var | Done |
| Archive verify | Enabled (`verify_backup => true`) | Done |
| Cleanup retention | 7 days all, 16 daily, 8 weekly, 4 monthly, 2 yearly | Done |
| Health monitoring | Max age 1 day, max size 5 GB | Done |
| Schedule | `backup:clean` daily 01:00, `backup:run` daily 01:30 | Done |
| Retry | 2 tries, 60s delay between attempts | Done |

**Composer package:** `spatie/laravel-backup` v10.3

**Scheduled:**
| Command | Schedule |
|---------|----------|
| `backup:clean` | Daily 01:00 |
| `backup:run` | Daily 01:30 |

**Env vars:**
```
BACKUP_DISK=backups               # or 's3' for off-site
BACKUP_ARCHIVE_PASSWORD=          # 32+ char AES-256 password
```

**Restore drill:** Not yet performed. Documented procedure below.

### Backup Restore Procedure

1. List available backups: `php artisan backup:list`
2. Restore database: `pg_restore -h <host> -U hrconnect -d hrconnect --clean --if-exists <backup-path>/db-dumps/pgsql-hrconnect-*.sql.gz`
3. Restore files: `tar -xzf <backup-path>/<name>.zip -C /var/www/hrconnect`
4. Verify: `php artisan cache:clear && php artisan config:cache && php artisan migrate --status`
5. Smoke test: `curl -s https://hrconnect.example.com/api/v1/health | jq .`

### Off-Site (S3) Setup

```bash
# In .env.production:
BACKUP_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=hrconnect-backups
```

---

## O-7: Deployment

**Status: ✅ AUDITED (LOW)**

| Check | Finding | Action |
|-------|---------|--------|
| Deployment guide | Comprehensive, 514 lines, 11 sections | No changes needed |
| Supervisor queue | Was missing `--queue` flag | **FIXED** (line 269) |
| Config caching | `config:cache`, `route:cache`, `view:cache` documented | Verified |
| Rollback plan | Documented (git checkout + migration rollback + cache clear) | Verified |
| Deployment checklist | 7 pre-deploy + 12 deploy + 11 post-deploy items | Verified |
| VPS requirements | 2 cores / 2 GB min, Ubuntu 22.04/24.04 | Documented |
| SSL | Let's Encrypt via Certbot | Documented |

**File:** `docs/deployment/deployment-guide.md`

---

## Summary

| Item | Status | Severity | Action |
|------|--------|----------|--------|
| O-1 Queue supervisor config | ✅ Fixed | HIGH | Added `--queue` flag |
| O-2 Scheduler environment guard | ✅ Fixed | HIGH | Added `->environments(['production'])` + log output to all 6 |
| O-3 CompanySettingObserver | ✅ Fixed | MEDIUM | Created + registered observer |
| O-4 Storage audit | ✅ Audited | LOW | No changes needed |
| O-5 Health endpoint | ✅ Audited | LOW | No changes needed |
| O-6 Backup | ✅ Setup | MEDIUM | Installed `spatie/laravel-backup`, configured, scheduled |
| O-7 Deployment guide | ✅ Audited | LOW | Supervisor queue fix applied |
