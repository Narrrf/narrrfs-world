#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
STARTUP="$ROOT/scripts/render-startup.sh"
WORK="$(mktemp -d)"; trap 'rm -rf "$WORK"' EXIT
mkdir -p "$WORK/bin"
printf 'canonical-before' > "$WORK/canonical.sqlite"
canonical_hash="$(sha256sum "$WORK/canonical.sqlite" | awk '{print $1}')"
cat > "$WORK/publisher" <<'EOF'
#!/usr/bin/env bash
printf '%s\n' "$1" >> "$NARRRFS_TEST_LOG"
exit 0
EOF
cat > "$WORK/migrator" <<'EOF'
#!/usr/bin/env bash
printf 'migrate\n' >> "$NARRRFS_TEST_LOG"
[ "${NARRRFS_TEST_MIGRATION_FAIL:-0}" != 1 ]
EOF
cat > "$WORK/bin/apache2-foreground" <<'EOF'
#!/usr/bin/env bash
printf 'apache\n' >> "$NARRRFS_TEST_LOG"
exit 0
EOF
chmod +x "$WORK/publisher" "$WORK/migrator" "$WORK/bin/apache2-foreground"

run_case() {
  local fail="$1" expected="$2"
  : > "$WORK/log"
  set +e
  PATH="$WORK/bin:$PATH" NARRRFS_STARTUP_TEST_MODE=1 NARRRFS_STARTUP_TEST_LIFECYCLE=1 \
  NARRRFS_DURABILITY_PUBLISHER="$WORK/publisher" NARRRFS_MIGRATION_RUNNER="$WORK/migrator" \
  NARRRFS_MIGRATION_MANIFEST="$WORK/manifest" NARRRFS_RUNTIME_DB_PATH="$WORK/runtime.sqlite" \
  NARRRFS_TEST_MIGRATION_FAIL="$fail" NARRRFS_TEST_LOG="$WORK/log" "$STARTUP"
  status=$?
  set -e
  [ "$status" -eq "$expected" ]
}
run_case 0 0
[ "$(tr '\n' ' ' < "$WORK/log")" = 'restore migrate publish apache ' ]
run_case 1 1
[ "$(tr '\n' ' ' < "$WORK/log")" = 'restore migrate ' ]
[ "$canonical_hash" = "$(sha256sum "$WORK/canonical.sqlite" | awk '{print $1}')" ]
echo 'PASS test-render-startup-migration-lifecycle-v1 success and failure propagation'
