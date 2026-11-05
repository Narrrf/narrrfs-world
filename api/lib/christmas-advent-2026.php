<?php
/**
 * Christmas Advent 2026 — read-only status foundation.
 *
 * DEVS FOR DECADES: this file has no payout, CSRF issuance, reward schedule,
 * or writable database behavior. A calendar date is presentation eligibility,
 * never authorization to grant DSPOINC or any other protected reward.
 */
declare(strict_types=1);

const CHRISTMAS26_EVENT_KEY = 'christmas_advent_2026';
const CHRISTMAS26_STATUS_GATE = 'NARRRFS_CHRISTMAS_ADVENT_2026_STATUS_ENABLED';
const CHRISTMAS26_CLAIMS_TABLE = 'tbl_christmas_advent_2026_claims';
const CHRISTMAS26_TIMEZONE = 'Europe/Vienna';
const CHRISTMAS26_START = '2026-12-01 00:00:00';
const CHRISTMAS26_END_EXCLUSIVE = '2026-12-25 00:00:00';

// Future marker contract: user_id is the Discord subject, actor_user_id must
// equal it, and claim_id is the marker's receipt identity. The generic receipt
// table is deliberately not a dependency of this read-only calendar contract.

/** Status reads are opt-in; an absent or non-exact value is disabled. */
function christmas26_status_feature_is_enabled(?string $value): bool
{
    return $value === '1';
}

/** Convert any supplied test/runtime instant to the authoritative Vienna timezone. */
function christmas26_vienna_time(DateTimeImmutable $now): DateTimeImmutable
{
    return $now->setTimezone(new DateTimeZone(CHRISTMAS26_TIMEZONE));
}

/** Return pre_event, active, or expired using an exclusive Vienna end boundary. */
function christmas26_event_state(DateTimeImmutable $now): string
{
    $viennaNow = christmas26_vienna_time($now);
    $timezone = new DateTimeZone(CHRISTMAS26_TIMEZONE);
    $start = new DateTimeImmutable(CHRISTMAS26_START, $timezone);
    $end = new DateTimeImmutable(CHRISTMAS26_END_EXCLUSIVE, $timezone);

    if ($viennaNow < $start) {
        return 'pre_event';
    }
    if ($viennaNow >= $end) {
        return 'expired';
    }
    return 'active';
}

/** Build the stable 24-door identifier set without consulting browser time. */
function christmas26_doors(): array
{
    $doors = [];
    for ($day = 1; $day <= 24; $day++) {
        $doors[] = [
            'door_id' => sprintf('christmas_advent_2026_day_%02d', $day),
            'day' => $day,
            'calendar_date' => sprintf('2026-12-%02d', $day),
        ];
    }
    return $doors;
}

/** Quote an SQLite identifier obtained from trusted SQLite metadata. */
function christmas26_quote_identifier(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

/** Check whether an exact unique index exists for a required claim identity. */
function christmas26_has_unique_index(SQLite3 $db, array $expectedColumns): bool
{
    $result = $db->query('PRAGMA index_list(' . CHRISTMAS26_CLAIMS_TABLE . ')');
    if (!$result instanceof SQLite3Result) {
        return false;
    }

    while (($index = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
        if ((int)($index['unique'] ?? 0) !== 1 || !is_string($index['name'] ?? null)) {
            continue;
        }
        $columns = [];
        $indexInfo = $db->query('PRAGMA index_info(' . christmas26_quote_identifier($index['name']) . ')');
        if (!$indexInfo instanceof SQLite3Result) {
            continue;
        }
        while (($column = $indexInfo->fetchArray(SQLITE3_ASSOC)) !== false) {
            $columns[] = (string)($column['name'] ?? '');
        }
        if ($columns === $expectedColumns) {
            return true;
        }
    }

    return false;
}

/**
 * Verify the future marker contract before reading it. A missing or partial
 * table is unavailable, rather than evidence that no door has been claimed.
 */
function christmas26_claims_schema_error(SQLite3 $db): ?string
{
    $statement = $db->prepare(
        "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :name LIMIT 1"
    );
    if (!$statement instanceof SQLite3Stmt) {
        return 'claims_schema_unavailable';
    }
    $statement->bindValue(':name', CHRISTMAS26_CLAIMS_TABLE, SQLITE3_TEXT);
    $result = $statement->execute();
    if (!$result instanceof SQLite3Result || $result->fetchArray(SQLITE3_NUM) === false) {
        return 'claims_schema_unavailable';
    }

    $requiredColumns = [
        'claim_id', 'event_key', 'user_id', 'actor_user_id', 'calendar_date',
        'day_number', 'reward_dspoinc', 'score_id', 'adjustment_id', 'claimed_at',
    ];
    $availableColumns = [];
    $columns = $db->query('PRAGMA table_info(' . CHRISTMAS26_CLAIMS_TABLE . ')');
    if (!$columns instanceof SQLite3Result) {
        return 'claims_schema_unavailable';
    }
    while (($column = $columns->fetchArray(SQLITE3_ASSOC)) !== false) {
        $availableColumns[] = (string)($column['name'] ?? '');
    }
    foreach ($requiredColumns as $requiredColumn) {
        if (!in_array($requiredColumn, $availableColumns, true)) {
            return 'claims_schema_inconsistent';
        }
    }

    if (!christmas26_has_unique_index($db, ['event_key', 'user_id', 'calendar_date'])
        || !christmas26_has_unique_index($db, ['event_key', 'user_id', 'day_number'])) {
        return 'claims_schema_inconsistent';
    }

    return null;
}

/** Return only a claim whose marker, ledger, and adjustment all agree. */
function christmas26_verified_claims(SQLite3 $db, string $userId): array
{
    $statement = $db->prepare(
        'SELECT c.claim_id, c.event_key, c.user_id, c.actor_user_id, c.calendar_date,
                c.day_number, c.reward_dspoinc, c.score_id, c.adjustment_id, c.claimed_at,
                s.user_id AS score_user_id, s.score, s.source,
                a.user_id AS adjustment_user_id, a.amount AS adjustment_amount, a.action
         FROM tbl_christmas_advent_2026_claims AS c
         LEFT JOIN tbl_user_scores AS s ON s.id = c.score_id
         LEFT JOIN tbl_score_adjustments AS a ON a.id = c.adjustment_id
         WHERE c.event_key = :event_key
           AND c.user_id = :user_id
           AND c.calendar_date BETWEEN :start_date AND :end_date
         ORDER BY c.day_number ASC'
    );
    if (!$statement instanceof SQLite3Stmt) {
        throw new RuntimeException('christmas26_claim_status_unavailable');
    }
    $statement->bindValue(':event_key', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
    $statement->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $statement->bindValue(':start_date', '2026-12-01', SQLITE3_TEXT);
    $statement->bindValue(':end_date', '2026-12-24', SQLITE3_TEXT);
    $result = $statement->execute();
    if (!$result instanceof SQLite3Result) {
        throw new RuntimeException('christmas26_claim_status_unavailable');
    }

    $claims = [];
    while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
        $day = (int)($row['day_number'] ?? 0);
        $date = (string)($row['calendar_date'] ?? '');
        $amount = (int)($row['reward_dspoinc'] ?? 0);
        $expectedDate = sprintf('2026-12-%02d', $day);
        if ($day < 1 || $day > 24 || $date !== $expectedDate || isset($claims[$day])
            || $amount <= 0 || (int)($row['score_id'] ?? 0) <= 0 || (int)($row['adjustment_id'] ?? 0) <= 0
            || trim((string)($row['claimed_at'] ?? '')) === ''
            || (string)($row['event_key'] ?? '') !== CHRISTMAS26_EVENT_KEY
            || (string)($row['user_id'] ?? '') !== $userId
            || (string)($row['actor_user_id'] ?? '') !== $userId
            || (string)($row['score_user_id'] ?? '') !== $userId
            || (string)($row['adjustment_user_id'] ?? '') !== $userId
            || (int)($row['score'] ?? 0) !== $amount
            || (int)($row['adjustment_amount'] ?? 0) !== $amount
            || (string)($row['source'] ?? '') !== CHRISTMAS26_EVENT_KEY
            || (string)($row['action'] ?? '') !== 'add') {
            throw new RuntimeException('christmas26_claim_status_integrity_failed');
        }
        $claims[$day] = [
            'claim_id' => (int)$row['claim_id'],
            'claimed_at' => (string)$row['claimed_at'],
        ];
    }

    return $claims;
}

/** Build an explicit unavailable response without inventing calendar eligibility. */
function christmas26_unavailable_doors(): array
{
    return array_map(static function (array $door): array {
        return $door + [
            'state' => 'status_unavailable',
            'claim_authorized' => false,
            'payout_authorized' => false,
            'claimed_at' => null,
        ];
    }, christmas26_doors());
}

/**
 * Build the authenticated calendar status. `available` means only that today
 * belongs to the calendar; both authorization booleans remain false in V2.1.
 */
function christmas26_status(SQLite3 $db, string $userId, DateTimeImmutable $now): array
{
    $schemaError = christmas26_claims_schema_error($db);
    $viennaNow = christmas26_vienna_time($now);
    $eventState = christmas26_event_state($viennaNow);
    if ($schemaError !== null) {
        return [
            'ready' => false,
            'error' => $schemaError,
            'event_state' => $eventState,
            'server_time_vienna' => $viennaNow->format(DateTimeInterface::ATOM),
            'doors' => christmas26_unavailable_doors(),
        ];
    }

    try {
        $claims = christmas26_verified_claims($db, $userId);
    } catch (RuntimeException $error) {
        return [
            'ready' => false,
            'error' => $error->getMessage(),
            'event_state' => $eventState,
            'server_time_vienna' => $viennaNow->format(DateTimeInterface::ATOM),
            'doors' => christmas26_unavailable_doors(),
        ];
    }

    $today = $viennaNow->format('Y-m-d');
    $doors = [];
    foreach (christmas26_doors() as $door) {
        $day = $door['day'];
        if (isset($claims[$day])) {
            $state = 'claimed';
            $claimedAt = $claims[$day]['claimed_at'];
        } elseif ($eventState === 'pre_event' || $door['calendar_date'] > $today) {
            $state = 'locked';
            $claimedAt = null;
        } elseif ($eventState === 'expired' || $door['calendar_date'] < $today) {
            $state = 'missed';
            $claimedAt = null;
        } else {
            $state = 'available';
            $claimedAt = null;
        }

        $doors[] = $door + [
            'state' => $state,
            'claim_authorized' => false,
            'payout_authorized' => false,
            'claimed_at' => $claimedAt,
        ];
    }

    return [
        'ready' => true,
        'error' => null,
        'event_state' => $eventState,
        'server_time_vienna' => $viennaNow->format(DateTimeInterface::ATOM),
        'doors' => $doors,
    ];
}
