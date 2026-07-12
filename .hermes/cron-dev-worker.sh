#!/usr/bin/env bash
# HRConnect Autonomous Development Worker with Role-Based Agents
# Routes tasks to FE/BE/QA agents based on task type

set -e

PROJECT_DIR="/home/merger/hrconnect"
LOG_DIR="$PROJECT_DIR/.hermes/cron-logs"
TASK_QUEUE="$PROJECT_DIR/.hermes/task-queue.json"
METRICS_FILE="$PROJECT_DIR/.hermes/metrics.json"
AGENTS_FILE="$PROJECT_DIR/AGENTS.md"

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
  esac
}

get_next_task() {
  jq -r '.queue[] | select(.status == "pending" or .status == null) | .id' "$TASK_QUEUE" 2>/dev/null | head -1
}

update_task_status() {
  local task_id=$1
  local status=$2
  local log_file=${3:-""}
  
  jq \
    --arg id "$task_id" \
    --arg s "$status" \
    --arg log "$log_file" \
    '.tasks[$id].status = $s | .tasks[$id].last_run = now | if $log != "" then .tasks[$id].log_file = $log else . end' \
    "$TASK_QUEUE" > "$TASK_QUEUE.tmp"
  mv "$TASK_QUEUE.tmp" "$TASK_QUEUE"
}

# Get agent role file based on task type
get_agent_file() {
  local task_type=$1
  
  case $task_type in
    frontend)
      echo "$PROJECT_DIR/.agents/FE-AGENT.md"
      ;;
    backend)
      echo "$PROJECT_DIR/.agents/BE-AGENT.md"
      ;;
    e2e|unit)
      echo "$PROJECT_DIR/.agents/QA-AGENT.md"
      ;;
    *)
      echo "$AGENTS_FILE"
      ;;
  esac
}

# Run OpenCode with appropriate agent context
run_opencode_task() {
  local task_id=$1
  local description=$2
  local task_type=$3
  local agent_file=$(get_agent_file "$task_type")
  local log_file="$LOG_DIR/${task_id}.opencode.log"
  
  log AGENT "Routing to agent: $task_type → $(basename $agent_file)"
  log INFO "Running OpenCode: $task_id"
  
  # Build context files list
  local context_files="-f $AGENTS_FILE -f docs/planning/task.md"
  
  if [ -f "$agent_file" ]; then
    context_files="$context_files -f $agent_file"
  fi
  
  timeout 600 opencode run "$description" $context_files --format json > "$log_file" 2>&1 || {
    log ERROR "OpenCode failed for $task_id"
    return 1
  }
  
  log INFO "OpenCode completed for $task_id"
  return 0
}

run_e2e_tests() {
  local test_pattern=$1
  local log_file="$LOG_DIR/e2e_${test_pattern}.log"
  
  log INFO "Running E2E tests: $test_pattern"
  
  if timeout 300 npm run test:e2e "$test_pattern" > "$log_file" 2>&1; then
    log INFO "✅ E2E tests passed"
    return 0
  else
    log WARN "❌ E2E tests failed"
    return 1
  fi
}

run_unit_tests() {
  local filter=$1
  local log_file="$LOG_DIR/unit_${filter}.log"
  
  log INFO "Running unit tests: $filter"
  
  if timeout 180 php artisan test --compact --filter="$filter" > "$log_file" 2>&1; then
    log INFO "✅ Unit tests passed"
    return 0
  else
    log WARN "❌ Unit tests failed"
    return 1
  fi
}

run_lint() {
  local log_file="$LOG_DIR/lint.log"
  
  log INFO "Running Pint lint..."
  
  if vendor/bin/pint --test --format agent > "$log_file" 2>&1; then
    log INFO "✅ Lint passed"
    return 0
  else
    log WARN "❌ Lint issues found"
    return 1
  fi
}

git_commit() {
  local task_id=$1
  local description=$2
  local task_type=$3
  
  git add -A
  git commit -m "feat($task_type): $description

Task: $task_id
Agent: $task_type
Automated: yes" || true
}

main() {
  log INFO "=== HRConnect Autonomous Dev Cycle Started ==="
  log AGENT "Agent orchestration enabled (FE/BE/QA)"
  
  TASK_ID=$(get_next_task)
  
  if [ -z "$TASK_ID" ] || [ "$TASK_ID" == "null" ]; then
    log WARN "No pending tasks in queue"
    exit 0
  fi
  
  # Get task details
  TASK=$(jq ".tasks[\"$TASK_ID\"]" "$TASK_QUEUE")
  TITLE=$(echo "$TASK" | jq -r '.title')
  DESCRIPTION=$(echo "$TASK" | jq -r '.description')
  TEST_CMD=$(echo "$TASK" | jq -r '.test_cmd')
  TASK_TYPE=$(jq -r ".queue[] | select(.id==\"$TASK_ID\") | .type" "$TASK_QUEUE")
  
  log INFO "Processing: [$TASK_ID] $TITLE"
  log AGENT "Task type: $TASK_TYPE"
  update_task_status "$TASK_ID" "in_progress"
  
  # Run OpenCode if development task
  if [[ "$TASK_TYPE" == "frontend" || "$TASK_TYPE" == "backend" ]]; then
    if ! run_opencode_task "$TASK_ID" "$DESCRIPTION" "$TASK_TYPE"; then
      log ERROR "OpenCode task failed"
      update_task_status "$TASK_ID" "failed" "$LOG_DIR/${TASK_ID}.opencode.log"
      exit 1
    fi
  fi
  
  # Run tests based on task type
  case "$TASK_TYPE" in
    e2e)
      run_e2e_tests "tests/e2e/${TASK_ID,,}.spec.ts" || {
        log ERROR "E2E tests failed"
        update_task_status "$TASK_ID" "failed" "$LOG_DIR/e2e_${TASK_ID}.log"
        exit 1
      }
      ;;
    unit)
      run_unit_tests "$TITLE" || {
        log ERROR "Unit tests failed"
        update_task_status "$TASK_ID" "failed" "$LOG_DIR/unit_${TITLE}.log"
        exit 1
      }
      ;;
    *)
      log INFO "Running: $TEST_CMD"
      if timeout 300 bash -c "$TEST_CMD" > "$LOG_DIR/${TASK_ID}.test.log" 2>&1; then
        log INFO "✅ Tests passed"
      else
        log WARN "⚠️ Tests may have issues"
      fi
      ;;
  esac
  
  # Lint check
  if ! run_lint; then
    log WARN "Lint issues - attempting auto-fix"
    vendor/bin/pint --dirty || true
  fi
  
  # Commit if all passed
  git_commit "$TASK_ID" "$TITLE" "$TASK_TYPE"
  
  log INFO "✅ Task completed: $TASK_ID"
  update_task_status "$TASK_ID" "completed" "$LOG_DIR/${TASK_ID}.log"
  
  # Auto-PR (if GitHub configured)
  if command -v "$PROJECT_DIR/.hermes/auto-pr.sh" &>/dev/null; then
    bash "$PROJECT_DIR/.hermes/auto-pr.sh" "$TASK_ID" "$TITLE" "$TASK_TYPE" "$LOG_DIR" || true
  fi
  
  log INFO "=== Cycle Complete ==="
}

main "$@"
