#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
cp "$ROOT/db/migrations/manifest-v1.txt" "$WORK/manifest.txt"
while IFS='|' read -r id file checksum; do
  checksum="${checksum%$'\r'}"
  [ -z "$id" ] || [[ "$id" == \#* ]] && continue
  sed 's/\r$//' "$ROOT/db/migrations/$file" > "$WORK/$file"
done < "$WORK/manifest.txt"
DB="$WORK/test.sqlite"
sqlite3 "$DB" 'CREATE TABLE tbl_mouse_lp_store_offers (offer_id INTEGER PRIMARY KEY, is_active INTEGER NOT NULL); CREATE TABLE tbl_schema_migrations (migration_id TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP);'
while IFS='|' read -r id file checksum; do
  checksum="${checksum%$'\r'}"
  [ -z "$id" ] || [[ "$id" == \#* ]] && continue
  [[ "$id" == owner-control-lab-store-offer-state-m2a-20261113 || "$id" == owner-control-lab-store-offer-commercial-m2b-20261115 ]] && continue
  sqlite3 "$DB" "INSERT INTO tbl_schema_migrations (migration_id,checksum) VALUES ('$id','$checksum');"
done < "$WORK/manifest.txt"
"$ROOT/scripts/apply-migration-manifest.sh" "$DB" "$WORK/manifest.txt"
test "$(sqlite3 "$DB" "SELECT group_concat(migration_id,'|') FROM (SELECT migration_id FROM tbl_schema_migrations ORDER BY rowid DESC LIMIT 2);")" = 'owner-control-lab-store-offer-commercial-m2b-20261115|owner-control-lab-store-offer-state-m2a-20261113'
SHA="$(sha256sum "$WORK/20261115_owner_control_lab_store_offer_commercial_m2b.sql" | awk '{print $1}')"
test "$(sqlite3 "$DB" "SELECT checksum FROM tbl_schema_migrations WHERE migration_id='owner-control-lab-store-offer-commercial-m2b-20261115';")" = "$SHA"
"$ROOT/scripts/apply-migration-manifest.sh" "$DB" "$WORK/manifest.txt"
test "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = "$(grep -cvE '^(#|$)' "$WORK/manifest.txt")"
printf 'CREATE TABLE should_rollback(id INTEGER);\nBROKEN SQL;\n' > "$WORK/broken.sql"
SHA="$(sha256sum "$WORK/broken.sql" | awk '{print $1}')"
printf 'broken-test|broken.sql|%s\n' "$SHA" > "$WORK/broken-manifest.txt"
sqlite3 "$WORK/broken.sqlite" 'CREATE TABLE baseline(id INTEGER);'
if "$ROOT/scripts/apply-migration-manifest.sh" "$WORK/broken.sqlite" "$WORK/broken-manifest.txt"; then exit 1; fi
test "$(sqlite3 "$WORK/broken.sqlite" "SELECT COUNT(*) FROM sqlite_master WHERE name IN ('should_rollback','tbl_schema_migrations');")" = 0
echo 'PASS: M2A then M2B manifest application, checksum, repeat and failure rollback'
