# HRConnect Cybersecurity Roadmap

**Last Updated:** 2026-07-12  
**Risk Level:** HIGH (HRIS/Payroll — PII, salary data, access control)  
**Baseline:** Laravel 13 + Sanctum + PostgreSQL

---

## Executive Summary

HRConnect handles sensitive employee data (PII, salary, attendance, leave). Current security posture is **STRONG FUNDAMENTALS** with **KNOWN VULNERABILITIES** in dependencies. This roadmap prioritizes:

1. **P0 (CRITICAL):** Dependency patches + secrets rotation
2. **P1 (HIGH):** Input validation + access control audit
3. **P2 (MEDIUM):** Monitoring + logging + incident response
4. **P3 (LOW):** Advanced hardening + compliance

---

## Risk Assessment Matrix

| Area | Risk Level | Status | Action |
|------|-----------|--------|--------|
| **Dependencies** | 🔴 HIGH | Known CVEs (Guzzle, PSR-7, node-fetch) | Patch immediately (P0) |
| **Authentication** | 🟢 LOW | Sanctum + verified + expiration setup | Keep current (monitor renewal) |
| **Authorization** | 🟡 MEDIUM | Policies exist but need audit | Systematic review (P1) |
| **Input Validation** | 🟡 MEDIUM | Laravel validation present, check coverage | Static analysis (P1) |
| **Data Protection** | 🟡 MEDIUM | Encryption at rest/transit, but needs policy | Harden config (P1) |
| **Secrets Management** | 🟢 LOW | .env secured (600), but no rotation | Implement rotation (P2) |
| **Logging & Monitoring** | 🔴 HIGH | Basic, no centralized SIEM | Build logging stack (P2) |
| **Incident Response** | 🔴 HIGH | No formal playbook | Document + drill (P2) |
| **Infrastructure** | 🟡 MEDIUM | PostgreSQL + dev server, no WAF/IDS | Add defensive layer (P3) |

---

## P0: CRITICAL — Fix Now (Week 1)

### 1. Patch Vulnerable Dependencies

**NPM:**
```bash
cd /home/merger/hrconnect
npm audit fix --force
```

**Composer:**
```bash
composer update guzzlehttp/guzzle guzzlehttp/psr7
```

**Verify:**
```bash
npm audit
composer audit
```

**Expected outcome:**
- node-fetch upgraded (removes GHSA-r683-j2x4-v87g, GHSA-w7rc-rwvf-8q5r)
- Guzzle ≥7.12.1 (removes CVE-2026-55767, CVE-2026-55568)
- PSR-7 ≥2.12.1 (removes CRLF injection CVEs)

---

### 2. Secret Rotation (Immediate)

**Actions:**
```bash
# 1. Generate new DB password
newpw=$(openssl rand -base64 32)
echo "New DB password: $newpw"

# 2. Rotate in PostgreSQL
psql -U postgres -d hris_payroll -c "ALTER USER hris_app WITH PASSWORD '$newpw';"

# 3. Update .env (file is 600, owner only)
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$newpw/" .env

# 4. Generate new APP_KEY
php artisan key:generate

# 5. Rotate API tokens (Sanctum)
# Clear existing tokens — force users to re-authenticate
php artisan tinker
# In Tinker:
# >>> Illuminate\Support\Facades\DB::table('personal_access_tokens')->truncate();
# >>> exit

# 6. Verify .env permissions
chmod 600 .env
stat -c '%A' .env  # should be: -rw-------
```

**Store rotated secrets securely:**
- Personal vault (Bitwarden/LastPass) — backup only
- Do NOT commit to git
- Document rotation date in `.hermes/SECRETS_AUDIT.md`

---

### 3. .env Security Audit

**Verify:**
```bash
# 1. File permissions
ls -la .env          # MUST be: -rw------- (600)
ls -la .env.example  # OK to be world-readable

# 2. Secrets present
grep -E "KEY|PASSWORD|SECRET|TOKEN" .env | wc -l

# 3. Pre-commit hook (prevent leaks)
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
if git diff --cached | grep -qE '\.(env|.*key|.*secret)$'; then
  echo "❌ BLOCKED: Attempted to commit secrets file"
  exit 1
fi
if git diff --cached | grep -q 'DB_PASSWORD=\|APP_KEY='; then
  echo "❌ BLOCKED: Attempted to commit unredacted credentials"
  exit 1
fi
exit 0
EOF
chmod +x .git/hooks/pre-commit

# 4. Test hook
git add .env  # Should be blocked
```

**Result:** No unredacted secrets in version control.

---

### 4. Database Encryption (PostgreSQL)

**Enable at-rest encryption:**
```sql
-- Check current status
SELECT datname, spcname FROM pg_database
JOIN pg_tablespace ON pg_database.dattablespace = pg_tablespace.spcoid
WHERE datname = 'hris_payroll';

-- Enable SSL/TLS for connections (already in Laravel config)
-- Verify in config/database.php:
-- 'sslmode' => 'require'
```

**Enable in-transit encryption (verify in .env):**
```bash
# .env should have:
DB_SSLMODE=require
DB_SSLCERT=null  # or path to cert if mTLS
```

**Sensitive field encryption (Laravel):**
```php
// In User model - encrypt PII columns
protected $encrypted = ['ssn', 'bank_account'];

// In Employee model
protected $encrypted = [
    'emergency_contact_phone',
    'home_address',
    'government_id',
];
```

---

## P1: HIGH — This Week

### 5. Input Validation & Sanitization Audit

**Scan for gaps:**
```bash
# Find all Form requests
find app/Http/Requests -name "*.php" | wc -l

# Find controllers accepting raw input
grep -r "request()" app/Http/Controllers | head -10

# Check for parameterized queries (good sign)
grep -r "DB::table" app | head -5
grep -r "whereRaw\|selectRaw" app  # RED FLAG if with user input

# Test XSS protection in Livewire
grep -r "wire:model\|@json" resources/views | head -5
```

**Action items:**
1. Every controller method using `request()` must have corresponding FormRequest
2. Every DB query with user input must use parameter binding (Laravel does this by default)
3. Every Livewire component using `@json()` must have `=>` encoding

**Example (FormRequest):**
```php
// app/Http/Requests/UpdateEmployeeRequest.php
public function rules()
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,'.$this->user()->id,
        'salary' => 'required|numeric|min:0',
        'department_id' => 'required|exists:departments,id',
    ];
}
```

---

### 6. Authorization (Role-Based Access Control — RBAC) Audit

**Map current permissions:**
```bash
# Find all Gate/Policy definitions
find app/Policies -name "*.php"
grep -r "Gate::define" app/Providers

# List current roles
php artisan tinker
>>> DB::table('roles')->pluck('name');
```

**Create RBAC matrix:**

| Role | Permissions | Routes |
|------|-------------|--------|
| **Admin** | All + audit logs | `/admin/*` |
| **Manager** | Read team data, approve leaves | `/approvals/*`, `/employees/team` |
| **Employee** | Read own data, submit requests | `/leaves/request`, `/my-profile` |
| **HR** | All employee data, run reports | `/admin/employees/*`, `/payroll/reports` |
| **Finance** | Payroll, reimbursements | `/payroll/*`, `/reimbursements/*` |

**Test authorization:**
```bash
# Test: Employee cannot read another employee's salary
curl -H "Authorization: Bearer $TOKEN_EMPLOYEE" \
     http://localhost:8000/api/employees/999/salary
# Expected: 403 Forbidden

# Test: Manager can only see team members
curl -H "Authorization: Bearer $TOKEN_MANAGER" \
     http://localhost:8000/api/approvals
# Expected: 200 OK with only their team's data
```

---

### 7. CSRF Protection Verification

**Already configured in Sanctum, verify:**
```bash
# 1. Check middleware is active
grep -r "VerifyCsrfToken" config/

# 2. Check Livewire CSRF token injection
grep -r "@csrf" resources/views | head -3

# 3. Test CSRF rejection
curl -X POST http://localhost:8000/api/profile \
     -d '{"name":"Hacker"}' \
     -H "Content-Type: application/json"
# Expected: 419 Token Mismatch
```

---

## P2: MEDIUM — Next 2 Weeks

### 8. Centralized Logging & Monitoring

**Setup:**
```bash
# 1. Laravel logging config
cat > config/logging.php << 'EOF'
return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'syslog'],
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'syslog' => [
            'driver' => 'syslog',
            'facility' => LOG_LOCAL0,
            'level' => 'info',
        ],
    ],
];
EOF

# 2. Log all auth events
php artisan make:event UserAuthenticated
```

**Key events to log:**
```php
// Log authentication attempts
Log::info('User login', ['user_id' => $user->id, 'ip' => request()->ip()]);

// Log authorization failures
Log::warning('Unauthorized access attempt', [
    'user_id' => auth()->id(),
    'action' => 'view_salary',
    'resource' => $employee->id,
    'ip' => request()->ip(),
]);

// Log data mutations
Log::info('Employee record modified', [
    'user_id' => auth()->id(),
    'employee_id' => $employee->id,
    'changes' => $changes,
    'timestamp' => now(),
]);
```

**Monitor logs:**
```bash
# Live tail (P2 task — integrate into cron)
tail -f storage/logs/laravel.log | grep -iE "warning|error|failed"

# Daily security summary (cron job)
grep -i "failed\|unauthorized\|error" storage/logs/laravel.log | wc -l
```

---

### 9. Incident Response Plan

**Create `.hermes/INCIDENT_RESPONSE.md`:**

```markdown
# Incident Response Plan — HRConnect

## Detection
- Monitoring alerts (failed logins, errors, rate limiting)
- User reports (unusual account activity, data concern)
- Automated security scans

## Classification
- **P1 (Critical):** Data breach, RCE, credential compromise
- **P2 (High):** Unauthorized access, DOS, malware
- **P3 (Medium):** XSS, CSRF, info disclosure (non-PII)
- **P4 (Low):** Policy violation, config issue

## Response Steps (P1 example)
1. **Isolate:** Kill affected sessions, revoke tokens
2. **Assess:** Determine scope (which users, which data)
3. **Contain:** Disable compromised accounts, patch exploit
4. **Notify:** User notification (as required by law)
5. **Report:** Document timeline, root cause, remediation
6. **Post-mortem:** Prevent recurrence

## Contacts
- Security lead: [name]
- Legal: [counsel]
- Incident hotline: [emergency contact]
```

---

### 10. Rate Limiting & DOS Protection

**Implement:**
```php
// In routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Stricter for sensitive endpoints
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/approvals/*/approve', [ApprovalController::class, 'approve']);
});
```

**Test:**
```bash
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/login \
    -d '{"email":"test@test.com","password":"x"}' \
    -H "Content-Type: application/json"
  sleep 0.1
done
# Expected: 429 Too Many Requests after throttle limit
```

---

## P3: LOW — Next Month

### 11. WAF / Intrusion Detection

**Add at reverse proxy layer:**
```nginx
# nginx config (if fronting Laravel)
location / {
    # Block obvious SQLi patterns
    if ($args ~ "union|select|insert|drop|delete") {
        return 403;
    }
    
    # Rate limit per IP
    limit_req zone=api burst=100 nodelay;
    
    proxy_pass http://laravel:8000;
}
```

---

### 12. Automated Security Scanning

**Weekly SAST:**
```bash
# Install PHPStan (static analyzer)
composer require --dev phpstan/phpstan

# Run scan
./vendor/bin/phpstan analyse app --level=9

# Add to CI/CD (pre-commit, pre-push)
```

**Setup GitHub Scanning (if using GitHub):**
```bash
gh repo update --enable-vulnerability-alerts
gh secret set GITHUB_TOKEN -b $(gh auth token)
```

---

### 13. Compliance Checklist (GDPR/Local)

- [ ] Privacy Policy updated (data handling, retention)
- [ ] Data Processing Agreement with infra providers
- [ ] PII inventory documented
- [ ] Encryption enabled (at-rest, in-transit)
- [ ] Access logging enabled (audit trail)
- [ ] Data retention policy defined (auto-delete old records)
- [ ] User consent mechanism (if applicable)
- [ ] Right-to-deletion workflow (GDPR Article 17)
- [ ] Breach notification plan

---

## Security Checklist (Ongoing)

- [ ] Dependencies patched weekly (`npm audit fix`, `composer update`)
- [ ] Secrets rotated quarterly (DB password, APP_KEY, API tokens)
- [ ] Logs reviewed daily (grep for errors, unauthorized access)
- [ ] Access control tested monthly (RBAC, IDOR)
- [ ] Penetration test annually (full scope)
- [ ] Team training quarterly (OWASP, secure coding)

---

## Tools & Commands

```bash
# Dependency audit
npm audit
composer audit

# SAST
./vendor/bin/phpstan analyse app
npm run lint

# Secrets scan (pre-commit)
git diff --cached | grep -i "password\|key\|secret" && echo "BLOCKED"

# Log review
grep -i "error\|warning\|failed" storage/logs/laravel.log

# Password rotation
php artisan tinker
>>> DB::table('personal_access_tokens')->truncate();

# Rate limit test
ab -n 100 -c 10 http://localhost:8000/api/endpoint
```

---

## Contacts & Escalation

| Role | Contact | Escalation Path |
|------|---------|-----------------|
| Security Lead | [TBD] | - |
| Dev Lead | [TBD] | Security Lead |
| DevOps | [TBD] | Security Lead |
| Legal / Compliance | [TBD] | Security Lead |

---

## Sign-Off

- [ ] P0 tasks completed (dependencies, secrets)
- [ ] P1 tasks assigned (team ownership)
- [ ] P2 tasks scheduled (sprints)
- [ ] P3 tasks backlogged (future)

Last reviewed: [date]  
Next review: [date + 30 days]
