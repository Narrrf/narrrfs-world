#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
RUNNER="$ROOT/scripts/apply-migration-manifest.sh"
MIGRATION="$ROOT/db/migrations/20261104_owner_control_bug_tracker_r21.sql"
WORK="$(mktemp -d)"; trap 'rm -rf "$WORK"' EXIT
DB="$WORK/runtime.sqlite"; MANIFEST="$WORK/manifest.txt"
sqlite3 "$DB" 'CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY, title TEXT, owner_control_version INTEGER);'
# Representative pre-migration schema deliberately has no version column.
sqlite3 "$DB" "DROP TABLE tbl_bug_reports; CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY, title TEXT); INSERT INTO tbl_bug_reports VALUES (1, 'before');"
sha="$(sha256sum "$MIGRATION" | awk '{print $1}')"
printf 'owner-control-bug-tracker-r21-20261104|%s|%s\n' "$MIGRATION" "$sha" > "$MANIFEST"
# The runner accepts manifest-relative names; copy the immutable candidate only
# into the disposable directory for this test.
cp "$MIGRATION" "$WORK/20261104_owner_control_bug_tracker_r21.sql"
sed -i 's#.*|.*|#owner-control-bug-tracker-r21-20261104|20261104_owner_control_bug_tracker_r21.sql|#' "$MANIFEST"
"$RUNNER" "$DB" "$MANIFEST"
[ "$(sqlite3 "$DB" 'SELECT owner_control_version FROM tbl_bug_reports WHERE bug_id=1;')" = 1 ]
[ "$(sqlite3 "$DB" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_owner_control_bug_update_receipts';")" = 1 ]
[ "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 1 ]
before="$(sha256sum "$DB" | awk '{print $1}')"
"$RUNNER" "$DB" "$MANIFEST"
[ "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 1 ]
# Invalid manifest is rejected before the ledger/schema is created.
BAD="$WORK/bad.sqlite"; sqlite3 "$BAD" 'CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY);'
printf 'bad-id|missing.sql|%064d\n' 0 > "$WORK/bad.txt"
if "$RUNNER" "$BAD" "$WORK/bad.txt"; then exit 1; fi
[ "$(sqlite3 "$BAD" "SELECT COUNT(*) FROM sqlite_master WHERE name='tbl_schema_migrations';")" = 0 ]
# Duplicate IDs are rejected during manifest validation, before a ledger or
# schema object can be created.
DUP="$WORK/duplicate.sqlite"; sqlite3 "$DUP" 'CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY);'
cp "$MIGRATION" "$WORK/duplicate.sql"
dup_sha="$(sha256sum "$WORK/duplicate.sql" | awk '{print $1}')"
printf 'duplicate-r21|duplicate.sql|%s\nduplicate-r21|duplicate.sql|%s\n' "$dup_sha" "$dup_sha" > "$WORK/duplicate.txt"
before_duplicate="$(sha256sum "$DUP" | awk '{print $1}')"
if "$RUNNER" "$DUP" "$WORK/duplicate.txt"; then exit 1; fi
[ "$before_duplicate" = "$(sha256sum "$DUP" | awk '{print $1}')" ]
# A syntactically valid manifest whose migration fails must roll back both the
# candidate schema change and the ledger record.
FAIL="$WORK/fail.sqlite"; sqlite3 "$FAIL" 'CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY);'
printf 'ALTER TABLE tbl_bug_reports ADD COLUMN temporary_value TEXT;\nTHIS IS INVALID SQL;\n' > "$WORK/fail.sql"
fail_sha="$(sha256sum "$WORK/fail.sql" | awk '{print $1}')"
printf 'failure-proof-r21|fail.sql|%s\n' "$fail_sha" > "$WORK/fail.txt"
before_fail="$(sha256sum "$FAIL" | awk '{print $1}')"
if "$RUNNER" "$FAIL" "$WORK/fail.txt"; then exit 1; fi
after_fail="$(sha256sum "$FAIL" | awk '{print $1}')"
[ "$before_fail" = "$after_fail" ]
echo 'PASS test-migration-runner-r21-v1 manifest validation, migration, and idempotency'
