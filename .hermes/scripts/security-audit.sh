#!/bin/bash
# HRConnect Security Audit Script
# Runs dependency checks, file permissions, and auth logs
# Usage: bash .hermes/scripts/security-audit.sh

set -e

PROJECT_DIR="/home/merger/hrconnect"
cd "$PROJECT_DIR"

echo "═══════════════════════════════════════════════════"
echo "  HRConnect Security Audit"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "═══════════════════════════════════════════════════"
echo ""

ISSUES=0

# 1. Composer vulnerabilities
echo "▸ Checking Composer dependencies..."
COMPOSER_AUDIT=$(composer audit --no-interaction 2>&1)
if echo "$COMPOSER_AUDIT" | grep -q "No security vulnerability"; then
    echo "  ✅ Composer: 0 vulnerabilities"
else
    echo "  ❌ Composer: VULNERABILITIES FOUND"
    echo "$COMPOSER_AUDIT" | grep -E "Found|Severity|Package" | head -10
    ISSUES=$((ISSUES + 1))
fi

# 2. npm vulnerabilities
echo ""
echo "▸ Checking npm dependencies..."
NPM_AUDIT=$(npm audit --omit=dev 2>&1 || true)
NPM_VULNS=$(echo "$NPM_AUDIT" | grep -oP '\d+ vulnerabilities' | head -1)
if echo "$NPM_VULNS" | grep -q "0 "; then
    echo "  ✅ npm: 0 vulnerabilities"
else
    echo "  ⚠️  npm: $NPM_VULNS"
    echo "$NPM_AUDIT" | grep -E "Severity:|severity:" | head -5
fi

# 3. .env permissions
echo ""
echo "▸ Checking .env permissions..."
ENV_PERMS=$(stat -c '%a' .env 2>/dev/null || echo "MISSING")
if [ "$ENV_PERMS" = "600" ]; then
    echo "  ✅ .env permissions: 600 (secure)"
else
    echo "  ❌ .env permissions: $ENV_PERMS (should be 600)"
    ISSUES=$((ISSUES + 1))
fi

# 4. Database user
echo ""
echo "▸ Checking database configuration..."
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)
if [ "$DB_USER" != "root" ]; then
    echo "  ✅ DB user: $DB_USER (not root)"
else
    echo "  ❌ DB user: root (should be dedicated user)"
    ISSUES=$((ISSUES + 1))
fi

# 5. Active Sanctum tokens
echo ""
echo "▸ Checking active Sanctum tokens..."
TOKEN_COUNT=$(php artisan tinker --execute="echo DB::table('personal_access_tokens')->count();" 2>/dev/null | tail -1 || echo "0")
echo "  ℹ️  Active tokens: $TOKEN_COUNT"

# 6. Laravel config
echo ""
echo "▸ Checking Laravel configuration..."
APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d'=' -f2)
APP_ENV=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
echo "  ℹ️  APP_ENV=$APP_ENV"
echo "  ℹ️  APP_DEBUG=$APP_DEBUG"

if [ "$APP_ENV" = "production" ] && [ "$APP_DEBUG" = "true" ]; then
    echo "  ⚠️  APP_DEBUG=true in production!"
    ISSUES=$((ISSUES + 1))
fi

# 7. Database connections
echo ""
echo "▸ Checking database connections..."
DB_CONNS=$(psql -U postgres -t -c "SELECT count(*) FROM pg_stat_activity WHERE datname = 'hris_payroll';" 2>/dev/null || echo "?")
echo "  ℹ️  Open connections: $DB_CONNS"

# Summary
echo ""
echo "═══════════════════════════════════════════════════"
if [ $ISSUES -eq 0 ]; then
    echo "  ✅ All checks passed — no issues found"
else
    echo "  ⚠️  $ISSUES issue(s) detected — review needed"
fi
echo "═══════════════════════════════════════════════════"
