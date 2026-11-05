#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
STARTUP="$ROOT/scripts/render-startup.sh"
restore_line="$(grep -n '"$DURABILITY_PUBLISHER" restore' "$STARTUP" | tail -1 | cut -d: -f1)"
migrate_line="$(grep -n '"$MIGRATION_RUNNER" /var/www/html/db/narrrf_world.sqlite' "$STARTUP" | tail -1 | cut -d: -f1)"
publish_line="$(grep -n '"$DURABILITY_PUBLISHER" publish' "$STARTUP" | tail -1 | cut -d: -f1)"
apache_line="$(grep -n 'run_apache_supervisor$' "$STARTUP" | tail -1 | cut -d: -f1)"
[ "$restore_line" -lt "$migrate_line" ]
[ "$migrate_line" -lt "$publish_line" ]
[ "$publish_line" -lt "$apache_line" ]
echo 'PASS test-render-startup-migration-v1 restore-migrate-publish-before-apache ordering'
