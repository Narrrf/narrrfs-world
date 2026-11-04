#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
STARTUP="$ROOT/scripts/render-startup.sh"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

grep -q '"$DURABILITY_PUBLISHER" restore' "$STARTUP"
grep -q '"$APACHE_FOREGROUND_COMMAND" &' "$STARTUP"
grep -q "trap 'graceful_shutdown' TERM INT" "$STARTUP"
grep -q '"$DURABILITY_PUBLISHER" publish' "$STARTUP"
if grep -q 'exec apache2-foreground' "$STARTUP"; then
    echo 'FAIL: startup still bypasses shutdown traps' >&2
    exit 1
fi

mkdir -p "$WORK/bin"
cat > "$WORK/bin/apache2-foreground" <<'EOF'
#!/usr/bin/env bash
trap 'exit 0' TERM INT
while :; do sleep 1; done
EOF
cat > "$WORK/publisher" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$1" >> "$NARRRFS_TEST_PUBLISH_LOG"
[ "${NARRRFS_TEST_FINAL_PUBLISH_FAIL:-0}" != 1 ]
EOF
chmod +x "$WORK/bin/apache2-foreground" "$WORK/publisher"

run_signal_case() {
    local expected_status="$1"
    local fail_final="$2"
    : > "$WORK/publish.log"
    PATH="$WORK/bin:$PATH" \
    NARRRFS_STARTUP_TEST_MODE=1 \
    NARRRFS_DURABILITY_PUBLISHER="$WORK/publisher" \
    NARRRFS_TEST_PUBLISH_LOG="$WORK/publish.log" \
    NARRRFS_TEST_FINAL_PUBLISH_FAIL="$fail_final" \
    "$STARTUP" &
    local supervisor=$!
    sleep 0.2
    kill -TERM "$supervisor"
    set +e
    wait "$supervisor"
    local status=$?
    set -e
    [ "$status" -eq "$expected_status" ]
    [ "$(cat "$WORK/publish.log")" = 'publish' ]
}

run_signal_case 0 0
run_signal_case 1 1
echo 'PASS test-render-startup-durability-v1 lifecycle and final-publish contract'
