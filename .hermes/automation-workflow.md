# HRConnect Autonomous Development Workflow

This workflow automates development cycles for hrconnect with testing and E2E validation.

## Priority Queue (from docs/planning/task.md)

### P0: Demo-Critical (Face + Attendance + RAG)
1. **FE-Face-Import** — Fix face-api.js import broken
2. **FE-Face-Mismatch** — Resolve face_crop/photo_selfie column mismatch
3. **BE-Face-Mocked** — Remove hardcoded is_mocked in face logic
4. **FE-Enrollment-UI** — Complete face enrollment Livewire component
5. **FE-Clock-In-UI** — Complete clock-in page with GPS + face verification

### P1: Backend Critical
6. **BE-Loan-Trait** — Add missing Loan trait to Payroll model
7. **BE-KnowledgeBaseFactory** — Fix enum crash in factory
8. **BE-Employee-Import** — Fix 404 on employee CSV import endpoint
9. **BE-Pph21-Conflict** — Consolidate dual-method Pph21 calculation
10. **BE-RAG-DeadCode** — Remove RAG_MOCK_MODE and crossCheckIpLocation dead code

### P2: Testing & E2E
11. **Test-E2E-Face** — E2E: face enrollment flow
12. **Test-E2E-Clock** — E2E: clock-in with GPS geofencing
13. **Test-E2E-RAG** — E2E: RAG chat + PDF upload
14. **Test-Unit** — Run full test suite (composer test)

### P3: Polish
15. **FE-Approval-UI** — Complete approval detail modal
16. **Security-Headers** — Add security middleware

## Automation Loop Strategy

Each cron cycle:
1. Pick 1-2 items from queue based on dependencies
2. Run OpenCode with MCP (laravel-boost, search-docs)
3. Verify with `composer test` + E2E if applicable
4. Commit if passing, otherwise log failure
5. Move to next item

## OpenCode Commands

### Single Task
```bash
cd /home/merger/hrconnect
opencode run "TASK_DESCRIPTION"
```

### With Context Files
```bash
opencode run "TASK_DESCRIPTION" -f docs/planning/task.md -f AGENTS.md
```

### With Thinking
```bash
opencode run "TASK_DESCRIPTION" --thinking
```

## Testing Commands

```bash
# Full test (lint + tests)
composer test

# Unit tests only
php artisan test --compact

# E2E tests
npm run test:e2e

# E2E with UI
npm run test:e2e:ui
```

## Cron Job Config

Schedule: Every 6 hours (or on-demand)
Actions:
1. Source .env
2. Pick next priority task
3. Run OpenCode
4. Log results
5. Run tests
6. Git commit if passing
