#!/usr/bin/env bash
#
# run-all.sh — Run all HRConnect K6 performance test suites.
#
# Usage:
#   bash tests/performance/run-all.sh
#
# Env vars:
#   BASE_URL          Server URL (default: http://localhost:8000)
#   K6_TOKEN_EMPLOYEE Sanctum token for employee role
#   K6_TOKEN_ADMIN    Sanctum token for admin role
#   K6_TOKEN_MANAGER  Sanctum token for manager role
#   K6_VUS            Virtual users per suite (default: 3)
#   K6_DURATION       Duration per suite (default: 30s)
#
set -euo pipefail

RESULTS_DIR="tests/performance/results"
SUITES_DIR="tests/performance/suites"

# Ensure results directory exists
mkdir -p "$RESULTS_DIR"

# Clean old results
rm -f "$RESULTS_DIR"/*.json

echo "╔══════════════════════════════════════════════════╗"
echo "║  HRConnect Performance Test — K6 Suite Runner    ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "BASE_URL:    ${BASE_URL:-http://localhost:8000}"
echo "VUS:         ${K6_VUS:-3}"
echo "Duration:    ${K6_DURATION:-30s}"
echo ""

# Export env vars for suites
export BASE_URL="${BASE_URL:-http://localhost:8000}"
export K6_VUS="${K6_VUS:-3}"
export K6_DURATION="${K6_DURATION:-30s}"

# Suite definitions: name, threshold note
SUITES=(
  "auth:Login + profile + password"
  "employees:CRUD + master data"
  "attendance:Clock-in + history + schedule + corrections"
  "leave:Leave + overtime + approvals"
  "payroll:Payroll + payslip + reimbursement (CRUD)"
  "reports:Dashboard + KB read + notifications"
  "operations:Shift swap + WFH + documents + checklists + kasbon + announcements"
  "assets:Asset CRUD + appraisals + settings"
  "kb-chat:KB RAG chat (Gemini AI inference)"
)

TOTAL_START=$(date +%s)

for entry in "${SUITES[@]}"; do
  IFS=':' read -r name desc <<< "$entry"
  outfile="$RESULTS_DIR/${name}.json"
  suitefile="$SUITES_DIR/${name}.js"

  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "▶ Suite: $name — $desc"
  echo "  Output: $outfile"
  echo ""

  k6 run \
    --out json="$outfile" \
    "$suitefile" || {
      echo "⚠️  Suite '$name' exited with non-zero status (thresholds may have been crossed)"
    }

  echo ""
done

TOTAL_END=$(date +%s)
TOTAL_SECS=$((TOTAL_END - TOTAL_START))

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ All suites completed in ${TOTAL_SECS}s"
echo ""
echo "Results:"
ls -lh "$RESULTS_DIR"/*.json 2>/dev/null || echo "  (no results found)"
echo ""
echo "Next step:"
echo "  python3 tests/performance/generate_report.py"
