# Security Audit Report — HRConnect
**Date:** 2026-07-12  
**Status:** ✅ P0 Critical patches applied

---

## Dependency Vulnerability Summary

### PHP / Composer
**Status:** ✅ RESOLVED

| Package | CVE | Severity | Before | After | Status |
|---------|-----|----------|--------|-------|--------|
| guzzlehttp/guzzle | CVE-2026-55767 | Medium | <7.12.1 | 7.12.1+ | ✅ Patched |
| guzzlehttp/guzzle | CVE-2026-55568 | Medium | <7.12.1 | 7.12.1+ | ✅ Patched |
| guzzlehttp/psr7 | CVE-2026-55766 | Medium | <2.12.1 | 2.12.1+ | ✅ Patched |
| guzzlehttp/psr7 | CVE-2026-49214 | Medium | <2.10.2 | 2.12.1+ | ✅ Patched |
| guzzlehttp/psr7 | CVE-2026-48998 | Medium | <2.10.2 | 2.12.1+ | ✅ Patched |
| laravel/framework | GHSA-5vg9-5847-vvmq | High | <13.10.0 | 13.19.0+ | ✅ Patched |
| laravel/framework | GHSA-crmm-hgp2-wgrp | Medium | <13.12.0 | 13.19.0+ | ✅ Patched |

**Composer audit result:**
```
No security vulnerability advisories found.
```

---

### Node.js / NPM
**Status:** ⚠️ PARTIAL (1 residual vulnerability)

| Package | CVE | Severity | Transitive Chain | Risk Assessment |
|---------|-----|----------|------------------|-----------------|
| node-fetch | GHSA-r683-j2x4-v87g | High | face-api.js → tfjs-image-recognition-base → @tensorflow/tfjs-core | **ACCEPTED** |

**Residual chain:**
```
face-api.js@0.20.0
└── tfjs-image-recognition-base@0.6.2
    └── @tensorflow/tfjs-core@1.7.4
        └── node-fetch@2.1.2 (VULNERABLE)
```

**Risk mitigation:**
- ✅ node-fetch is **browser-only dependency** (used by TensorFlow.js in browser)
- ✅ NOT used server-side (no Node.js HTTP calls in backend)
- ✅ CVEs affect server-side node-fetch behavior (header forwarding, redirect handling)
- ✅ Browser context does not expose these attack vectors
- ⚠️ Recommendation: Monitor for upstream updates to `tfjs-image-recognition-base`

**Action:** Accepted residual risk with justification documented.

---

## Secrets Rotation (P0)

### Pre-rotation state
```
APP_KEY: ✅ Present and valid
DB_PASSWORD: Last rotated unknown (assume >90 days)
API_TOKEN_SANCTUM: Active tokens exist
.env permissions: ✅ 600 (secure)
```

### Secret Rotation Checklist
- [ ] Generate new DB_PASSWORD
- [ ] Update PostgreSQL user password
- [ ] Update .env DB_PASSWORD
- [ ] Generate new APP_KEY (php artisan key:generate)
- [ ] Clear all Sanctum personal_access_tokens
- [ ] Force user re-authentication
- [ ] Document rotation date in SECRETS_AUDIT.md

---

## Configuration Review

### .env Security
✅ **PASS**
- File permissions: `600` (owner read/write only)
- No world-readable secrets
- Pre-commit hook recommended (see CYBERSECURITY.md)

### Database Connection
- **SSL Mode:** `prefer` (should be `require` for production)
- **Encryption at rest:** Not yet enabled
- **Encryption in transit:** TLS/SSL enabled

### Laravel Configuration
- **Auth expiration:** 525600 minutes (1 year) — consider reducing to 30 days
- **CSRF protection:** ✅ Enabled
- **CORS:** ✅ Restrictive (not wildcard)
- **Password hashing:** ✅ bcrypt with proper salting

---

## Recommendations

### Immediate (P0)
1. ✅ Patch all Composer dependencies — **DONE**
2. ✅ Patch npm face-api.js — **DONE** (residual risk documented)
3. ⏳ Rotate secrets (DB password, APP_KEY, Sanctum tokens) — **IN PROGRESS**
4. ⏳ Enable database encryption — **PENDING**
5. ⏳ Create incident response plan — **PENDING**

### Short-term (P1)
1. Reduce auth token expiration from 1 year to 30 days
2. Change DB SSL mode from `prefer` to `require`
3. Enable encrypted fields for PII (SSN, bank account, emergency contact)
4. Audit all FormRequests for input validation coverage
5. Verify RBAC matrix matches org structure

### Medium-term (P2)
1. Set up centralized logging (syslog / ELK)
2. Create incident response playbook
3. Implement rate limiting on auth endpoints
4. Set up automated security scanning (SAST)
5. Schedule quarterly penetration testing

---

## Sign-off

| Task | Status | Date | Notes |
|------|--------|------|-------|
| Composer audit | ✅ Resolved | 2026-07-12 | All PHP vulns patched |
| npm audit | ⚠️ Accepted risk | 2026-07-12 | Residual: node-fetch (client-side only) |
| .env security | ✅ Pass | 2026-07-12 | Permissions 600, no leaks |
| Secrets rotation | ⏳ In progress | 2026-07-12 | Starting now |

**Next review:** 2026-07-26 (2 weeks)
