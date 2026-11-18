#!/usr/bin/env bash
# Disposable manifest-runner proof for the ordered R2.1 then Christmas schema set.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
RUNNER="$ROOT/scripts/apply-migration-manifest.sh"
R21="$ROOT/db/migrations/20261104_owner_control_bug_tracker_r21.sql"
CHRISTMAS="$ROOT/db/migrations/create_christmas_advent_2026_claims_v1.sql"
WORK="$(mktemp -d)"; trap 'rm -rf "$WORK"' EXIT
manifest_sha() { sed 's/\r$//' "$1" | sha256sum | awk '{print $1}'; }

make_base() {
  local db="$1"
  sqlite3 "$db" 'CREATE TABLE tbl_bug_reports (bug_id INTEGER PRIMARY KEY, title TEXT); CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY); CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY); CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY);'
}

cp "$R21" "$WORK/20261104_owner_control_bug_tracker_r21.sql"
cp "$CHRISTMAS" "$WORK/create_christmas_advent_2026_claims_v1.sql"
r21_sha="$(manifest_sha "$WORK/20261104_owner_control_bug_tracker_r21.sql")"
christmas_sha="$(manifest_sha "$WORK/create_christmas_advent_2026_claims_v1.sql")"
printf 'owner-control-bug-tracker-r21-20261104|20261104_owner_control_bug_tracker_r21.sql|%s\nchristmas-advent-2026-claims-v1|create_christmas_advent_2026_claims_v1.sql|%s\n' "$r21_sha" "$christmas_sha" > "$WORK/manifest.txt"

DB="$WORK/ordered.sqlite"; make_base "$DB"
"$RUNNER" "$DB" "$WORK/manifest.txt"
[ "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 2 ]
[ "$(sqlite3 "$DB" "SELECT group_concat(migration_id, '|') FROM (SELECT migration_id FROM tbl_schema_migrations ORDER BY rowid);")" = 'owner-control-bug-tracker-r21-20261104|christmas-advent-2026-claims-v1' ]
[ "$(sqlite3 "$DB" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_christmas_advent_2026_claims';")" = 1 ]
"$RUNNER" "$DB" "$WORK/manifest.txt"
[ "$(sqlite3 "$DB" 'SELECT COUNT(*) FROM tbl_schema_migrations;')" = 2 ]

# The R2.1 entry may commit, but a malformed Christmas entry must leave no
# Christmas schema object and no Christmas ledger row.
FAIL_DB="$WORK/christmas-failure.sqlite"; make_base "$FAIL_DB"
cp "$CHRISTMAS" "$WORK/failing-christmas.sql"
printf '\nTHIS IS INVALID SQL;\n' >> "$WORK/failing-christmas.sql"
fail_sha="$(manifest_sha "$WORK/failing-christmas.sql")"
printf 'owner-control-bug-tracker-r21-20261104|20261104_owner_control_bug_tracker_r21.sql|%s\nchristmas-advent-2026-claims-v1|failing-christmas.sql|%s\n' "$r21_sha" "$fail_sha" > "$WORK/failing-manifest.txt"
if "$RUNNER" "$FAIL_DB" "$WORK/failing-manifest.txt"; then exit 1; fi
[ "$(sqlite3 "$FAIL_DB" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_christmas_advent_2026_claims';")" = 0 ]
[ "$(sqlite3 "$FAIL_DB" "SELECT COUNT(*) FROM tbl_schema_migrations WHERE migration_id='christmas-advent-2026-claims-v1';")" = 0 ]

# Duplicate, missing, and checksum-invalid entries are rejected before ledger creation.
PRECHECK_DB="$WORK/precheck.sqlite"; make_base "$PRECHECK_DB"
printf 'duplicate-id|20261104_owner_control_bug_tracker_r21.sql|%s\nduplicate-id|20261104_owner_control_bug_tracker_r21.sql|%s\n' "$r21_sha" "$r21_sha" > "$WORK/duplicate-manifest.txt"
if "$RUNNER" "$PRECHECK_DB" "$WORK/duplicate-manifest.txt"; then exit 1; fi
[ "$(sqlite3 "$PRECHECK_DB" "SELECT COUNT(*) FROM sqlite_master WHERE name='tbl_schema_migrations';")" = 0 ]
printf 'missing-entry|missing.sql|%064d\n' 0 > "$WORK/missing-manifest.txt"
if "$RUNNER" "$PRECHECK_DB" "$WORK/missing-manifest.txt"; then exit 1; fi
printf 'checksum-entry|20261104_owner_control_bug_tracker_r21.sql|%064d\n' 0 > "$WORK/checksum-manifest.txt"
if "$RUNNER" "$PRECHECK_DB" "$WORK/checksum-manifest.txt"; then exit 1; fi
[ "$(sqlite3 "$PRECHECK_DB" "SELECT COUNT(*) FROM sqlite_master WHERE name='tbl_schema_migrations';")" = 0 ]


# Exercise the real published draft entry using the same runner and a fixture
# that snapshots protected sentinel tables before and after the migration.
DRAFT_SQL="$ROOT/db/migrations/create_christmas_advent_2026_draft_revisions_v1.sql"
DRAFT_ID='christmas-advent-2026-draft-revisions-v1'
DRAFT_LINE="$(grep -F "$DRAFT_ID|" "$ROOT/db/migrations/manifest-v1.txt" | tr -d '\r')"
[ "$(printf '%s\n' "$DRAFT_LINE" | wc -l | tr -d ' ')" = 1 ]
printf '%s\n' "$DRAFT_LINE" > "$WORK/draft-manifest.txt"
cp "$DRAFT_SQL" "$WORK/create_christmas_advent_2026_draft_revisions_v1.sql"
DRAFT_DB="$WORK/draft.sqlite"
sqlite3 "$DRAFT_DB" "CREATE TABLE tbl_user_scores(id INTEGER PRIMARY KEY, score INTEGER); INSERT INTO tbl_user_scores VALUES(1,42); CREATE TABLE tbl_user_inventory(id INTEGER PRIMARY KEY, quantity INTEGER); INSERT INTO tbl_user_inventory VALUES(1,3); CREATE TABLE tbl_staking_fixture(id INTEGER PRIMARY KEY); INSERT INTO tbl_staking_fixture VALUES(1); CREATE TABLE tbl_lab_fixture(id INTEGER PRIMARY KEY); INSERT INTO tbl_lab_fixture VALUES(1);"
"$RUNNER" "$DRAFT_DB" "$WORK/draft-manifest.txt"
[ "$(sqlite3 "$DRAFT_DB" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_christmas_advent_2026_draft_revisions';")" = 1 ]
[ "$(sqlite3 "$DRAFT_DB" "SELECT COUNT(*) FROM tbl_schema_migrations WHERE migration_id='$DRAFT_ID' AND checksum='$(manifest_sha "$DRAFT_SQL")';")" = 1 ]
[ "$(sqlite3 "$DRAFT_DB" 'SELECT score FROM tbl_user_scores WHERE id=1;')" = 42 ]
[ "$(sqlite3 "$DRAFT_DB" 'SELECT quantity FROM tbl_user_inventory WHERE id=1;')" = 3 ]
[ "$(sqlite3 "$DRAFT_DB" 'SELECT COUNT(*) FROM tbl_staking_fixture;')" = 1 ]
[ "$(sqlite3 "$DRAFT_DB" 'SELECT COUNT(*) FROM tbl_lab_fixture;')" = 1 ]
"$RUNNER" "$DRAFT_DB" "$WORK/draft-manifest.txt"
[ "$(sqlite3 "$DRAFT_DB" "SELECT COUNT(*) FROM tbl_schema_migrations WHERE migration_id='$DRAFT_ID';")" = 1 ]
[ "$(sqlite3 "$DRAFT_DB" 'PRAGMA integrity_check;')" = ok ]

# A deliberately malformed copy must roll back the draft table and ledger.
cp "$DRAFT_SQL" "$WORK/draft-failing.sql"
printf '\nTHIS IS INVALID SQL;\n' >> "$WORK/draft-failing.sql"
printf '%s|draft-failing.sql|%s\n' "$DRAFT_ID" "$(manifest_sha "$WORK/draft-failing.sql")" > "$WORK/draft-failing-manifest.txt"
DRAFT_FAIL_DB="$WORK/draft-failing.sqlite"
sqlite3 "$DRAFT_FAIL_DB" 'CREATE TABLE protected_sentinel(id INTEGER PRIMARY KEY); INSERT INTO protected_sentinel VALUES(9);'
if "$RUNNER" "$DRAFT_FAIL_DB" "$WORK/draft-failing-manifest.txt"; then exit 1; fi
[ "$(sqlite3 "$DRAFT_FAIL_DB" "SELECT COUNT(*) FROM sqlite_master WHERE name IN ('tbl_christmas_advent_2026_draft_revisions','tbl_schema_migrations');")" = 0 ]
[ "$(sqlite3 "$DRAFT_FAIL_DB" 'SELECT id FROM protected_sentinel;')" = 9 ]
echo 'PASS Christmas draft manifest checksum, atomic ledger, repeat and rollback'

echo 'PASS test-migration-runner-christmas-v1 ordered manifest, rollback, and preflight validation'
