# HRConnect Autonomous Development — Setup Checklist

## ✅ Already Completed (No DB Required)

### 1. Automation Framework Created
- `.hermes/task-queue.json` — 10 prioritized tasks (E2E-first strategy)
- `.hermes/cron-dev-worker.sh` — Main orchestrator script (executable)
- `.hermes/metrics.json` — Metrics tracking system
- `.hermes/automation-workflow.md` — Strategy documentation

### 2. E2E Test Specs Created (Playwright)
- `tests/e2e/face-enrollment.spec.ts` — Face registration flow tests
- `tests/e2e/clock-in.spec.ts` — Clock-in + GPS geofencing tests
- `tests/e2e/rag-chat.spec.ts` — RAG chat + PDF upload tests

### 3. Configuration Files
- `.hermes/setup.sh` — One-time initialization script (executable)
- `.hermes/QUICKSTART.sh` — Quick reference guide (executable)
- `.hermes/README.md` — Full operations documentation

---

## ⏳ PENDING: Database Required

These steps CANNOT run until PostgreSQL is ready:

### Prerequisites
- [ ] PostgreSQL 15+ running
- [ ] Extensions installed: `pgvector`, `pg_trgm`, `pgcrypto`
- [ ] Database `hris_payroll` created
- [ ] `.env` configured with `DB_HOST`, `DB_DATABASE`, `DB_PASSWORD`

### When DB Ready
```bash
cd /home/merger/hrconnect

# 1. Run migrations
php artisan migrate

# 2. Run setup script
bash .hermes/setup.sh

# 3. Test manually (optional)
bash .hermes/cron-dev-worker.sh

# 4. Check Hermes cron job created
hermes cron list
```

---

## 🎯 Current Status

| Component | Status | Notes |
|-----------|--------|-------|
| **OpenCode Setup** | ✅ Ready | 7 credentials configured, Laravel Boost MCP enabled |
| **Task Queue** | ✅ Ready | 10 tasks queued, E2E-first strategy |
| **E2E Tests** | ✅ Ready | Playwright specs written (3 test files) |
| **Automation Script** | ✅ Ready | cron-dev-worker.sh ready to run |
| **Metrics System** | ✅ Ready | Tracking setup complete |
| **Database** | ⏳ Pending | Waiting for PostgreSQL activation |
| **Cron Job** | ⏳ Pending | Will create after DB ready |
| **First Task Run** | ⏳ Pending | Blocked on DB + migrations |

---

## 📋 Manual E2E Testing (Before DB)

Can test without running full automation:

```bash
# Build frontend assets
npm run build

# Run specific E2E test (headless)
npm run test:e2e tests/e2e/face-enrollment.spec.ts

# Run with UI for debugging
npm run test:e2e:ui

# Generate report
npm run test:e2e:report
```

---

## 🚀 Next Steps

### When You Activate Database:
1. Notify Hermes: "Database ready, activate automation"
2. Run: `bash /home/merger/hrconnect/.hermes/setup.sh`
3. Monitor: `watch -n 30 'tail -30 .hermes/cron-logs/$(date +%Y%m%d).log'`

### To Pause Automation Anytime:
```bash
hermes cron pause hrconnect-autonomous-dev
```

### To View Progress:
```bash
# Task queue status
cat .hermes/task-queue.json | jq '.queue[] | {id, priority, status}'

# Metrics
cat .hermes/metrics.json | jq '.metrics'

# Recent logs
tail -50 .hermes/cron-logs/$(date +%Y%m%d).log
```

---

## 📚 Files Created (8 total)

```
.hermes/
├── automation-workflow.md      ← Strategy document
├── cron-dev-worker.sh          ← Main orchestrator (executable)
├── metrics.json                ← Metrics tracking
├── README.md                   ← Full documentation
├── QUICKSTART.sh               ← Quick reference (executable)
├── setup.sh                    ← Initialization script (executable)
└── task-queue.json             ← 10 prioritized tasks

tests/e2e/
├── face-enrollment.spec.ts     ← Face registration E2E
├── clock-in.spec.ts            ← Clock-in + GPS E2E
└── rag-chat.spec.ts            ← RAG chat + PDF E2E
```

---

## 💡 Key Points

✅ **No database needed** for:
- Reviewing task queue structure
- Reading automation strategy
- Building frontend (npm run build)
- Running E2E tests (with mocks)

⏳ **Database REQUIRED** for:
- Running migrations
- Creating Hermes cron job
- Running actual development cycles
- Tests that interact with real DB

**Cost-free setup** → Minimal cost when DB ready → Maximum automation after that.
