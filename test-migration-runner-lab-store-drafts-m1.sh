#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"; RUNNER="$ROOT/scripts/apply-migration-manifest.sh"; SQL="$ROOT/db/migrations/20261107_owner_control_lab_store_drafts_m1.sql"; WORK="$(mktemp -d)"; trap 'rm -rf "$WORK"' EXIT
manifest_sha() { sed 's/\r$//' "$1" | sha256sum | awk '{print $1}'; }
cp "$SQL" "$WORK/20261107_owner_control_lab_store_drafts_m1.sql"; SHA="$(manifest_sha "$WORK/20261107_owner_control_lab_store_drafts_m1.sql")"; printf 'owner-control-lab-store-drafts-m1-20261107|20261107_owner_control_lab_store_drafts_m1.sql|%s\n' "$SHA" > "$WORK/manifest.txt"
DB="$WORK/test.sqlite"; sqlite3 "$DB" 'CREATE TABLE tbl_mouse_lp_store_offers (offer_id INTEGER PRIMARY KEY); CREATE TABLE tbl_mouse_lp_store_purchases (purchase_id INTEGER PRIMARY KEY);'
"$RUNNER" "$DB" "$WORK/manifest.txt"; [ "$(sqlite3 "$DB" "SELECT COUNT(*) FROM tbl_schema_migrations WHERE migration_id='owner-control-lab-store-drafts-m1-20261107';")" = 1 ]; [ "$(sqlite3 "$DB" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_owner_control_lab_store_drafts';")" = 1 ]
"$RUNNER" "$DB" "$WORK/manifest.txt"; [ "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 1 ]
sqlite3 "$WORK/precheck.sqlite" 'CREATE TABLE marker (id INTEGER);'
printf 'duplicate-id|20261107_owner_control_lab_store_drafts_m1.sql|%s\nduplicate-id|20261107_owner_control_lab_store_drafts_m1.sql|%s\n' "$SHA" "$SHA" > "$WORK/bad.txt"; if "$RUNNER" "$WORK/precheck.sqlite" "$WORK/bad.txt"; then exit 1; fi; [ "$(sqlite3 "$WORK/precheck.sqlite" "SELECT COUNT(*) FROM sqlite_master WHERE name='tbl_schema_migrations';")" = 0 ]
printf 'CREATE TABLE crlf_manifest_probe (id INTEGER PRIMARY KEY);\r\n' > "$WORK/crlf.sql"; CRLF_SHA="$(manifest_sha "$WORK/crlf.sql")"; printf 'crlf-manifest-probe|crlf.sql|%s\r\n' "$CRLF_SHA" > "$WORK/crlf-manifest.txt"; sqlite3 "$WORK/crlf.sqlite" 'VACUUM;'; "$RUNNER" "$WORK/crlf.sqlite" "$WORK/crlf-manifest.txt"; [ "$(sqlite3 "$WORK/crlf.sqlite" "SELECT COUNT(*) FROM tbl_schema_migrations WHERE migration_id='crlf-manifest-probe';")" = 1 ]
echo 'PASS: Lab Store M1 manifest migration is ordered, idempotent, and preflight fail-closed'
