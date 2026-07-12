# Incident Response Plan — HRConnect

**Version:** 1.0  
**Date:** 2026-07-12  
**Last Updated:** 2026-07-12  
**Classification:** Internal Use Only

---

## Table of Contents

1. [Purpose](#purpose)
2. [Scope](#scope)
3. [Incident Classification](#incident-classification)
4. [Response Team](#response-team)
5. [Incident Response Procedures](#incident-response-procedures)
6. [Communication Plan](#communication-plan)
7. [Post-Incident Activities](#post-incident-activities)
8. [Contact Information](#contact-information)

---

## Purpose

This Incident Response Plan (IRP) defines the procedures for detecting, responding to, and recovering from security incidents affecting HRConnect. The goal is to minimize damage, reduce recovery time, and prevent recurrence.

---

## Scope

This plan covers:
- **Applications:** HRConnect (Laravel 13 + Livewire 4)
- **Infrastructure:** PostgreSQL database, application servers
- **Data:** Employee PII, salary information, HR records
- **Exclusions:** Third-party integrations (handled by vendor IRP)

---

## Incident Classification

| Level | Description | Response Time | Example |
|-------|-------------|---------------|---------|
| **P1 (Critical)** | Active breach, data exfiltration, RCE, credential compromise | **Immediate** (< 15 min) | Unauthorized database access, SQL injection success, admin account compromise |
| **P2 (High)** | Unauthorized access, DOS, malware detected | **1 hour** | Failed auth spike, XSS reflected in logs, suspected malware in uploads |
| **P3 (Medium)** | Policy violation, misconfiguration, info disclosure (non-PII) | **4 hours** | Exposed debug logs, weak password detected, missing MFA |
| **P4 (Low)** | Minor issues, no immediate risk | **24 hours** | UI bugs, non-critical errors, outdated dependencies |

---

## Response Team

| Role | Name | Contact | Escalation Path |
|------|------|---------|-----------------|
| **Security Lead** | Merger (Owner) | merger@hrconnect.id | CEO/CFO |
| **Dev Lead** | Hermes Agent | telegram://saya | Security Lead |
| **DevOps** | Hermes Agent | cron://hrconnect-security-daily | Security Lead |
| **Legal/Compliance** | [TBD] | legal@hrconnect.id | Security Lead |

---

## Incident Response Procedures

### Phase 1: Detection & Reporting

**Sources:**
- Monitoring alerts (errors, failed logins, rate limiting)
- User reports (unusual activity, phishing)
- Security scans (SAST, dependency audit)
- Log review (grep errors/warnings)

**Reporting:**
1. Report to Security Lead immediately (P1/P2) or Dev Lead (P3/P4)
2. Use incident ticket system (or email with `[SECURITY]` prefix)
3. Include: timestamp, affected systems, initial assessment

---

### Phase 2: Triage & Classification

**Security Lead responsibilities:**
1. Verify incident真实性 (not false positive)
2. Classify severity (P1-P4)
3. Assign incident handler
4. Activate IR team if P1/P2

**Triage checklist:**
- [ ] Is the system still accessible?
- [ ] Is data being exfiltrated?
- [ ] Are user credentials compromised?
- [ ] Is the attack ongoing?

---

### Phase 3: Response (P1 Example)

#### 1. Isolate

**Immediate actions:**
```bash
# Revoke all active tokens
php artisan tinker
>>> DB::table('personal_access_tokens')->delete();

# Block suspicious IP ranges (if applicable)
# In nginx/Apache config or firewall

# Disable affected features
# Update .env: APP_DEBUG=false
```

#### 2. Contain

**Per incident type:**

| Type | Action |
|------|--------|
| **SQLi** | Rollback recent DB changes, audit affected tables |
| **XSS/CSRF** | Clear cache, update CSP headers |
| **Auth bypass** | Force password reset, review auth logs |
| **Data breach** | Identify scope (which users, which data) |

#### 3. Eradicate

- Patch exploit (code + config)
- Remove malware/backdoors
- Revoke all compromised tokens
- Update secrets (APP_KEY, DB password)

#### 4. Recover

- Restore from clean backup if needed
- Re-enable services gradually
- Monitor for residual activity

---

### Phase 4: Notification

**Internal:**
- Security Lead → Dev Lead, DevOps, Management (P1/P2 within 1 hour)
- Dev Lead → Development team (root cause, fix progress)

**External (GDPR/compliance):**
- Affected users (within 72 hours if PII breach)
- Regulatory bodies (if required by law)
- Legal counsel (before external communication)

---

### Phase 5: Documentation

**Incident report includes:**
- Timeline (detection → resolution)
- Root cause analysis
- Impact assessment (users affected, data exposed)
- Response actions taken
- Lessons learned
- Preventive measures

---

## Communication Plan

### Internal Channels

| Channel | Use Case |
|---------|----------|
| **Slack/Telegram** | Real-time coordination |
| **Email** | Formal updates |
| **Incident ticket** | Documentation trail |

### External Channels

| Channel | Approval Required |
|---------|-------------------|
| User email ( breach notification) | Legal + Management |
| Social media | Management only |
| PR statement | Legal + Management |

---

## Post-Incident Activities

### 1. Debrief (Within 48 hours)

**Attendees:** Security Lead, Dev Lead, DevOps, affected team members

**Topics:**
- What happened? (root cause)
- What worked well?
- What can be improved?
- Action items (with owners and deadlines)

### 2. Documentation

Update `.hermes/incidents/` with:
- Incident report
- Timeline
- Screenshots/logs (if applicable)
- Lessons learned

### 3. Prevention

**Automate:**
- Add SAST rule for exploit type
- Add monitoring alert for similar pattern
- Update security training

**Manual:**
- Patch similar vulnerabilities
- Update IR plan
- Conduct security review

---

## Contact Information

### Emergency

| Role | Primary | Backup |
|------|---------|--------|
| Security Lead | [TBD] | [TBD] |
| Dev Lead | [TBD] | [TBD] |
| DevOps | [TBD] | [TBD] |

### External

| Organization | Contact | Notes |
|--------------|---------|-------|
| Law enforcement | 110/119 | Immediate P1 response |
| Cyber security agency | BSSN (bssn.go.id) | If required by law |
| Legal counsel | legal@hrconnect.id | Before external comms |

---

## Test & Drills

| Drill Type | Frequency | Last Tested |
|------------|-----------|-------------|
| Tabletop exercise | Quarterly | [TBD] |
| Full incident simulation | Annually | [TBD] |
| Backup restore test | Monthly | [TBD] |

---

## Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Security Lead | [TBD] | [Digital/Physical] | [Date] |
| Dev Lead | [TBD] | [Digital/Physical] | [Date] |
| Management | [TBD] | [Digital/Physical] | [Date] |

---

## Revision History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-07-12 | Initial draft | Security Team |

---

## Quick Reference (P1 Response)

```bash
# 1. Revoke all tokens (force re-auth)
php artisan tinker
>>> DB::table('personal_access_tokens')->delete();

# 2. Disable app (emergency)
php artisan down --message="Security incident - system temporarily unavailable"

# 3. Clear logs (if sensitive data exposed)
> storage/logs/laravel.log

# 4. Generate new APP_KEY
php artisan key:generate

# 5. Rotate DB password
psql -U postgres -c "ALTER USER hrconnect_app WITH PASSWORD 'new_strong_password';"

# 6. Enable debugging for investigation
php artisan tinker
>>> Log::info('Incident investigation started', ['user' => 'security_lead', 'incident' => 'TBD']);
```

---

## Next Steps

1. [ ] Fill in `[TBD]` contact information
2. [ ] Schedule first tabletop drill
3. [ ] Add to onboarding documentation
4. [ ] Print and distribute to response team
5. [ ] Review quarterly (next: 2026-10-12)
