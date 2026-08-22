#!/bin/bash

# === PERSISTENT DATABASE SETUP ===
if [ -f /data/narrrf_world.sqlite ]; then
  echo "✅ Found DB in /data — copying to app directory..."
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
else
  echo "🆕 No DB in /data — seeding /data with app DB (first launch)..."
  cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
  # After seeding, copy back just in case (for safety/consistency)
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
fi

# === PERMISSIONS ===
chown www-data:www-data /var/www/html/db/narrrf_world.sqlite
chmod 664 /var/www/html/db/narrrf_world.sqlite

# === SETUP DATABASE BACKUP SCRIPTS ===
if [ -d /var/www/html/scripts ]; then
    echo "🔧 Setting up database backup scripts..."
    chmod +x /var/www/html/scripts/db-backup.sh
    chmod +x /var/www/html/scripts/db-cleanup.sh
    echo "✅ Database backup scripts configured"
fi

# === MIGRATIONS ===
echo "📦 Applying database migrations..."
sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_score_tables.sql
sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_store_tables.sql

# === DAILY GENESIS LEAGUE SNAPSHOT AUTOMATION ===
# mousefight_league_daily_production_v1
#
# DEVS FOR DECADES:
# This process reads the live app DB through the existing read-only V1 League
# endpoint and publishes immutable V2 snapshot files only. It never writes
# Genesis/Lab state, ownership, Genetic Items, economy, Fight Recovery or DB
# schema rows.
LEAGUE_DAILY_LOOP="/var/www/html/scripts/mousefight-league-daily-loop.sh"
LEAGUE_PYTHON_BIN="$(command -v python3 || true)"
LEAGUE_PHP_BIN="$(command -v php || true)"
LEAGUE_SNAPSHOT_DIRECTORY="/data/mousefight-leagues"

if [ -z "$LEAGUE_PYTHON_BIN" ]; then
    echo "⚠️ Daily Genesis League automation disabled: python3 not found."
elif [ -z "$LEAGUE_PHP_BIN" ]; then
    echo "⚠️ Daily Genesis League automation disabled: PHP CLI not found."
elif [ ! -f "$LEAGUE_DAILY_LOOP" ]; then
    echo "⚠️ Daily Genesis League automation disabled: loop script missing."
elif \
    MOUSEFIGHT_LEAGUE_APP_ROOT="/var/www/html" \
    MOUSEFIGHT_LEAGUE_PERSIST_DIR="$LEAGUE_SNAPSHOT_DIRECTORY" \
    bash "$LEAGUE_DAILY_LOOP" --bootstrap
then
    export MOUSEFIGHT_LEAGUE_SNAPSHOT_DIRECTORY="$LEAGUE_SNAPSHOT_DIRECTORY"
    export MOUSEFIGHT_LEAGUE_PYTHON_BIN="$LEAGUE_PYTHON_BIN"
    export MOUSEFIGHT_LEAGUE_PHP_EXE="$LEAGUE_PHP_BIN"

    echo "✅ Daily Genesis League snapshots: persistent chain ready."
    echo "🕑 Daily Genesis League schedule: 02:00 UTC."

    bash "$LEAGUE_DAILY_LOOP" &
else
    echo "⚠️ Daily Genesis League automation disabled: snapshot bootstrap failed."
    echo "⚠️ Website continues with packaged League snapshot fallback."
fi

# === DISCORD BOT MANUAL LAUNCH NOTICE ===
echo ""
echo "🤖 To start the Discord bot locally, run these commands in your terminal:"
echo "cd /var/www/html/discord"
echo "npm install"
echo "node index.js"
echo ""

# === START APACHE ===
exec apache2-foreground
