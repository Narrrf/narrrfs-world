#!/usr/bin/env bash
# Verified SQLite publication helper for Render's persistent /data database.
#
# This script is deliberately file-only: it never changes schema or rows.
# It creates a SQLite-consistent backup in the target directory, verifies it,
# and replaces the canonical file only after every verification succeeds.

set -u -o pipefail

MODE="${1:-publish}"
SCRIPT_PATH="${NARRRFS_DURABILITY_SCRIPT_PATH:-$0}"
SQLITE_BIN="${NARRRFS_SQLITE_BIN:-sqlite3}"
RUNTIME_DB="${NARRRFS_RUNTIME_DB_PATH:-/var/www/html/db/narrrf_world.sqlite}"
PERSISTENT_DB="${NARRRFS_PERSISTENT_DB_PATH:-/data/narrrf_world.sqlite}"
MIN_FREE_BYTES="${NARRRFS_DB_PUBLISH_MIN_FREE_BYTES:-67108864}"
LOCK_FILE="${PERSISTENT_DB}.publish.lock"
TEMP_DB=""

fail() {
    echo "SQLite durability: $*" >&2
    exit 1
}

cleanup_temp() {
    if [ -n "$TEMP_DB" ] && [ -e "$TEMP_DB" ]; then
        rm -f -- "$TEMP_DB"
    fi
}
trap cleanup_temp EXIT

require_tooling() {
    command -v "$SQLITE_BIN" >/dev/null 2>&1 || fail "sqlite3 is unavailable"
    command -v flock >/dev/null 2>&1 || fail "flock is unavailable"
    command -v sha256sum >/dev/null 2>&1 || fail "sha256sum is unavailable"
    command -v mktemp >/dev/null 2>&1 || fail "mktemp is unavailable"
    command -v stat >/dev/null 2>&1 || fail "stat is unavailable"
    command -v df >/dev/null 2>&1 || fail "df is unavailable"
    command -v awk >/dev/null 2>&1 || fail "awk is unavailable"
}

integrity_is_ok() {
    [ "$("$SQLITE_BIN" -readonly "$1" 'PRAGMA integrity_check;' 2>/dev/null)" = "ok" ]
}

file_size() {
    stat -c '%s' "$1"
}

free_bytes() {
    df -Pk "$1" | awk 'NR == 2 { print $4 * 1024 }'
}

make_backup_copy() {
    local source_db="$1"
    local target_db="$2"
    local target_dir
    local source_size
    local available
    local required

    [ -f "$source_db" ] || fail "source database is missing: $source_db"
    [ -r "$source_db" ] || fail "source database is unreadable: $source_db"
    target_dir="$(dirname "$target_db")"
    [ -d "$target_dir" ] || fail "target directory is missing: $target_dir"
    [ -w "$target_dir" ] || fail "target directory is not writable: $target_dir"

    source_size="$(file_size "$source_db")"
    [ "$source_size" -gt 0 ] || fail "source database is empty"
    available="$(free_bytes "$target_dir")"
    required=$((source_size + MIN_FREE_BYTES))
    [ "$available" -ge "$required" ] || fail "insufficient free space for verified publication"

    TEMP_DB="$(mktemp "$target_dir/.narrrf_world.sqlite.publish.XXXXXX")"
    # sqlite3 .backup creates its own destination; only this new temporary
    # artifact is removed before backup creation.
    rm -f -- "$TEMP_DB"

    "$SQLITE_BIN" "$source_db" ".backup '$TEMP_DB'" || fail "SQLite online backup failed"
    [ -s "$TEMP_DB" ] || fail "SQLite backup is empty"
    integrity_is_ok "$TEMP_DB" || fail "SQLite backup integrity check failed"

    local output_size output_hash
    output_size="$(file_size "$TEMP_DB")"
    output_hash="$(sha256sum "$TEMP_DB" | awk '{print $1}')"
    [ -n "$output_hash" ] || fail "SQLite backup hash is empty"

    mv -f -- "$TEMP_DB" "$target_db" || fail "atomic database rename failed"
    TEMP_DB=""
    echo "SQLite durability: published size=$output_size sha256=$output_hash"
}

publish_locked() {
    if [ -n "${NARRRFS_PUBLISHER_TEST_HOLD_SECONDS:-}" ]; then
        sleep "$NARRRFS_PUBLISHER_TEST_HOLD_SECONDS"
    fi
    make_backup_copy "$RUNTIME_DB" "$PERSISTENT_DB"
}

restore_locked() {
    [ -f "$PERSISTENT_DB" ] || fail "persistent canonical database is missing"
    [ -r "$PERSISTENT_DB" ] || fail "persistent canonical database is unreadable"
    integrity_is_ok "$PERSISTENT_DB" || fail "persistent canonical database integrity check failed"
    make_backup_copy "$PERSISTENT_DB" "$RUNTIME_DB"
}

case "$MODE" in
    publish)
        require_tooling
        flock -n "$LOCK_FILE" "$SCRIPT_PATH" publish-locked || fail "publisher lock unavailable or publication failed"
        ;;
    restore)
        require_tooling
        flock -n "$LOCK_FILE" "$SCRIPT_PATH" restore-locked || fail "publisher lock unavailable or restore failed"
        ;;
    publish-locked)
        publish_locked
        ;;
    restore-locked)
        restore_locked
        ;;
    *)
        fail "unsupported mode: $MODE"
        ;;
esac
