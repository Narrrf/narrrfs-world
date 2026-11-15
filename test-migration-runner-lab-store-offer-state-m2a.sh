#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
RUNNER="$ROOT/scripts/apply-migration-manifest.sh"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

manifest_sha() { sed 's/\r$//' "$1" | sha256sum | awk '{print $1}'; }

SQL="$ROOT/db/migrations/20261113_owner_control_lab_store_offer_state_m2a.sql"
cp "$ROOT/db/migrations/manifest-v1.txt" "$WORK/manifest.txt"
while IFS='|' read -r migration_id migration_file migration_checksum; do
  migration_checksum="${migration_checksum%$'\r'}"
  [ -z "$migration_id" ] || [[ "$migration_id" == \#* ]] && continue
  sed 's/\r$//' "$ROOT/db/migrations/$migration_file" > "$WORK/$migration_file"
done < "$WORK/manifest.txt"
SHA="$(manifest_sha "$WORK/20261113_owner_control_lab_store_offer_state_m2a.sql")"

DB="$WORK/valid.sqlite"
sqlite3 "$DB" 'CREATE TABLE tbl_mouse_lp_store_offers (offer_id INTEGER PRIMARY KEY, is_active INTEGER NOT NULL); INSERT INTO tbl_mouse_lp_store_offers VALUES (1, 1); CREATE TABLE tbl_schema_migrations (migration_id TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP);'
while IFS='|' read -r migration_id migration_file migration_checksum; do
  migration_checksum="${migration_checksum%$'\r'}"
  [ -z "$migration_id" ] || [[ "$migration_id" == \#* ]] && continue
  [ "$migration_id" = 'owner-control-lab-store-offer-state-m2a-20261113' ] && continue
  sqlite3 "$DB" "INSERT INTO tbl_schema_migrations (migration_id, checksum) VALUES ('$migration_id', '$migration_checksum');"
done < "$WORK/manifest.txt"
"$RUNNER" "$DB" "$WORK/manifest.txt"
test "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 5
test "$(sqlite3 "$DB" 'SELECT owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=1;')" = 1
test "$(sqlite3 "$DB" "SELECT checksum FROM tbl_schema_migrations WHERE migration_id='owner-control-lab-store-offer-state-m2a-20261113';")" = "$SHA"
"$RUNNER" "$DB" "$WORK/manifest.txt"
test "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 5

printf 'CREATE TABLE m2a_should_roll_back (id INTEGER);\nBROKEN SQL;\n' > "$WORK/broken.sql"
BROKEN_SHA="$(manifest_sha "$WORK/broken.sql")"
printf 'm2a-disposable-failure|broken.sql|%s\n' "$BROKEN_SHA" > "$WORK/broken-manifest.txt"
BROKEN_DB="$WORK/broken.sqlite"
sqlite3 "$BROKEN_DB" 'CREATE TABLE tbl_mouse_lp_store_offers (offer_id INTEGER PRIMARY KEY, is_active INTEGER NOT NULL);'
if "$RUNNER" "$BROKEN_DB" "$WORK/broken-manifest.txt"; then
  echo 'FAIL: deliberately broken migration unexpectedly applied' >&2
  exit 1
fi
test "$(sqlite3 "$BROKEN_DB" "SELECT COUNT(*) FROM sqlite_master WHERE name='m2a_should_roll_back';")" = 0
test "$(sqlite3 "$BROKEN_DB" "SELECT COUNT(*) FROM sqlite_master WHERE name='tbl_schema_migrations';")" = 0

echo 'PASS: M2A real manifest runner applies, records checksum, is idempotent, and rolls back failing SQL'
