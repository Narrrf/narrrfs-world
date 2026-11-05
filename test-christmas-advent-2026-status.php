<?php
/** Disposable-only Christmas Advent 2026 GET-status regression suite. */
declare(strict_types=1);
ini_set('session.save_path', sys_get_temp_dir());

require_once __DIR__ . '/api/config/admin-session.php';
require_once __DIR__ . '/api/lib/christmas-advent-2026.php';

function christmas26_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

function christmas26_test_db(bool $withClaimsTable = true): SQLite3
{
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    $db->exec('CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY, user_id TEXT, score INTEGER, source TEXT)');
    $db->exec('CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY, user_id TEXT, amount INTEGER, action TEXT)');
    if ($withClaimsTable) {
        $db->exec("CREATE TABLE tbl_christmas_advent_2026_claims (
            claim_id INTEGER PRIMARY KEY,
            event_key TEXT NOT NULL,
            user_id TEXT NOT NULL,
            actor_user_id TEXT NOT NULL,
            calendar_date TEXT NOT NULL,
            day_number INTEGER NOT NULL,
            reward_dspoinc INTEGER NOT NULL,
            score_id INTEGER NOT NULL UNIQUE,
            adjustment_id INTEGER NOT NULL UNIQUE,
            claimed_at TEXT NOT NULL,
            UNIQUE (event_key, user_id, calendar_date),
            UNIQUE (event_key, user_id, day_number)
        )");
    }
    return $db;
}

function christmas26_test_now(string $value): DateTimeImmutable
{
    return new DateTimeImmutable($value, new DateTimeZone('UTC'));
}

function christmas26_test_total_changes(SQLite3 $db): int
{
    return (int)$db->querySingle('SELECT total_changes()');
}

christmas26_test_assert(!christmas26_status_feature_is_enabled(null), 'status gate defaults off');
christmas26_test_assert(!christmas26_status_feature_is_enabled('0'), 'non-exact status gate stays off');
christmas26_test_assert(christmas26_status_feature_is_enabled('1'), 'exact status gate enables read capability only');

$trusted = ['discord_id' => '111111111111111111', NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE];
christmas26_test_assert(narrrfs_session_has_trusted_discord_oauth_provenance($trusted), 'trusted Discord OAuth subject is accepted');
christmas26_test_assert(!narrrfs_session_has_trusted_discord_oauth_provenance([]), 'missing session is rejected');
christmas26_test_assert(!narrrfs_session_has_trusted_discord_oauth_provenance(['discord_id' => '111111111111111111']), 'forged numeric identity without provenance is rejected');

christmas26_test_assert(christmas26_event_state(christmas26_test_now('2026-11-30T22:59:59Z')) === 'pre_event', 'November 30 before Vienna midnight is pre-event');
christmas26_test_assert(christmas26_event_state(christmas26_test_now('2026-11-30T23:00:00Z')) === 'active', 'December 1 Vienna midnight starts event');
for ($day = 1; $day <= 24; $day++) {
    $now = christmas26_test_now(sprintf('2026-12-%02dT00:00:00+01:00', $day));
    christmas26_test_assert(christmas26_event_state($now) === 'active', 'December ' . $day . ' Vienna midnight remains active');
}
christmas26_test_assert(christmas26_event_state(christmas26_test_now('2026-12-24T22:59:59Z')) === 'active', 'December 24 final Vienna second is active');
christmas26_test_assert(christmas26_event_state(christmas26_test_now('2026-12-24T23:00:00Z')) === 'expired', 'December 25 Vienna midnight expires event');

$missingDb = christmas26_test_db(false);
$beforeReads = christmas26_test_total_changes($missingDb);
$missing = christmas26_status($missingDb, '111111111111111111', christmas26_test_now('2026-12-01T12:00:00Z'));
christmas26_test_assert(!$missing['ready'] && $missing['error'] === 'claims_schema_unavailable', 'missing marker table fails closed');
christmas26_test_assert(count($missing['doors']) === 24 && $missing['doors'][0]['state'] === 'status_unavailable', 'missing marker never manufactures claimed or available doors');
christmas26_test_assert(christmas26_test_total_changes($missingDb) === $beforeReads, 'missing-schema status performs zero SQL writes');

$partialDb = new SQLite3(':memory:');
$partialDb->enableExceptions(true);
$partialDb->exec('CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY, user_id TEXT, score INTEGER, source TEXT)');
$partialDb->exec('CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY, user_id TEXT, amount INTEGER, action TEXT)');
$partialDb->exec('CREATE TABLE tbl_christmas_advent_2026_claims (claim_id INTEGER PRIMARY KEY, event_key TEXT, user_id TEXT, actor_user_id TEXT, calendar_date TEXT, day_number INTEGER, reward_dspoinc INTEGER, score_id INTEGER, adjustment_id INTEGER, claimed_at TEXT)');
$partial = christmas26_status($partialDb, '111111111111111111', christmas26_test_now('2026-12-01T12:00:00Z'));
christmas26_test_assert(!$partial['ready'] && $partial['error'] === 'claims_schema_inconsistent', 'marker schema without daily uniqueness fails closed');

$readOnlyDb = christmas26_test_db();
$readOnlyDb->exec('PRAGMA query_only = ON');
$writeRejected = false;
try {
    $readOnlyDb->exec("INSERT INTO tbl_user_scores (id, user_id, score, source) VALUES (99, '111111111111111111', 1, 'test')");
} catch (Throwable) {
    $writeRejected = true;
}
christmas26_test_assert($writeRejected, 'SQLite query_only rejects a disposable write');

$db = christmas26_test_db();
$beforeReads = christmas26_test_total_changes($db);
$status = christmas26_status($db, '111111111111111111', christmas26_test_now('2026-12-03T12:00:00Z'));
christmas26_test_assert($status['ready'] && $status['event_state'] === 'active', 'complete empty marker schema produces active status');
christmas26_test_assert(count($status['doors']) === 24, 'stable response includes all 24 doors');
christmas26_test_assert($status['doors'][0]['state'] === 'missed' && $status['doors'][1]['state'] === 'missed' && $status['doors'][2]['state'] === 'available' && $status['doors'][3]['state'] === 'locked', 'active calendar distinguishes missed current available and future locked');
christmas26_test_assert($status['doors'][2]['claim_authorized'] === false && $status['doors'][2]['payout_authorized'] === false, 'available calendar date is never payout authorization');
christmas26_test_assert(christmas26_test_total_changes($db) === $beforeReads, 'ready-schema status performs zero SQL writes');

$db->exec("INSERT INTO tbl_user_scores (id, user_id, score, source) VALUES (11, '111111111111111111', 5000, 'christmas_advent_2026')");
$db->exec("INSERT INTO tbl_score_adjustments (id, user_id, amount, action) VALUES (12, '111111111111111111', 5000, 'add')");
$db->exec("INSERT INTO tbl_christmas_advent_2026_claims (claim_id, event_key, user_id, actor_user_id, calendar_date, day_number, reward_dspoinc, score_id, adjustment_id, claimed_at) VALUES (1, 'christmas_advent_2026', '111111111111111111', '111111111111111111', '2026-12-01', 1, 5000, 11, 12, '2026-12-01 00:00:05')");
$beforeReads = christmas26_test_total_changes($db);
$claimed = christmas26_status($db, '111111111111111111', christmas26_test_now('2026-12-03T12:00:00Z'));
christmas26_test_assert($claimed['doors'][0]['state'] === 'claimed' && $claimed['doors'][0]['claimed_at'] === '2026-12-01 00:00:05', 'verified marker ledger and audit render claimed door');
christmas26_test_assert(christmas26_test_total_changes($db) === $beforeReads, 'claimed status performs zero SQL writes');

$invalidDb = christmas26_test_db();
$invalidDb->exec("INSERT INTO tbl_user_scores (id, user_id, score, source) VALUES (21, '111111111111111111', 5000, 'christmas_advent_2026')");
$invalidDb->exec("INSERT INTO tbl_score_adjustments (id, user_id, amount, action) VALUES (22, '111111111111111111', 5000, 'add')");
$invalidDb->exec("INSERT INTO tbl_christmas_advent_2026_claims (claim_id, event_key, user_id, actor_user_id, calendar_date, day_number, reward_dspoinc, score_id, adjustment_id, claimed_at) VALUES (2, 'christmas_advent_2026', '111111111111111111', '111111111111111111', '2026-12-02', 1, 5000, 21, 22, '2026-12-01 00:00:05')");
$invalid = christmas26_status($invalidDb, '111111111111111111', christmas26_test_now('2026-12-03T12:00:00Z'));
christmas26_test_assert(!$invalid['ready'] && $invalid['error'] === 'christmas26_claim_status_integrity_failed', 'inconsistent marker data fails closed');

$pre = christmas26_status($db, '111111111111111111', christmas26_test_now('2026-11-30T22:00:00Z'));
christmas26_test_assert($pre['event_state'] === 'pre_event' && $pre['doors'][1]['state'] === 'locked', 'pre-event unclaimed doors are locked');
$expired = christmas26_status($db, '111111111111111111', christmas26_test_now('2026-12-25T00:00:00+01:00'));
christmas26_test_assert($expired['event_state'] === 'expired' && $expired['doors'][1]['state'] === 'missed', 'expired unclaimed doors are missed');

$endpoint = file_get_contents(__DIR__ . '/api/user/christmas-advent-2026.php');
christmas26_test_assert(is_string($endpoint) && str_contains($endpoint, "!== 'GET'") && str_contains($endpoint, "Allow: GET"), 'endpoint exposes GET only and has no POST payout capability');
christmas26_test_assert(str_contains($endpoint, 'SQLITE3_OPEN_READONLY') && str_contains($endpoint, "PRAGMA query_only = ON"), 'endpoint opens SQLite read-only and enforces query_only');
$protectedNames = ['halloween-pumpkin-2026.php', 'open-reward-box.php', 'tbl_user_scores', 'tbl_score_adjustments'];
foreach ($protectedNames as $protectedName) {
    christmas26_test_assert(!str_contains((string)$endpoint, $protectedName), 'endpoint leaves protected module out: ' . $protectedName);
}

echo "PASS: Christmas Advent 2026 GET-only status suite complete" . PHP_EOL;
