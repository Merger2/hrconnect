## 🎯 AGENT ROLES SETUP — COMPLETE

**Date:** 2026-07-11  
**Status:** ✅ All three agent roles configured

---

## Agent Roles Defined

### 1. FE Agent (Frontend Specialist)
**File:** `.agents/FE-AGENT.md`  
**Skills:** `livewire-development`, `tailwindcss-development`, `pest-testing`  
**Focus:** Livewire 4 components, MD3 styling, face-api.js, E2E

### 2. BE Agent (Backend Specialist)
**File:** `.agents/BE-AGENT.md`  
**Skills:** `laravel-best-practices`, `fortify-development`, `ai-sdk-development`, `pest-testing`  
**Focus:** Models, migrations, APIs, services, database validation

### 3. QA Agent (Quality Assurance Specialist)
**File:** `.agents/QA-AGENT.md`  
**Skills:** `pest-testing`  
**Focus:** E2E (Playwright), feature tests (Pest), bug verification, regression

---

## Orchestration

**Master File:** `AGENTS.md`  
**Routing Logic:** Task type → Agent assignment
- `frontend` → FE Agent
- `backend` → BE Agent
- `e2e` → QA Agent
- `unit` → QA Agent

**Cron Job Updated:** `cron-dev-worker.sh` now routes tasks with agent context

---

## Task Queue Mapping (10 Tasks)

| Task ID | Type | Agent | Description |
|---------|------|-------|-------------|
| FE_FACE_IMPORT | frontend | FE | Fix face-api.js import |
| FE_FACE_MISMATCH | backend | BE | Resolve column mismatch |
| E2E_FACE_ENROLL | e2e | QA | Test face enrollment |
| FE_CLOCK_IN_UI | frontend | FE | Build clock-in UI |
| E2E_CLOCK_IN | e2e | QA | Test clock-in GPS |
| BE_RAG_DEADCODE | backend | BE | Clean up dead code |
| E2E_RAG_CHAT | e2e | QA | Test RAG chat |
| BE_LOAN_TRAIT | backend | BE | Add missing trait |
| BE_PPH21_FIX | backend | BE | Consolidate Pph21 |
| UNIT_TESTS | unit | QA | Full test suite |

---

## Agent Collaboration Workflow

### New Feature
1. BE Agent: API + service
2. BE Agent: Feature tests
3. FE Agent: Component + styling
4. FE Agent: Client validation
5. QA Agent: E2E tests
6. QA Agent: Full flow validation

### Bug Fix
1. QA Agent: Reproduce with test
2. BE/FE Agent: Fix
3. QA Agent: Verify test passes
4. QA Agent: Regression suite

---

## Quality Gates

Before commit:
- ✅ Relevant tests pass (Pest/Playwright)
- ✅ No lint errors (Pint)
- ✅ Build succeeds (npm run build)
- ✅ No console errors (E2E)
- ✅ Database validated (BE Agent)

---

## Files Created

```
.agents/
├── FE-AGENT.md        (2.8 KB) ← Frontend role definition
├── BE-AGENT.md        (3.8 KB) ← Backend role definition
├── QA-AGENT.md        (6.4 KB) ← QA role definition
├── AGENTS.md          (7.2 KB) ← Orchestration master
└── skills/            (existing) ← 6 technical skills

AGENTS.md              ← Project master (7.2 KB)

.hermes/
├── cron-dev-worker.sh (5.9 KB) ← Updated with agent routing
└── task-queue.json    (existing) ← 10 tasks mapped
```

---

## Verification

```bash
# Check agent files
ls -la /home/merger/hrconnect/.agents/*.md

# View orchestration
cat /home/merger/hrconnect/AGENTS.md

# Test agent routing
bash /home/merger/hrconnect/.hermes/cron-dev-worker.sh
```

---

## ✅ Ready for Autonomous Development

All roles configured. Automation will route tasks to appropriate agents.

**Next Run:** 2026-07-12 00:00:00 UTC+7 (cron job `8ce522d16e66`)
