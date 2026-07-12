# GitHub PR Automation — Setup Guide

**Status:** Ready for activation  
**Requires:** GitHub CLI authentication

---

## What This Adds

**Before:** Tasks commit locally only  
**After:** Tasks create feature branches + auto-PR to `development`

### Full Workflow Per Task:
1. Create feature branch: `feat/{TASK_ID}-{description}`
2. Run OpenCode (FE/BE tasks)
3. Run tests (E2E/unit)
4. Auto-fix lint issues
5. Git commit with task metadata
6. Push branch to GitHub
7. Create draft PR → `development`
8. Log PR URL to task queue

---

## Setup Steps

### 1. Install & Authenticate GitHub CLI

```bash
cd /home/merger/hrconnect

# Install gh CLI + authenticate
bash .hermes/setup-github.sh
```

**This will:**
- Install `gh` CLI (GitHub official tool)
- Run `gh auth login` (interactive)
- Verify repo access: `Merger2/hrconnect`

**Required scopes:** `repo`, `workflow`

### 2. Update Cron Job

```bash
# Update cron to use PR-enabled script
cronjob action=update job_id=8ce522d16e66 script=.hermes/cron-dev-pr.sh
```

### 3. Test Manually (Optional)

```bash
# Dry run (doesn't require gh auth yet)
bash .hermes/cron-dev-pr.sh
```

---

## Branch Strategy

### Source Branch
- Always branch from: `development`
- Always pull latest before creating branch

### Feature Branch Naming
```
feat/{TASK_ID}-{first-30-chars-of-title}
```

Examples:
- `feat/FE_FACE_IMPORT-fix-face-api-js-import`
- `feat/E2E_CLOCK_IN-test-clock-in-with-gps-ge`
- `feat/BE_PPH21_FIX-consolidate-pph21-calcula`

### PR Target
- Always: `development` branch
- Never: `main` (production protected)

---

## PR Format

### Title
```
[TASK_ID] Task Title
```

### Body
```markdown
## Task: TASK_ID

**Title:** Task title here

**Description:**
Full task description from queue

**Auto-generated:** Autonomous development cycle
**Branch:** feat/...
**Target:** development

### Checks
- ✅ Tests passing (Pest/Playwright)
- ✅ Lint passing (Pint)
- ✅ Build successful (npm run build)
- ✅ No console errors (E2E)
```

### Status
- Created as **draft PR** (not ready for merge yet)
- You can mark "Ready for review" manually

---

## Verification

After setup, check:

```bash
# Verify gh CLI
gh --version
gh auth status

# Verify repo access
gh repo view Merger2/hrconnect

# Check cron job script
cronjob action=list | grep cron-dev-pr.sh
```

---

## Task Queue Updates

Each completed task will now have:

```json
{
  "tasks": {
    "FE_FACE_IMPORT": {
      "status": "completed",
      "last_run": "2026-07-11T20:00:00Z",
      "pr_url": "https://github.com/Merger2/hrconnect/pull/123"
    }
  }
}
```

---

## Manual PR Review Workflow

1. **Wait for automation** to create draft PR
2. **Review changes** on GitHub
3. **Check CI/CD** (if configured)
4. **Mark ready** for review
5. **Merge** to development
6. **Delete** feature branch (optional)

---

## Safety Features

✅ **Never pushes to main** - Always development  
✅ **Draft PRs** - Requires manual review  
✅ **Tests must pass** - No PR if tests fail  
✅ **Lint auto-fix** - Clean code before commit  
✅ **No changes = no commit** - Won't create empty PRs  
✅ **Failed tasks cleanup** - Auto-deletes failed branches  

---

## Troubleshooting

### `gh: command not found`
Run: `bash .hermes/setup-github.sh`

### `gh auth status` fails
Run: `gh auth login --scopes repo,workflow`

### PR creation fails
1. Check token scopes: `gh auth status`
2. Verify repo access: `gh repo view Merger2/hrconnect`
3. Check branch exists: `git branch -a`

### Branch already exists
Automation will checkout existing branch and continue

---

## Rollback to No-PR Mode

```bash
# Switch back to old script (commits only, no PR)
cronjob action=update job_id=8ce522d16e66 script=.hermes/cron-dev-worker.sh
```

---

## Cost / Rate Limits

**GitHub API:**
- 5,000 requests/hour (authenticated)
- Each cycle uses ~3-5 requests (branch, push, PR create)
- ~100-150 cycles/hour max (well within limit)

**No cost** - GitHub CLI is free for public/private repos.
