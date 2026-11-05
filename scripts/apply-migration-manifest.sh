#!/usr/bin/env bash
# Fail-closed SQLite migration publisher. It accepts only the explicit manifest
# and records a migration only in the transaction that successfully applies it.
set -euo pipefail

DB_PATH="${1:?runtime database path required}"
MANIFEST_PATH="${2:?manifest path required}"
SQLITE_BIN="${NARRRFS_SQLITE_BIN:-sqlite3}"

fail() { echo "migration runner: $*" >&2; exit 1; }
command -v "$SQLITE_BIN" >/dev/null 2>&1 || fail 'sqlite3 unavailable'
[ -f "$DB_PATH" ] && [ -r "$DB_PATH" ] && [ -w "$DB_PATH" ] || fail 'runtime database unavailable'
[ -f "$MANIFEST_PATH" ] && [ -r "$MANIFEST_PATH" ] || fail 'manifest unavailable'

ROOT="$(cd "$(dirname "$MANIFEST_PATH")" && pwd)"
entries=()
declare -A seen_ids=()
while IFS='|' read -r id relative expected; do
  [ -z "${id:-}" ] && continue
  [[ "$id" =~ ^# ]] && continue
  [[ "$id" =~ ^[a-z0-9][a-z0-9-]{2,127}$ ]] || fail 'invalid migration id'
  [ -z "${seen_ids[$id]+present}" ] || fail "duplicate migration id: $id"
  seen_ids[$id]=1
  [[ "$relative" =~ ^[A-Za-z0-9._-]+\.sql$ ]] || fail 'invalid migration path'
  [[ "$expected" =~ ^[a-f0-9]{64}$ ]] || fail 'invalid manifest checksum'
  sql="$ROOT/$relative"
  [ -f "$sql" ] && [ -r "$sql" ] || fail "migration file missing: $relative"
  actual="$(sha256sum "$sql" | awk '{print $1}')"
  [ "$actual" = "$expected" ] || fail "migration checksum mismatch: $relative"
  entries+=("$id|$sql|$expected")
done < "$MANIFEST_PATH"
[ "${#entries[@]}" -gt 0 ] || fail 'manifest has no migrations'

# Validation above occurs before this creates even the ledger table.
for entry in "${entries[@]}"; do
  IFS='|' read -r id sql checksum <<< "$entry"
  ledger_exists="$("$SQLITE_BIN" -readonly "$DB_PATH" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_schema_migrations';" 2>/dev/null)" \
    || fail 'migration ledger inspection failed'
  if [ "$ledger_exists" = '1' ]; then
    existing="$("$SQLITE_BIN" -readonly "$DB_PATH" "SELECT COALESCE((SELECT checksum FROM tbl_schema_migrations WHERE migration_id='$id'), '');" 2>/dev/null)" \
      || fail 'applied migration inspection failed'
  else
    existing=''
  fi
  if [ -n "$existing" ]; then
    [ "$existing" = "$checksum" ] || fail "applied migration checksum mismatch: $id"
    continue
  fi
  tmp="$(mktemp "$(dirname "$DB_PATH")/.migration.XXXXXX.sql")"
  trap 'rm -f -- "$tmp"' EXIT
  {
    echo '.bail on'
    echo 'BEGIN IMMEDIATE;'
    echo 'CREATE TABLE IF NOT EXISTS tbl_schema_migrations (migration_id TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP);'
    cat "$sql"
    printf "INSERT INTO tbl_schema_migrations (migration_id, checksum) VALUES ('%s', '%s');\n" "$id" "$checksum"
    echo 'COMMIT;'
  } > "$tmp"
  "$SQLITE_BIN" -bail "$DB_PATH" < "$tmp" || fail "migration failed: $id"
  rm -f -- "$tmp"; trap - EXIT
done
