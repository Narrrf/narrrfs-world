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
sqlite3 "$DB" 'CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY);'
sqlite3 "$DB" < "$ROOT/db/migrations/create_mouse_lp_store_v1.sql"
bash "$ROOT/scripts/apply-migration-manifest.sh" "$DB" "$WORK/manifest.txt"
test "$(sqlite3 "$DB" "SELECT group_concat(migration_id,'|') FROM (SELECT migration_id FROM tbl_schema_migrations ORDER BY rowid);")" = 'owner-control-bug-tracker-r21-20261104|christmas-advent-2026-claims-v1|owner-control-lab-store-drafts-m1-20261107|owner-control-lab-store-drafts-m11-20261109|owner-control-lab-store-offer-state-m2a-20261113|owner-control-lab-store-offer-commercial-m2b-20261115|owner-control-lab-store-publish-m2c-20261115'
expected="$(sha256sum "$WORK/20261115_owner_control_lab_store_publish_m2c.sql" | awk '{print $1}')"
test "$(sqlite3 "$DB" "SELECT checksum FROM tbl_schema_migrations WHERE migration_id='owner-control-lab-store-publish-m2c-20261115';")" = "$expected"
bash "$ROOT/scripts/apply-migration-manifest.sh" "$DB" "$WORK/manifest.txt"
test "$(sqlite3 "$DB" "SELECT COUNT(*) FROM tbl_schema_migrations WHERE migration_id='owner-control-lab-store-publish-m2c-20261115';")" = 1
printf 'CREATE TABLE should_rollback(id INTEGER);\nBROKEN SQL;\n' > "$WORK/broken.sql"
sha="$(sha256sum "$WORK/broken.sql" | awk '{print $1}')"
printf 'broken-test|broken.sql|%s\n' "$sha" > "$WORK/broken-manifest.txt"
sqlite3 "$WORK/broken.sqlite" 'CREATE TABLE baseline(id INTEGER);'
if bash "$ROOT/scripts/apply-migration-manifest.sh" "$WORK/broken.sqlite" "$WORK/broken-manifest.txt"; then exit 1; fi
test "$(sqlite3 "$WORK/broken.sqlite" "SELECT COUNT(*) FROM sqlite_master WHERE name IN ('should_rollback','tbl_schema_migrations');")" = 0
echo 'PASS: M2C manifest apply/checksum/repeat/failure rollback'
