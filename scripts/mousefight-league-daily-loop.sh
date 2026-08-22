#!/bin/bash

# MouseFight Genesis Leagues V2 — production daily publisher loop.
#
# Plain language for DEVS FOR DECADES:
# - bootstrap the immutable snapshot chain into persistent /data storage;
# - run at most one successful League publication per UTC calendar day;
# - scheduled publication becomes eligible at 02:00 UTC;
# - retry later if publication fails;
# - never write SQLite, Genesis/Lab state, economy or Fight Recovery.
#
# Patch marker:
# mousefight_league_daily_production_v1

set -u

APP_ROOT="${MOUSEFIGHT_LEAGUE_APP_ROOT:-/var/www/html}"
PACKAGED_SNAPSHOT_DIRECTORY="${APP_ROOT}/league-audit"
PERSIST_DIRECTORY="${MOUSEFIGHT_LEAGUE_PERSIST_DIR:-${MOUSEFIGHT_LEAGUE_SNAPSHOT_DIRECTORY:-/data/mousefight-leagues}}"

PUBLISHER="${APP_ROOT}/league-audit/mousefight-league-v2-shadow-daily.py"

STATE_FILE="${PERSIST_DIRECTORY}/.last-successful-utc-date"

LOCK_DIRECTORY="/tmp/narrrfs-mousefight-league-daily.lock"

MIN_FREE_KB="${MOUSEFIGHT_LEAGUE_MIN_FREE_KB:-102400}"

SNAPSHOT_PREFIX="mousefight-leagues-v2-shadow-snapshot-"
SNAPSHOT_SUFFIX=".json"


log_message() {
    printf '%s %s\n' \
        "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" \
        "$*"
}


sha256_file() {
    sha256sum "$1" \
        | awk '{print $1}'
}


atomic_copy() {
    local source_file="$1"
    local target_file="$2"
    local temporary_file="${target_file}.tmp"

    rm -f "$temporary_file"

    cp \
        "$source_file" \
        "$temporary_file" \
        || return 1

    mv \
        "$temporary_file" \
        "$target_file"
}


bootstrap_snapshots() {
    mkdir -p \
        "$PERSIST_DIRECTORY" \
        || {
            log_message \
                "ERROR: unable to create persistent League snapshot directory."
            return 1
        }

    local packaged_snapshots=()

    shopt -s nullglob

    packaged_snapshots=(
        "$PACKAGED_SNAPSHOT_DIRECTORY"/"$SNAPSHOT_PREFIX"*"$SNAPSHOT_SUFFIX"
    )

    shopt -u nullglob

    if [ "${#packaged_snapshots[@]}" -eq 0 ]; then
        log_message \
            "ERROR: no packaged Genesis League snapshots were found."
        return 1
    fi

    local source_file
    local target_file
    local source_hash
    local target_hash

    for source_file in "${packaged_snapshots[@]}"; do
        target_file="${PERSIST_DIRECTORY}/$(basename "$source_file")"

        if [ -f "$target_file" ]; then
            source_hash="$(
                sha256_file "$source_file"
            )"

            target_hash="$(
                sha256_file "$target_file"
            )"

            if [ "$source_hash" != "$target_hash" ]; then
                log_message \
                    "ERROR: packaged/persistent snapshot hash mismatch: $(basename "$source_file")"
                return 1
            fi

            continue
        fi

        atomic_copy \
            "$source_file" \
            "$target_file" \
            || {
                log_message \
                    "ERROR: unable to bootstrap $(basename "$source_file")."
                return 1
            }
    done

    local required_snapshot

    for required_snapshot in \
        "${SNAPSHOT_PREFIX}001${SNAPSHOT_SUFFIX}" \
        "${SNAPSHOT_PREFIX}004${SNAPSHOT_SUFFIX}"
    do
        if [ ! -f "$PERSIST_DIRECTORY/$required_snapshot" ]; then
            log_message \
                "ERROR: required bootstrap snapshot missing: $required_snapshot"
            return 1
        fi
    done

    log_message \
        "Genesis League persistent snapshot bootstrap: PASS"

    return 0
}


resolve_python() {
    if [ -n "${MOUSEFIGHT_LEAGUE_PYTHON_BIN:-}" ]; then
        printf '%s\n' \
            "$MOUSEFIGHT_LEAGUE_PYTHON_BIN"
        return 0
    fi

    command -v python3 \
        || true
}


resolve_php() {
    if [ -n "${MOUSEFIGHT_LEAGUE_PHP_EXE:-}" ]; then
        printf '%s\n' \
            "$MOUSEFIGHT_LEAGUE_PHP_EXE"
        return 0
    fi

    command -v php \
        || true
}


latest_snapshot() {
    local snapshots=()

    shopt -s nullglob

    snapshots=(
        "$PERSIST_DIRECTORY"/"$SNAPSHOT_PREFIX"*"$SNAPSHOT_SUFFIX"
    )

    shopt -u nullglob

    if [ "${#snapshots[@]}" -eq 0 ]; then
        return 1
    fi

    printf '%s\n' \
        "${snapshots[@]}" \
        | sort \
        | tail -1
}


snapshot_sequence() {
    local snapshot_name

    snapshot_name="$(
        basename "$1"
    )"

    snapshot_name="${snapshot_name#"$SNAPSHOT_PREFIX"}"

    snapshot_name="${snapshot_name%"$SNAPSHOT_SUFFIX"}"

    printf '%s\n' \
        "$snapshot_name"
}


latest_snapshot_generated_date() {
    local python_bin
    local latest

    python_bin="$(
        resolve_python
    )"

    latest="$(
        latest_snapshot
    )" || return 1

    if [ -z "$python_bin" ]; then
        return 1
    fi

    "$python_bin" \
        -c \
        'import json,sys; data=json.load(open(sys.argv[1],encoding="utf-8")); value=str(data.get("generated_at","")); print(value[:10] if len(value) >= 10 else "")' \
        "$latest"
}


already_published_today() {
    local today
    local state_date=""
    local snapshot_date=""

    today="$(
        date -u +%F
    )"

    if [ -f "$STATE_FILE" ]; then
        state_date="$(
            tr -d '\r\n' < "$STATE_FILE"
        )"
    fi

    if [ "$state_date" = "$today" ]; then
        return 0
    fi

    snapshot_date="$(
        latest_snapshot_generated_date \
            2>/dev/null \
            || true
    )"

    if [ "$snapshot_date" = "$today" ]; then
        return 0
    fi

    return 1
}


check_persistent_free_space() {
    local available_kb

    available_kb="$(
        df -Pk "$PERSIST_DIRECTORY" \
            | awk 'NR == 2 {print $4}'
    )"

    if ! [[ "$available_kb" =~ ^[0-9]+$ ]]; then
        log_message \
            "ERROR: unable to determine /data free space."
        return 1
    fi

    if [ "$available_kb" -lt "$MIN_FREE_KB" ]; then
        log_message \
            "ERROR: persistent storage below safety floor (${available_kb} KB available; ${MIN_FREE_KB} KB required)."
        return 1
    fi

    return 0
}


verify_new_snapshot() {
    local snapshot_file="$1"
    local expected_sequence="$2"
    local python_bin="$3"

    "$python_bin" \
        -c \
        'import json,sys; p=json.load(open(sys.argv[1],encoding="utf-8")); expected=int(sys.argv[2]); assert p.get("success") is True; c=p.get("contract",{}); assert c.get("version")=="mousefight_genesis_leagues_v2_shadow_daily_v1"; assert c.get("read_only") is True; assert int(c.get("league_count",0))==9; assert int(p.get("snapshot",{}).get("sequence",0))==expected; mice=p.get("mice",[]); assert isinstance(mice,list) and len(mice)>0; print("verified_sequence=",expected); print("verified_named_mice=",len(mice))' \
        "$snapshot_file" \
        "$expected_sequence"
}


record_success_date() {
    local today
    local temporary_state

    today="$(
        date -u +%F
    )"

    temporary_state="${STATE_FILE}.tmp"

    printf '%s\n' \
        "$today" \
        > "$temporary_state" \
        || return 1

    mv \
        "$temporary_state" \
        "$STATE_FILE"
}


run_publish_once() {
    if ! bootstrap_snapshots; then
        return 1
    fi

    if already_published_today; then
        log_message \
            "Daily Genesis League snapshot already published for today; skipping."
        return 0
    fi

    if ! check_persistent_free_space; then
        return 1
    fi

    local python_bin
    local php_bin

    python_bin="$(
        resolve_python
    )"

    php_bin="$(
        resolve_php
    )"

    if [ -z "$python_bin" ] || [ ! -x "$python_bin" ]; then
        log_message \
            "ERROR: python3 executable unavailable."
        return 1
    fi

    if [ -z "$php_bin" ] || [ ! -x "$php_bin" ]; then
        log_message \
            "ERROR: PHP CLI executable unavailable."
        return 1
    fi

    if [ ! -f "$PUBLISHER" ]; then
        log_message \
            "ERROR: daily League publisher missing: $PUBLISHER"
        return 1
    fi

    local before_snapshot
    local before_sequence

    before_snapshot="$(
        latest_snapshot
    )" || {
        log_message \
            "ERROR: no current snapshot before publication."
        return 1
    }

    before_sequence="$(
        snapshot_sequence "$before_snapshot"
    )"

    local publish_rc=0

    (
        if ! mkdir "$LOCK_DIRECTORY" 2>/dev/null; then
            log_message \
                "Daily Genesis League publication already locked; skipping duplicate runner."
            exit 75
        fi

        trap \
            'rmdir "$LOCK_DIRECTORY" 2>/dev/null || true' \
            EXIT INT TERM

        export \
            MOUSEFIGHT_LEAGUE_SNAPSHOT_DIRECTORY="$PERSIST_DIRECTORY"

        export \
            MOUSEFIGHT_LEAGUE_PHP_EXE="$php_bin"

        "$python_bin" \
            "$PUBLISHER"
    ) || publish_rc=$?

    if [ "$publish_rc" -eq 75 ]; then
        return 0
    fi

    if [ "$publish_rc" -ne 0 ]; then
        log_message \
            "ERROR: daily Genesis League publisher exited ${publish_rc}."
        return "$publish_rc"
    fi

    local after_snapshot
    local after_sequence
    local expected_sequence

    after_snapshot="$(
        latest_snapshot
    )" || {
        log_message \
            "ERROR: no current snapshot after publication."
        return 1
    }

    after_sequence="$(
        snapshot_sequence "$after_snapshot"
    )"

    expected_sequence="$(
        printf '%03d' \
            "$((10#$before_sequence + 1))"
    )"

    if [ "$after_sequence" != "$expected_sequence" ]; then
        log_message \
            "ERROR: expected snapshot ${expected_sequence}, found ${after_sequence}."
        return 1
    fi

    if ! verify_new_snapshot \
        "$after_snapshot" \
        "$((10#$after_sequence))" \
        "$python_bin"
    then
        log_message \
            "ERROR: newly published snapshot failed wrapper validation."
        return 1
    fi

    if ! record_success_date; then
        log_message \
            "ERROR: snapshot published but daily success checkpoint could not be written."
        return 1
    fi

    log_message \
        "Daily Genesis League publication PASS: snapshot #${after_sequence}"

    return 0
}


scheduler_loop() {
    if ! bootstrap_snapshots; then
        log_message \
            "ERROR: scheduler refused because bootstrap failed."
        return 1
    fi

    log_message \
        "Daily Genesis League scheduler active; publication time 02:00 UTC."

    while true; do
        local hhmm

        hhmm="$(
            date -u +%H%M
        )"

        if ((10#$hhmm >= 200)) && ! already_published_today; then
            if run_publish_once; then
                sleep 60
            else
                log_message \
                    "Daily Genesis League publication failed; retrying in 15 minutes."
                sleep 900
            fi
        else
            sleep 60
        fi
    done
}


case "${1:-}" in
    --bootstrap)
        bootstrap_snapshots
        ;;

    --run-once)
        run_publish_once
        ;;

    "")
        scheduler_loop
        ;;

    *)
        echo \
            "Usage: $0 [--bootstrap|--run-once]" \
            >&2
        exit 2
        ;;
esac
