<?php
/**
 * Disposable-only Halloween Pumpkin 2026 economy regression suite.
 * NEVER opens an application/local/production DB. Only sqlite::memory:.
 */
declare(strict_types=1);
require_once __DIR__ . '/api/lib/halloween-pumpkin-2026.php';

/** Fail immediately with an exact meaningful assertion. */
function halloween26_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

/** Create a disposable approximation of the user-provided score schemas. */
function halloween26_fixture(): PDO
{
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec("CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT NOT NULL)");
    $db->exec("CREATE TABLE tbl_user_scores (
        id INTEGER PRIMARY KEY, user_id TEXT, game TEXT, score INTEGER,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, source TEXT DEFAULT 'legacy',
        game_type TEXT, season TEXT DEFAULT 'season_2', created_at DATETIME
    )");
    $db->exec("CREATE TABLE tbl_score_adjustments (
        id INTEGER PRIMARY KEY, user_id TEXT NOT NULL, admin_id TEXT NOT NULL,
        amount INTEGER NOT NULL, action TEXT NOT NULL,
        reason TEXT NOT NULL DEFAULT 'admin_adjustment',
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, quest_id INTEGER
    )");
    $db->exec("INSERT INTO tbl_users (discord_id, username) VALUES ('111111111111111111', 'Fixture A'), ('222222222222222222', 'Fixture B')");
    return $db;
}

/** Return score and adjustment row counts for narrowly scoped event records. */
function halloween26_counts(PDO $db): array
{
    return [
        (int)$db->query("SELECT COUNT(*) FROM tbl_user_scores WHERE source = 'halloween_pumpkin_2026'")->fetchColumn(),
        (int)$db->query("SELECT COUNT(*) FROM tbl_score_adjustments WHERE admin_id = 'halloween_pumpkin_2026'")->fetchColumn(),
    ];
}

$inside = new DateTimeImmutable('2026-10-30T12:00:00+00:00');
$lastSecond = new DateTimeImmutable('2026-10-31T22:59:59+00:00');
$expired = new DateTimeImmutable('2026-10-31T23:00:00+00:00');
$before = new DateTimeImmutable('2026-10-27T23:59:59+00:00');
halloween26_assert(halloween26_is_open($inside), 'event open in window');
halloween26_assert(halloween26_is_open($lastSecond), 'Vienna Halloween final second included');
halloween26_assert(!halloween26_is_open($expired), 'Vienna November 1 midnight excluded');
halloween26_assert(!halloween26_is_open($before), 'pre-event excluded');

$first = '111111111111111111';
$second = '222222222222222222';
$db = halloween26_fixture();
$r = halloween26_claim($db, $first, $inside, static fn(): int => 25000);
halloween26_assert($r === ['claimed'=>true,'already_claimed'=>false,'amount'=>25000], 'first successful 25k credit');
halloween26_assert(halloween26_counts($db) === [1,1], 'one ledger and one matching audit');
halloween26_assert(halloween26_get_claim_state($db,$first) === ['claimed'=>true,'amount'=>25000], 'status returns original credit');
$r = halloween26_claim($db,$first,$inside,static fn(): int => 100000);
halloween26_assert($r['already_claimed'] && $r['amount'] === 25000, 'duplicate click never rerolls or pays twice');
halloween26_assert(halloween26_counts($db) === [1,1], 'duplicate click leaves economy unchanged');
$r = halloween26_claim($db,$second,$lastSecond,static fn(): int => 10000);
halloween26_assert($r['amount'] === 10000 && !$r['already_claimed'], 'independent Discord user gets one valid credit');
halloween26_assert(halloween26_counts($db) === [2,2], 'two user claims recorded');
$r = halloween26_claim($db,$first,$expired,static fn(): int => 100000);
halloween26_assert($r['already_claimed'] && $r['amount'] === 25000, 'post-close recheck returns existing amount without credit');

$closedDb = halloween26_fixture();
try {
    halloween26_claim($closedDb,$first,$expired,static fn(): int => 10000);
    throw new RuntimeException('FAIL: expired request accepted');
} catch (DomainException $error) {
    halloween26_assert($error->getMessage()==='halloween26_event_closed','expired request rejected');
}
halloween26_assert(halloween26_counts($closedDb) === [0,0], 'expired request zero writes');
try {
    halloween26_claim($closedDb,'333333333333333333',$inside,static fn(): int => 10000);
    throw new RuntimeException('FAIL: missing account accepted');
} catch (DomainException $error) {
    halloween26_assert($error->getMessage()==='halloween26_account_not_found','unknown account rejected');
}
try {
    halloween26_claim($closedDb,$first,$inside,static fn(): int => 500001);
    throw new RuntimeException('FAIL: oversized amount accepted');
} catch (RuntimeException $error) {
    halloween26_assert($error->getMessage()==='halloween26_reward_out_of_range','invalid reward rejected');
}
halloween26_assert(halloween26_counts($closedDb) === [0,0], 'unknown account/invalid reward zero writes');
try {
    halloween26_claim($closedDb,$first,$inside,static fn(): int => 9999);
    throw new RuntimeException('FAIL: below-minimum amount accepted');
} catch (RuntimeException $error) {
    halloween26_assert($error->getMessage()==='halloween26_reward_out_of_range','below-minimum reward rejected');
}
halloween26_assert(halloween26_counts($closedDb) === [0,0], 'out-of-range rewards leave zero writes');

$dbRollback = halloween26_fixture();
$dbRollback->exec("CREATE TRIGGER reject_halloween_audit BEFORE INSERT ON tbl_score_adjustments BEGIN SELECT RAISE(ABORT, 'forced_audit_failure'); END");
try {
    halloween26_claim($dbRollback,$first,$inside,static fn(): int => 50000);
    throw new RuntimeException('FAIL: forced audit failure allowed');
} catch (PDOException $error) {
    halloween26_assert(str_contains($error->getMessage(),'forced_audit_failure'),'audit trigger rejects transaction');
}
halloween26_assert(halloween26_counts($dbRollback) === [0,0], 'audit failure rolls back ledger and marker');
$dbRollback->exec('DROP TRIGGER reject_halloween_audit');
$r = halloween26_claim($dbRollback,$first,$inside,static fn(): int => 50000);
halloween26_assert($r['amount']===50000 && !$r['already_claimed'], 'safe retry after full rollback');
halloween26_assert(halloween26_counts($dbRollback) === [1,1], 'safe retry records exactly one pair');

$dbOrphan = halloween26_fixture();
$dbOrphan->exec("INSERT INTO tbl_user_scores (user_id,game,score,source) VALUES ('$first','halloween_pumpkin',10000,'halloween_pumpkin_2026')");
try {
    halloween26_claim($dbOrphan,$first,$inside,static fn(): int => 25000);
    throw new RuntimeException('FAIL: orphan ledger accepted');
} catch (RuntimeException $error) {
    halloween26_assert($error->getMessage()==='halloween26_claim_record_integrity_failed','orphan ledger fails closed');
}
halloween26_assert(halloween26_counts($dbOrphan) === [1,0], 'orphan integrity failure never adds a second credit');

for ($i=0;$i<200;$i++) {
    $amount=halloween26_roll_amount();
    if ($amount < 10000 || $amount > 500000) throw new RuntimeException('FAIL: random reward out of range');
}
echo "PASS: 200 secure reward-roll range checks" . PHP_EOL;
echo "PASS: Halloween 2026 disposable-only suite complete" . PHP_EOL;
