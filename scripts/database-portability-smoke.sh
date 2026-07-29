#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-sqlite}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SMOKE_TESTS=(
  tests/Feature/HealthEndpointTest.php
  tests/Feature/WilayahApiHardeningTest.php
  tests/Feature/AuthenticationTest.php
  tests/Feature/AdminLeaveApprovalTest.php
  tests/Feature/ApprovalWorkflowTest.php
  tests/Feature/AttendanceMediaAndApiTest.php
  tests/Feature/DynamicBarcodeTest.php
  tests/Feature/WorkFromHomeRequestFlowTest.php
  tests/Feature/IndonesiaPayrollCalculatorTest.php
)

run_artisan_with_env() {
  env \
    APP_ENV=testing \
    APP_DEBUG=true \
    APP_URL=http://127.0.0.1 \
    CACHE_STORE=array \
    QUEUE_CONNECTION=sync \
    SESSION_DRIVER=array \
    MAIL_MAILER=array \
    BROADCAST_CONNECTION=log \
    "$@"
}

run_sqlite_smoke() {
  local sqlite_db
  sqlite_db="$(mktemp /tmp/paspapan_sqlite_smoke.XXXXXX)"

  cleanup_sqlite() {
    if [ -n "${sqlite_db:-}" ]; then
      rm -f "$sqlite_db"
    fi
  }
  trap cleanup_sqlite EXIT

  cd "$ROOT_DIR"
  run_artisan_with_env php artisan optimize:clear

  run_artisan_with_env \
    DB_CONNECTION=sqlite \
    DB_DATABASE="$sqlite_db" \
    php artisan migrate:fresh --force

  run_artisan_with_env \
    DB_CONNECTION=sqlite \
    DB_DATABASE="$sqlite_db" \
    php artisan test "${SMOKE_TESTS[@]}"
}

run_postgres_smoke() {
  local pg_host="${PASPAPAN_PG_HOST:-localhost}"
  local pg_port="${PASPAPAN_PG_PORT:-5432}"
  local pg_user="${PASPAPAN_PG_USER:-${USER:-postgres}}"
  local pg_password="${PASPAPAN_PG_PASSWORD:-}"
  local pg_admin_db="${PASPAPAN_PG_ADMIN_DB:-postgres}"
  local pg_schema="${PASPAPAN_PG_SCHEMA:-public}"
  local pg_sslmode="${PASPAPAN_PG_SSLMODE:-prefer}"
  local db_name="paspapan_pg_smoke_$(date +%Y%m%d%H%M%S)_${RANDOM}"

  psql_cmd() {
    PGPASSWORD="$pg_password" psql \
      -h "$pg_host" \
      -p "$pg_port" \
      -U "$pg_user" \
      -d "$pg_admin_db" \
      -v ON_ERROR_STOP=1 \
      "$@"
  }

  cleanup_postgres() {
    if [ -z "${db_name:-}" ]; then
      return
    fi

    PGPASSWORD="$pg_password" psql \
      -h "$pg_host" \
      -p "$pg_port" \
      -U "$pg_user" \
      -d "$pg_admin_db" \
      -v ON_ERROR_STOP=1 \
      -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${db_name}';" >/dev/null 2>&1 || true

    PGPASSWORD="$pg_password" psql \
      -h "$pg_host" \
      -p "$pg_port" \
      -U "$pg_user" \
      -d "$pg_admin_db" \
      -v ON_ERROR_STOP=1 \
      -c "DROP DATABASE IF EXISTS \"${db_name}\";" >/dev/null 2>&1 || true
  }
  trap cleanup_postgres EXIT

  cd "$ROOT_DIR"
  run_artisan_with_env php artisan optimize:clear

  psql_cmd -c "CREATE DATABASE \"${db_name}\";"

  run_artisan_with_env \
    DB_CONNECTION=pgsql \
    DB_HOST="$pg_host" \
    DB_PORT="$pg_port" \
    DB_DATABASE="$db_name" \
    DB_USERNAME="$pg_user" \
    DB_PASSWORD="$pg_password" \
    DB_SCHEMA="$pg_schema" \
    DB_SSLMODE="$pg_sslmode" \
    php artisan migrate --force

  run_artisan_with_env \
    DB_CONNECTION=pgsql \
    DB_HOST="$pg_host" \
    DB_PORT="$pg_port" \
    DB_DATABASE="$db_name" \
    DB_USERNAME="$pg_user" \
    DB_PASSWORD="$pg_password" \
    DB_SCHEMA="$pg_schema" \
    DB_SSLMODE="$pg_sslmode" \
    php artisan paspapan:seed-real

  run_artisan_with_env \
    DB_CONNECTION=pgsql \
    DB_HOST="$pg_host" \
    DB_PORT="$pg_port" \
    DB_DATABASE="$db_name" \
    DB_USERNAME="$pg_user" \
    DB_PASSWORD="$pg_password" \
    DB_SCHEMA="$pg_schema" \
    DB_SSLMODE="$pg_sslmode" \
    php artisan test "${SMOKE_TESTS[@]}"
}

run_mysql_smoke() {
  local mysql_host="${PASPAPAN_MYSQL_HOST:-127.0.0.1}"
  local mysql_port="${PASPAPAN_MYSQL_PORT:-3306}"
  local mysql_user="${PASPAPAN_MYSQL_USER:-root}"
  local mysql_password="${PASPAPAN_MYSQL_PASSWORD:-}"
  local db_name="paspapan_mysql_smoke_$(date +%Y%m%d%H%M%S)_${RANDOM}"

  mysql_cmd() {
    MYSQL_PWD="$mysql_password" mysql \
      -h "$mysql_host" \
      -P "$mysql_port" \
      -u "$mysql_user" \
      "$@"
  }

  cleanup_mysql() {
    if [ -z "${db_name:-}" ]; then
      return
    fi

    MYSQL_PWD="$mysql_password" mysql \
      -h "$mysql_host" \
      -P "$mysql_port" \
      -u "$mysql_user" \
      -e "DROP DATABASE IF EXISTS \`${db_name}\`;" >/dev/null 2>&1 || true
  }
  trap cleanup_mysql EXIT

  cd "$ROOT_DIR"
  run_artisan_with_env php artisan optimize:clear

  mysql_cmd -e "CREATE DATABASE \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

  run_artisan_with_env \
    DB_CONNECTION=mysql \
    DB_HOST="$mysql_host" \
    DB_PORT="$mysql_port" \
    DB_DATABASE="$db_name" \
    DB_USERNAME="$mysql_user" \
    DB_PASSWORD="$mysql_password" \
    php artisan migrate --force

  run_artisan_with_env \
    DB_CONNECTION=mysql \
    DB_HOST="$mysql_host" \
    DB_PORT="$mysql_port" \
    DB_DATABASE="$db_name" \
    DB_USERNAME="$mysql_user" \
    DB_PASSWORD="$mysql_password" \
    php artisan test "${SMOKE_TESTS[@]}"
}

case "$MODE" in
  sqlite)
    run_sqlite_smoke
    ;;
  pgsql|postgres|postgresql)
    command -v psql >/dev/null 2>&1 || {
      echo "psql is required for PostgreSQL portability smoke." >&2
      exit 1
    }
    run_postgres_smoke
    ;;
  mysql)
    command -v mysql >/dev/null 2>&1 || {
      echo "mysql client is required for MySQL compatibility smoke." >&2
      exit 1
    }
    run_mysql_smoke
    ;;
  *)
    echo "Usage: $0 [sqlite|pgsql|mysql]" >&2
    exit 2
    ;;
esac
