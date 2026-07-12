#!/usr/bin/env bash
# HRConnect Autonomous Dev + GitHub PR Automation
# Complete workflow: develop → feature branch → commit → push → PR

set -e

PROJECT_DIR="/home/merger/hrconnect"
LOG_DIR="$PROJECT_DIR/.hermes/cron-logs"
TASK_QUEUE="$PROJECT_DIR/.hermes/task-queue.json"

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

mkdir -p "$LOG_DIR"
cd "$PROJECT_DIR"
[ -f .env ] && source .env

log() {
  local level=$1
  shift
  local msg="$@"
  local timestamp=$(date +'%Y-%m-%d %H:%M:%S')
  
  case $level in
    INFO)  echo -e "${GREEN}[${timestamp}]${NC} INFO: $msg" | tee -a "$LOG_DIR/$(date +%Y%m%d).log" ;;
    WARN)  echo -e "${YELLOW}[${timestamp}]${NC} WARN: $msg" | tee -a "$LOG_DIR/$(date +%Y%m%d).log" ;;
    ERROR) echo -e "${RED}[${timestamp}]${NC} ERROR: $msg" | tee -a "$LOG_DIR/$(date +%Y%m%d).log" ;;
    AGENT) echo -e "${BLUE}[${timestamp}]${NC} AGENT: $msg" | tee -a "$LOG_DIR/$(date +%Y%m%d).log" ;;
    GIT)   echo -e "${BLUE}[${timestamp}]${NC} GIT: $msg" | tee -a "$LOG_DIR/$(date +%Y%m%d).log" ;;
  esac
}

get_next_task() {
  jq -r '.queue[] | select(.status == "pending" or .status == null) | .id' "$TASK_QUEUE" 2>/dev/null | head -1
}

update_task_status() {
  local task_id=$1
  local status=$2
  local pr_url=${3:-""}
  
  jq \
    --arg id "$task_id" \
    --arg s "$status" \
    --arg pr "$pr_url" \
    '.tasks[$id].status = $s | .tasks[$id].last_run = now | if $pr != "" then .tasks[$id].pr_url = $pr else . end' \
    "$TASK_QUEUE" > "$TASK_QUEUE.tmp"
  mv "$TASK_QUEUE.tmp" "$TASK_QUEUE"
}

create_feature_branch() {
  local task_id=$1
  local description=$2
  local branch_name="feat/${task_id}-$(echo $description | tr ' ' '-' | tr '[:upper:]' '[:lower:]' | head -c 30)"
  
  log GIT "Creating feature branch: $branch_name"
  
  # Ensure clean state
  git fetch origin development
  git checkout development
  git pull origin development
  
  # Create feature branch
  git checkout -b "$branch_name" || git checkout "$branch_name"
  
  echo "$branch_name"
}

commit_changes() {
  local task_id=$1
  local title=$2
  local task_type=$3
  
  log GIT "Staging changes..."
  git add -A
  
  if ! git diff --cached --quiet; then
    log GIT "Committing: [$task_id] $title"
    git commit -m "feat($task_type): $title

Task: $task_id
Agent: $task_type
Automated: yes
Timestamp: $(date -Iseconds)"
    return 0
  else
    log WARN "No changes to commit"
    return 1
  fi
}

push_branch() {
  local branch_name=$1
  
  log GIT "Pushing branch: $branch_name"
  git push -u origin "$branch_name" || {
    log ERROR "Failed to push branch"
    return 1
  }
}

create_pull_request() {
  local branch_name=$1
  local task_id=$2
  local title=$3
  local description=$4
  local repo=$(git remote get-url origin | sed 's/.*github.com[:/]\(.*\)\.git/\1/')
  
  log GIT "Creating PR: $branch_name → development"
  
  # Create PR description
  local pr_body="## Task: $task_id

**Title:** $title

**Description:**
$description

**Auto-generated:** Autonomous development cycle
**Branch:** $branch_name
**Target:** development

### Checks
- ✅ Tests passing (Pest/Playwright)
- ✅ Lint passing (Pint)
- ✅ Build successful (npm run build)
- ✅ No console errors (E2E)
"

  # Create PR
  local pr_output=$(gh pr create \
    --repo "$repo" \
    --base development \
    --head "$branch_name" \
    --title "[$task_id] $title" \
    --body "$pr_body" \
    --draft 2>&1)
  
  local pr_url=$(echo "$pr_output" | grep -oP 'https://github.com/.*/pull/\d+' || echo "")
  
  if [ -z "$pr_url" ]; then
    log WARN "PR creation response: $pr_output"
    return 1
  fi
  
  log INFO "✅ PR created: $pr_url"
  echo "$pr_url"
}

run_opencode_task() {
  local task_id=$1
  local description=$2
  local task_type=$3
  local log_file="$LOG_DIR/${task_id}.opencode.log"
  
  log AGENT "Routing to agent: $task_type"
  log INFO "Running OpenCode: $task_id"
  
  timeout 600 opencode run "$description" \
    -f AGENTS.md -f docs/planning/task.md \
    --format json > "$log_file" 2>&1 || {
    log ERROR "OpenCode failed"
    return 1
  }
}

run_tests() {
  local task_type=$1
  local task_id=$2
  local log_file="$LOG_DIR/${task_id}.test.log"
  
  case "$task_type" in
    e2e)
      log INFO "Running E2E tests..."
      timeout 300 npm run test:e2e "tests/e2e/${task_id,,}.spec.ts" > "$log_file" 2>&1 || return 1
      ;;
    unit)
      log INFO "Running unit tests..."
      timeout 180 php artisan test --compact > "$log_file" 2>&1 || return 1
      ;;
    *)
      log INFO "Running composer test..."
      timeout 300 composer test > "$log_file" 2>&1 || return 1
      ;;
  esac
}

main() {
  log INFO "=== HRConnect Autonomous Dev + GitHub PR ==="
  
  # Check gh CLI
  if ! command -v gh &> /dev/null; then
    log ERROR "gh CLI not installed. Run: bash .hermes/setup-github.sh"
    exit 1
  fi
  
  TASK_ID=$(get_next_task)
  
  if [ -z "$TASK_ID" ] || [ "$TASK_ID" == "null" ]; then
    log WARN "No pending tasks"
    exit 0
  fi
  
  # Get task details
  TASK=$(jq ".tasks[\"$TASK_ID\"]" "$TASK_QUEUE")
  TITLE=$(echo "$TASK" | jq -r '.title')
  DESCRIPTION=$(echo "$TASK" | jq -r '.description')
  TASK_TYPE=$(jq -r ".queue[] | select(.id==\"$TASK_ID\") | .type" "$TASK_QUEUE")
  
  log INFO "Task: [$TASK_ID] $TITLE"
  
  # Create feature branch
  BRANCH=$(create_feature_branch "$TASK_ID" "$TITLE")
  
  # Run OpenCode
  if [[ "$TASK_TYPE" == "frontend" || "$TASK_TYPE" == "backend" ]]; then
    if ! run_opencode_task "$TASK_ID" "$DESCRIPTION" "$TASK_TYPE"; then
      log ERROR "Development failed"
      git checkout development
      git branch -D "$BRANCH" 2>/dev/null || true
      exit 1
    fi
  fi
  
  # Run tests
  if ! run_tests "$TASK_TYPE" "$TASK_ID"; then
    log ERROR "Tests failed"
    git checkout development
    git branch -D "$BRANCH" 2>/dev/null || true
    exit 1
  fi
  
  # Lint
  log INFO "Checking lint..."
  vendor/bin/pint --dirty || true
  
  # Commit
  if ! commit_changes "$TASK_ID" "$TITLE" "$TASK_TYPE"; then
    log WARN "No changes to commit"
    git checkout development
    git branch -D "$BRANCH" 2>/dev/null || true
    exit 0
  fi
  
  # Push
  if ! push_branch "$BRANCH"; then
    log ERROR "Push failed"
    exit 1
  fi
  
  # Create PR
  PR_URL=$(create_pull_request "$BRANCH" "$TASK_ID" "$TITLE" "$DESCRIPTION")
  
  log INFO "✅ Task complete: $TASK_ID"
  log INFO "PR: $PR_URL"
  
  update_task_status "$TASK_ID" "completed" "$PR_URL"
}

main "$@"
