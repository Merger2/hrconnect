# HRConnect Autonomous Development — Setup & Operations Guide

## Overview

Project automation yang menggunakan:
- **OpenCode** — autonomous coding agent dengan Laravel Boost MCP
- **Playwright** — E2E testing untuk face enrollment, clock-in, RAG chat
- **Hermes Cron** — orchestrator untuk looping development cycles
- **Strategi** — E2E-first: setiap task develop → test → commit

## Quick Start

### 1. Verify Prerequisites

```bash
cd /home/merger/hrconnect

# Check OpenCode
opencode --version
opencode auth list

# Check Playwright
npm run test:e2e --help

# Check Laravel artisan
php artisan --version
```

### 2. View Task Queue

```bash
cat .hermes/task-queue.json | jq '.queue[]'
```

Status: pending → in_progress → completed

### 3. Manual Test Run (Optional)

```bash
# Run single task manually
bash .hermes/cron-dev-worker.sh

# Check logs
cat .hermes/cron-logs/$(date +%Y%m%d).log

# View metrics
cat .hermes/metrics.json | jq '.metrics'
```

## Cron Job Configuration

Scheduled to run **every 6 hours** automatically via Hermes.

### Cycle Details

Each cycle:
1. Pick next pending task from queue
2. Run OpenCode with task description + context files (AGENTS.md, task.md)
3. Execute task-specific tests (E2E or unit)
4. Auto-fix linting issues if needed
5. Git commit with task ID in message
6. Update metrics and log results

### Logs Location

- Main log: `.hermes/cron-logs/YYYYMMDD.log`
- Per-task logs: `.hermes/cron-logs/{TASK_ID}.log`
- Test outputs: `.hermes/cron-logs/{TASK_ID}.test.log`

### Status Tracking

Metrics tracked in `.hermes/metrics.json`:
- Task attempts
- Passed/failed tests
- Last run timestamp
- Log file references

## Task Queue Priority

### P0: Demo-Critical (Face + Attendance + RAG)
1. **FE_FACE_IMPORT** — Fix face-api.js import
2. **FE_FACE_MISMATCH** — Resolve column mismatch
3. **E2E_FACE_ENROLL** — Test complete face enrollment flow
4. **FE_CLOCK_IN_UI** — Build clock-in component
5. **E2E_CLOCK_IN** — Test clock-in with GPS geofencing
6. **BE_RAG_DEADCODE** — Clean up dead code
7. **E2E_RAG_CHAT** — Test RAG chat + PDF upload

### P1: Backend Stability
8. **BE_LOAN_TRAIT** — Add missing Loan trait
9. **BE_PPH21_FIX** — Consolidate Pph21 calculation
10. **UNIT_TESTS** — Full test suite validation

## Troubleshooting

### Task Stuck in progress?

```bash
# Reset task status
jq '.tasks["TASK_ID"].status = "pending"' .hermes/task-queue.json > .tmp && mv .tmp .hermes/task-queue.json

# Re-run manually
bash .hermes/cron-dev-worker.sh
```

### Tests failing locally?

```bash
# Full test
composer test

# E2E debug
npm run test:e2e:debug

# Specific test
php artisan test tests/Feature/FaceEnrollmentTest.php --filter=testFaceUpload
```

### Check OpenCode MCP

```bash
# Verify Laravel Boost MCP is loaded
php artisan boost:mcp

# Check MCP in opencode.json
cat opencode.json | jq '.mcp'
```

## Development Workflow

### If Need to Pause Automation
```bash
# Disable cron temporarily
hermes cron pause <job_id>
```

### Manual Development + Test

```bash
# Make changes
vim app/Services/FaceService.php

# Run OpenCode for code review
opencode run "Review and improve this FaceService implementation" -f app/Services/FaceService.php

# Test locally
php artisan test tests/Feature/FaceTest.php

# If passing, commit and resume automation
git add -A && git commit -m "refactor: improve face service"
hermes cron resume <job_id>
```

## Expected Outcomes

By end of all cycles:
- ✅ Face enrollment working (E2E passing)
- ✅ Clock-in with GPS geofencing (E2E passing)
- ✅ RAG chat functional (E2E passing)
- ✅ All backend bugs fixed
- ✅ Full test suite passing
- ✅ Zero lint errors
- ✅ Ready for demo to dospem

## Key Commands Reference

```bash
# Start automation
hermes cron create "every 6h" -s cron-dev-worker.sh

# Monitor progress
watch -n 30 'tail -20 .hermes/cron-logs/$(date +%Y%m%d).log'

# View all metrics
cat .hermes/metrics.json | jq '.'

# Check git commits from automation
git log --oneline -20 --grep="feat:"

# Stop automation
hermes cron pause <job_id>

# Resume
hermes cron resume <job_id>
```
