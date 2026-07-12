#!/usr/bin/env bash
# HRConnect Autonomous Dev Setup
# Initializes all automation components and creates Hermes cron job

set -e

PROJECT_DIR="/home/merger/hrconnect"
cd "$PROJECT_DIR"

echo "🚀 HRConnect Autonomous Development Setup"
echo "=========================================="

# Verify prerequisites
echo "✓ Checking prerequisites..."

if ! command -v opencode &> /dev/null; then
  echo "❌ OpenCode not found. Install: npm i -g opencode-ai@latest"
  exit 1
fi

if ! command -v npm &> /dev/null; then
  echo "❌ npm not found"
  exit 1
fi

if ! command -v php &> /dev/null; then
  echo "❌ PHP not found"
  exit 1
fi

if ! command -v hermes &> /dev/null; then
  echo "❌ Hermes not found"
  exit 1
fi

echo "✅ All prerequisites found"

# Initialize directories
echo "✓ Initializing directories..."
mkdir -p .hermes/cron-logs
mkdir -p tests/e2e

# Test environment setup
echo "✓ Setting up test environment..."
npm install --save-dev @playwright/test 2>/dev/null || true

# Verify Laravel setup
echo "✓ Verifying Laravel setup..."
php artisan config:clear
php artisan cache:clear

# Create Hermes cron job
echo "✓ Creating Hermes cron job..."

CRON_JOB_NAME="hrconnect-autonomous-dev"

# Delete existing job if present
hermes cron list 2>/dev/null | grep -q "$CRON_JOB_NAME" && hermes cron remove "$CRON_JOB_NAME" || true

# Create new cron job - every 6 hours
# hermes cron create schedule prompt [options]
hermes cron create \
  "0 */6 * * *" \
  "Run HRConnect autonomous development cycle using OpenCode and E2E testing" \
  --name "$CRON_JOB_NAME" \
  --script ".hermes/cron-dev-worker.sh" \
  --no-agent

echo "✅ Cron job created: $CRON_JOB_NAME"

# Display job info
echo ""
echo "📋 Cron Job Details:"
hermes cron list | grep "$CRON_JOB_NAME" || true

echo ""
echo "✅ Setup Complete!"
echo ""
echo "Next steps:"
echo "1. Test manually: bash .hermes/cron-dev-worker.sh"
echo "2. View logs: tail -f .hermes/cron-logs/\$(date +%Y%m%d).log"
echo "3. Check metrics: cat .hermes/metrics.json | jq ."
echo "4. Pause/resume: hermes cron pause/resume $CRON_JOB_NAME"
echo ""
echo "Documentation: cat .hermes/README.md"
