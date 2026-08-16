#!/usr/bin/env bash
# Backward-compat: full suite via run-e2e.sh (server + suite satu proses).
# Dipakai bila ada referensi lama ke nama ini; prefer `bash scripts/run-e2e.sh`.
exec bash "$(dirname "$0")/run-e2e.sh" "$@"
