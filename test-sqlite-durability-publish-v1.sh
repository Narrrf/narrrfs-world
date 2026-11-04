#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
PUBLISHER="$ROOT/scripts/sqlite-durability-publish.sh"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
NATIVE_SQLITE3="$(command -v sqlite3)"

mkdir -p "$WORK/bin" "$WORK/data" "$WORK/runtime"
cat > "$WORK/bin/sqlite3" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
native="${NARRRFS_TEST_NATIVE_SQLITE3:?native sqlite3 path is required}"
readonly_flag=''
if [ "${1:-}" = '-readonly' ]; then
    readonly_flag='-readonly'
    shift
fi
if [ "$#" -eq 1 ] && [ "$1" = '--version' ]; then
    exec "$native" --version
fi
if command -v cygpath >/dev/null 2>&1; then
    db="$(cygpath -am "$1")"
else
    db="$1"
fi
shift
if [ "$#" -eq 0 ]; then
    exec "$native" $readonly_flag "$db"
fi
sql="$1"
if [[ "$sql" =~ ^\.backup\ \'(.*)\'$ ]]; then
    if command -v cygpath >/dev/null 2>&1; then
        target="$(cygpath -am "${BASH_REMATCH[1]}")"
    else
        target="${BASH_REMATCH[1]}"
    fi
    sql=".backup \"$target\""
fi
exec "$native" $readonly_flag "$db" "$sql"
EOF
chmod +x "$WORK/bin/sqlite3"
cat > "$WORK/bin/sqlite3-fail-backup" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
for arg in "$@"; do
    if [[ "$arg" == .backup* ]]; then
        exit 23
    fi
done
exec "$(dirname "$0")/sqlite3" "$@"
EOF
chmod +x "$WORK/bin/sqlite3-fail-backup"
cat > "$WORK/bin/flock" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
[ "$1" = "-n" ] || exit 64
shift
lock="$1"
shift
guard="${lock}.test-guard"
mkdir "$guard" 2>/dev/null || exit 75
trap 'rmdir "$guard"' EXIT
"$@"
EOF
chmod +x "$WORK/bin/flock"

export PATH="$WORK/bin:$PATH"
export NARRRFS_TEST_NATIVE_SQLITE3="$NATIVE_SQLITE3"
export NARRRFS_DURABILITY_SCRIPT_PATH="$PUBLISHER"
export NARRRFS_RUNTIME_DB_PATH="$WORK/runtime/narrrf_world.sqlite"
export NARRRFS_PERSISTENT_DB_PATH="$WORK/data/narrrf_world.sqlite"
export NARRRFS_DB_PUBLISH_MIN_FREE_BYTES=1

sqlite3 "$NARRRFS_RUNTIME_DB_PATH" "CREATE TABLE proof(value TEXT); INSERT INTO proof VALUES ('first');"
sqlite3 "$NARRRFS_PERSISTENT_DB_PATH" "CREATE TABLE preserved(value TEXT); INSERT INTO preserved VALUES ('old');"

start_ns="$(date +%s%N)"
"$PUBLISHER" publish
elapsed_ms=$(( ($(date +%s%N) - start_ns) / 1000000 ))
[ "$(sqlite3 -readonly "$NARRRFS_PERSISTENT_DB_PATH" 'SELECT value FROM proof;')" = "first" ]
[ "$(sqlite3 -readonly "$NARRRFS_PERSISTENT_DB_PATH" 'PRAGMA integrity_check;')" = "ok" ]

# A corrupt runtime source must fail and retain the prior canonical bytes.
before_hash="$(sha256sum "$NARRRFS_PERSISTENT_DB_PATH" | awk '{print $1}')"
printf 'not sqlite' > "$NARRRFS_RUNTIME_DB_PATH"
if "$PUBLISHER" publish; then
    echo 'FAIL: corrupt source unexpectedly published' >&2
    exit 1
fi
after_hash="$(sha256sum "$NARRRFS_PERSISTENT_DB_PATH" | awk '{print $1}')"
[ "$before_hash" = "$after_hash" ]

# A forced SQLite backup failure must also retain the canonical bytes.
rm -f "$NARRRFS_RUNTIME_DB_PATH"
sqlite3 "$NARRRFS_RUNTIME_DB_PATH" "CREATE TABLE proof(value TEXT); INSERT INTO proof VALUES ('second');"
before_hash="$(sha256sum "$NARRRFS_PERSISTENT_DB_PATH" | awk '{print $1}')"
if NARRRFS_SQLITE_BIN=sqlite3-fail-backup "$PUBLISHER" publish; then
    echo 'FAIL: forced backup failure unexpectedly published' >&2
    exit 1
fi
after_hash="$(sha256sum "$NARRRFS_PERSISTENT_DB_PATH" | awk '{print $1}')"
[ "$before_hash" = "$after_hash" ]

# Restore succeeds only from a verified canonical source.
rm -f "$NARRRFS_RUNTIME_DB_PATH"
"$PUBLISHER" restore
[ "$(sqlite3 -readonly "$NARRRFS_RUNTIME_DB_PATH" 'SELECT value FROM proof;')" = "first" ]

# Concurrent publication observes the single-publisher lock.
NARRRFS_PUBLISHER_TEST_HOLD_SECONDS=1 "$PUBLISHER" publish &
holder=$!
sleep 0.1
if "$PUBLISHER" publish; then
    echo 'FAIL: concurrent publisher acquired lock' >&2
    exit 1
fi
wait "$holder"

# An impossible space requirement must preserve canonical data.
before_hash="$(sha256sum "$NARRRFS_PERSISTENT_DB_PATH" | awk '{print $1}')"
if NARRRFS_DB_PUBLISH_MIN_FREE_BYTES=999999999999 "$PUBLISHER" publish; then
    echo 'FAIL: free-space guard unexpectedly passed' >&2
    exit 1
fi
after_hash="$(sha256sum "$NARRRFS_PERSISTENT_DB_PATH" | awk '{print $1}')"
[ "$before_hash" = "$after_hash" ]

echo "PASS test-sqlite-durability-publish-v1 elapsed_ms=$elapsed_ms"
