## ✅ JAWABAN: Auto-Commit & PR Setup — READY

**Date:** 2026-07-11  
**Status:** Scripts ready, requires GitHub authentication

---

## Yang Sudah Dibuat

### 1. GitHub Setup Script
**File:** `.hermes/setup-github.sh` (executable)  
**Purpose:** Install gh CLI + authenticate GitHub

### 2. PR Automation Script
**File:** `.hermes/cron-dev-pr.sh` (executable)  
**Purpose:** Complete workflow: develop → branch → commit → push → PR

### 3. Documentation
**File:** `.hermes/GITHUB-PR-SETUP.md`  
**Purpose:** Setup guide & troubleshooting

---

## Current Config (Already Set ✅)

```bash
# Git config
User: Merger
Email: fikihaldiansyah28@gmail.com
Remote: https://github.com/Merger2/hrconnect.git
Branch: development

# Cron job
Job ID: 8ce522d16e66
Schedule: Every 6 hours
Script: .hermes/cron-dev-worker.sh (local commits only)
```

---

## What You Need to Provide

### Option A: GitHub PAT (Personal Access Token) — RECOMMENDED

**Create at:** https://github.com/settings/tokens?type=beta

**Required scopes:**
- ✅ `repo` (full control of private repositories)
- ✅ `workflow` (update GitHub Actions workflows)

**Steps:**
1. Go to GitHub Settings → Developer settings → Personal access tokens → Fine-grained tokens
2. Click "Generate new token"
3. Token name: `HRConnect Automation`
4. Expiration: 90 days (or custom)
5. Repository access: **Only select repositories** → `Merger2/hrconnect`
6. Permissions:
   - Contents: **Read and write**
   - Pull requests: **Read and write**
   - Workflows: **Read and write**
7. Generate token
8. **Copy token** (won't show again!)

### Option B: OAuth (Interactive Login)

Run `gh auth login` and follow prompts (browser-based)

---

## Activation Steps

### Step 1: Authenticate GitHub

```bash
cd /home/merger/hrconnect

# If you have PAT token
echo "YOUR_GITHUB_TOKEN_HERE" | gh auth login --with-token

# OR interactive OAuth
gh auth login
```

### Step 2: Verify Authentication

```bash
# Should show: Logged in to github.com
gh auth status

# Test repo access
gh repo view Merger2/hrconnect
```

### Step 3: Update Cron Job

```bash
# Switch from local-commit to PR-enabled script
cronjob action=update job_id=8ce522d16e66 script=.hermes/cron-dev-pr.sh
```

### Step 4: Test (Optional)

```bash
# Manual test run (won't affect cron schedule)
bash .hermes/cron-dev-pr.sh
```

---

## Workflow Comparison

### Before (Current)
```
Task → OpenCode → Tests → Commit (local) → Done
```

### After (With PR)
```
Task → Feature Branch → OpenCode → Tests → Commit → Push → Draft PR → Done
```

---

## PR Example

When task `FE_FACE_IMPORT` completes:

**Branch created:**
```
feat/FE_FACE_IMPORT-fix-face-api-js-import
```

**PR created:**
```
Title: [FE_FACE_IMPORT] Fix face-api.js import
Target: development
Status: Draft
```

**PR body:**
```markdown
## Task: FE_FACE_IMPORT

**Title:** Fix face-api.js import

**Description:**
Fix broken face-api.js import in resources/js/face-recognition.js.
Check node_modules, verify CDN link in layout, test browser console

**Auto-generated:** Autonomous development cycle
**Branch:** feat/FE_FACE_IMPORT-fix-face-api-js-import
**Target:** development

### Checks
- ✅ Tests passing (Pest/Playwright)
- ✅ Lint passing (Pint)
- ✅ Build successful (npm run build)
- ✅ No console errors (E2E)
```

**You then:**
1. Review PR on GitHub
2. Check changes
3. Mark "Ready for review"
4. Merge to `development`

---

## Safety Features

✅ **Draft PRs only** - Requires manual review before merge  
✅ **Never touches main** - Always branches from/to `development`  
✅ **Tests must pass** - No PR if tests fail  
✅ **Lint auto-fixed** - Clean code guaranteed  
✅ **No empty PRs** - Won't create PR with no changes  

---

## Task Queue Update

After activation, completed tasks will include PR URL:

```json
{
  "FE_FACE_IMPORT": {
    "status": "completed",
    "last_run": "2026-07-11T20:00:00Z",
    "pr_url": "https://github.com/Merger2/hrconnect/pull/123",
    "log_file": ".hermes/cron-logs/FE_FACE_IMPORT.log"
  }
}
```

---

## Next Immediate Action

**Tell me:**
1. Do you want me to install `gh` CLI now? (requires sudo)
2. Do you have GitHub PAT ready? (I can help authenticate)
3. Or prefer manual setup later?

I can run `bash .hermes/setup-github.sh` if you want automatic setup.

---

## Files Summary

```
.hermes/
├── setup-github.sh          (1.5 KB) ← Install gh + auth
├── cron-dev-pr.sh           (6.5 KB) ← PR automation script
├── cron-dev-worker.sh       (5.9 KB) ← Old script (local only)
├── GITHUB-PR-SETUP.md       (4.0 KB) ← Full documentation
└── task-queue.json          (5.3 KB) ← Will store PR URLs
```

**All scripts have valid bash syntax** ✅
