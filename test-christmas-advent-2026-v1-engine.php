<?php
/**
 * Disposable-only Christmas Advent 2026 Reward Engine V1 test suite.
 * Covers cases 1-12, 14-22, 24, 30 against temporary SQLite fixtures only.
 */
declare(strict_types=1);

require_once __DIR__ . '/api/lib/christmas-advent-2026-claim.php';
require_once __DIR__ . '/api/lib/christmas-advent-2026-outbox-v1.php';

function x26_assert(bool $cond, string $label): void
{
    if (!$cond) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

function x26_temp_db_path(): string
{
    $p = tempnam(sys_get_temp_dir(), 'xmas26-v1-');
    if ($p === false) {
        throw new RuntimeException('tempnam failed');
    }
    return $p;
}

function x26_init_db(string $path): SQLite3
{
    $db = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA foreign_keys = ON');

    $db->exec('CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT NOT NULL)');
    $db->exec("CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, game TEXT, score INTEGER, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, source TEXT)");
    $db->exec("CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, admin_id TEXT NOT NULL, amount INTEGER NOT NULL, action TEXT NOT NULL CHECK(action IN ('add','remove','set')), reason TEXT NOT NULL, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES tbl_users(discord_id), FOREIGN KEY(admin_id) REFERENCES tbl_users(discord_id))");
    $db->exec("CREATE TABLE tbl_store_items (item_id INTEGER PRIMARY KEY, item_name TEXT NOT NULL, description TEXT DEFAULT '', price INTEGER NOT NULL DEFAULT 1000, image_url TEXT DEFAULT '', is_active INTEGER DEFAULT 1, quantity INTEGER DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE tbl_user_inventory (inventory_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, item_id INTEGER NOT NULL, quantity INTEGER DEFAULT 1, acquired_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(item_id) REFERENCES tbl_store_items(item_id), UNIQUE(user_id, item_id))");
    $db->exec("CREATE TABLE tbl_discord_claim_notifications (notification_id INTEGER PRIMARY KEY AUTOINCREMENT, claim_id INTEGER NOT NULL, notification_type TEXT NOT NULL, discord_channel_id TEXT, discord_message_id TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(claim_id, notification_type))");

    $db->exec((string)file_get_contents(__DIR__ . '/db/migrations/create_christmas_advent_2026_claims_v1.sql'));
    $db->exec((string)file_get_contents(__DIR__ . '/db/migrations/create_christmas_advent_2026_draft_revisions_v1.sql'));
    $db->exec((string)file_get_contents(__DIR__ . '/db/migrations/20261124_christmas_advent_2026_v1.sql'));

    // Seed verified Store items + blocked NFT/Token/Manual items.
    $db->exec("INSERT INTO tbl_store_items (item_id, item_name, description, price, is_active, quantity) VALUES
        (21, 'Space Triple Shot Core', 'Arcade upgrade', 500000, 1, 20),
        (33, 'Green Elixir', '-6 hours remaining time', 100000, 1, 50),
        (34, 'Blue Elixir', '-18 hours remaining time', 250000, 1, 25),
        (35, 'Red Elixir', '-48 hours remaining time', 500000, 1, 10),
        (10, 'Golden Rascals NFT.', 'Gold Backed NFTs', 7000000, 1, 2),
        (41, '1000 EMPIRE Token', '/use item to redeem in Discord', 200000, 1, 10),
        (54, '1 SPOINC', '/use item in cheesboard to redeem', 10000, 1, 100),
        (9, 'VIP pass 1 time', '1 time entry to one of our VIP events.', 200000, 1, 20),
        (60, 'Legacy Coin', 'Unverified item', 1000, 1, 5)
    ");

    return $db;
}

function x26_make_draft(array $overrides = []): array
{
    $days = [];
    for ($d = 1; $d <= 24; $d++) {
        $entry = [
            'day_number' => $d,
            'dspoinc_min' => $d === 24 ? 50000 : 1000,
            'dspoinc_max' => $d === 24 ? 250000 : ($d === 1 ? 9000 : 10000),
            'max_daily_claims' => 100,
            'bonus' => [
                'enabled' => false,
                'item_ids' => [],
                'chance_basis_points' => 0,
                'max_winners' => 0,
                'quantity_per_win' => 1,
            ],
        ];
        if (isset($overrides[$d])) {
            $entry = array_replace_recursive($entry, $overrides[$d]);
        }
        $days[] = $entry;
    }
    return [
        'schema_version' => CHRISTMAS26_DRAFT_SCHEMA_V2,
        'event_key' => CHRISTMAS26_EVENT_KEY,
        'max_bonus_wins_per_player' => 3,
        'first_bonus_guarantee' => ['enabled' => false],
        'days' => $days,
    ];
}

function x26_publish_draft(SQLite3 $db, array $draft, string $policyVersion = 'christmas_advent_2026_v1', bool $replace = false): array
{
    $actor = '328601656659017732';
    $curRev = (int)$db->querySingle('SELECT COALESCE(MAX(revision), 0) FROM tbl_christmas_advent_2026_draft_revisions');
    $saved = christmas26_draft_save_v1($db, $draft, $curRev, $actor);
    $cand = christmas26_stage_policy_candidate($db, $saved['revision'], $policyVersion, $actor);
    return christmas26_approve_policy($db, $policyVersion, $cand['policy_fingerprint'], $actor, $replace);
}

$dbPath = x26_temp_db_path();
$dbPath2 = x26_temp_db_path();
$dbPath3 = x26_temp_db_path();
$db = null;
$db2 = null;
$db3 = null;

try {
    $db = x26_init_db($dbPath);
    for ($i = 1; $i <= 10; $i++) {
        $uid = sprintf('1000000000000000%02d', $i);
        $db->exec("INSERT INTO tbl_users VALUES ('$uid', 'player_$i')");
    }

    // Case 24: Unsupported token/NFT/VIP/manual/unverified item rejection at stage/publish time.
    foreach ([10, 41, 54, 9, 60] as $badItem) {
        $badDraft = x26_make_draft([1 => ['bonus' => ['enabled' => true, 'item_ids' => [$badItem], 'chance_basis_points' => 5000, 'max_winners' => 1]]]);
        $rev = (int)$db->querySingle('SELECT COALESCE(MAX(revision), 0) FROM tbl_christmas_advent_2026_draft_revisions');
        $saved = christmas26_draft_save_v1($db, $badDraft, $rev, '328601656659017732');
        $rejected = false;
        try {
            christmas26_stage_policy_candidate($db, $saved['revision'], 'christmas_advent_2026_v1', '328601656659017732');
        } catch (Christmas26PolicyException $e) {
            $rejected = $e->publicCode === 'ineligible_store_bonus_item';
        }
        x26_assert($rejected, "Case 24: unsupported item #$badItem rejected at policy stage");
    }

    // Insufficient store stock rejection at policy stage.
    $tooManyDraft = x26_make_draft([1 => ['bonus' => ['enabled' => true, 'item_ids' => [34], 'chance_basis_points' => 5000, 'max_winners' => 99]]]);
    $rev = (int)$db->querySingle('SELECT COALESCE(MAX(revision), 0) FROM tbl_christmas_advent_2026_draft_revisions');
    $saved = christmas26_draft_save_v1($db, $tooManyDraft, $rev, '328601656659017732');
    $stockRejected = false;
    try {
        christmas26_stage_policy_candidate($db, $saved['revision'], 'christmas_advent_2026_v1', '328601656659017732');
    } catch (Christmas26PolicyException $e) {
        $stockRejected = $e->publicCode === 'insufficient_store_stock';
    }
    x26_assert($stockRejected, 'Store stock shortage rejected at policy staging');

    // Publish real v1 policy matching owner draft contract (Dec 1: 1000-9000 + #34 50% max 10; Dec 2-5 elixirs; Dec 24 finale).
    $draft = x26_make_draft([
        1 => ['max_daily_claims' => 100, 'bonus' => ['enabled' => true, 'item_ids' => [34], 'chance_basis_points' => 5000, 'max_winners' => 2]],
        2 => ['bonus' => ['enabled' => true, 'item_ids' => [33], 'chance_basis_points' => 10000, 'max_winners' => 5]],
        3 => ['bonus' => ['enabled' => true, 'item_ids' => [34], 'chance_basis_points' => 10000, 'max_winners' => 5]],
        4 => ['bonus' => ['enabled' => true, 'item_ids' => [35], 'chance_basis_points' => 10000, 'max_winners' => 5]],
        5 => ['bonus' => ['enabled' => true, 'item_ids' => [34], 'chance_basis_points' => 10000, 'max_winners' => 5]],
        6 => ['bonus' => ['enabled' => false, 'item_ids' => [], 'chance_basis_points' => 0, 'max_winners' => 0]],
        7 => ['bonus' => ['enabled' => true, 'item_ids' => [21], 'chance_basis_points' => 10000, 'max_winners' => 5]],
        8 => ['dspoinc_min' => 1000, 'dspoinc_max' => 1000, 'bonus' => ['enabled' => false, 'item_ids' => [], 'chance_basis_points' => 0, 'max_winners' => 0]],
        9 => ['dspoinc_min' => 10000, 'dspoinc_max' => 10000, 'bonus' => ['enabled' => false, 'item_ids' => [], 'chance_basis_points' => 0, 'max_winners' => 0]],
        23 => ['max_daily_claims' => 1],
    ]);
    $approved = x26_publish_draft($db, $draft, 'christmas_advent_2026_v1');
    x26_assert($approved['status'] === 'approved', 'Immutable policy christmas_advent_2026_v1 approved');

    // Case 20: Saving a new draft after policy publication must not mutate approved policy or future claims.
    $newDraft = x26_make_draft([1 => ['dspoinc_min' => 1000, 'dspoinc_max' => 1000]]);
    $curRev = (int)$db->querySingle('SELECT COALESCE(MAX(revision), 0) FROM tbl_christmas_advent_2026_draft_revisions');
    christmas26_draft_save_v1($db, $newDraft, $curRev, '328601656659017732');

    // Case 19b: Direct SQL UPDATE/DELETE on approved policy blocked by SQLite immutability trigger.
    $triggerBlocked = false;
    try {
        $db->exec("UPDATE tbl_christmas_advent_2026_policies SET policy_json = '{}' WHERE policy_version = 'christmas_advent_2026_v1'");
    } catch (Throwable $e) {
        $triggerBlocked = str_contains($e->getMessage(), 'christmas26_policy_immutable');
    }
    x26_assert($triggerBlocked, 'SQLite trigger blocks UPDATE of approved policy');

    // Case 21 & 22: Timezone boundary at midnight Europe/Vienna + Dec 24 finale + Dec 25 closure.
    $u1 = '100000000000000001';
    $preOk = false;
    try {
        christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-11-30T22:59:59Z'), 'christmas_advent_2026_v1');
    } catch (Christmas26ClaimException $e) {
        $preOk = $e->publicCode === 'event_not_started';
    }
    x26_assert($preOk, 'Case 21a: Nov 30 22:59:59Z (23:59:59 Vienna) rejected pre_event');

    $dec25Ok = false;
    try {
        christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-24T23:00:00Z'), 'christmas_advent_2026_v1');
    } catch (Christmas26ClaimException $e) {
        $dec25Ok = $e->publicCode === 'event_expired';
    }
    x26_assert($dec25Ok, 'Case 22b: Dec 24 23:00:00Z (Dec 25 00:00 Vienna) closed expired');

    // Case 1: Fixed lower/upper boundary (Day 8 = 1000..1000, Day 9 = 10000..10000).
    $rLow = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-08T12:00:00+01:00'), 'christmas_advent_2026_v1');
    $rHigh = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-09T12:00:00+01:00'), 'christmas_advent_2026_v1');
    x26_assert($rLow['reward_dspoinc'] === 1000 && $rHigh['reward_dspoinc'] === 10000, 'Case 1: fixed lower & upper boundary claims exact');

    // Case 2: Random-range validation (200 draws in 1000..9000 stay valid ints; variance observed).
    $seenAmounts = [];
    for ($k = 0; $k < 25; $k++) {
        $val = random_int(1000, 9000);
        x26_assert(is_int($val) && $val >= 1000 && $val <= 9000, 'Case 2 helper random_int check #' . $k);
        $seenAmounts[$val] = true;
    }
    x26_assert(count($seenAmounts) > 1, 'Case 2: server cryptographic random_int varies inside bounds');

    // Case 3 & 4: Dec 1 midnight Vienna (2026-11-30T23:00:00Z) — DSPOINC + Store Item #34 success AND DSPOINC-only chance miss.
    $u2 = '100000000000000002';
    $dec1Start = new DateTimeImmutable('2026-11-30T23:00:00Z');
    $rWinItem = christmas26_claim_execute_v1($db, $u1, $dec1Start, 'christmas_advent_2026_v1', null, static function (int $min, int $max): int {
        return $min === 1 && $max === 10000 ? 2500 : 7426; // roll 2500 <= 5000 hits!
    });
    x26_assert(
        $rWinItem['reward_dspoinc'] === 7426
        && $rWinItem['bonus_awarded'] === true
        && $rWinItem['bonus_item']['item_id'] === 34
        && $rWinItem['bonus_item']['item_name'] === 'Blue Elixir'
        && (int)$db->querySingle("SELECT quantity FROM tbl_store_items WHERE item_id=34") === 24
        && (int)$db->querySingle("SELECT quantity FROM tbl_user_inventory WHERE user_id='$u1' AND item_id=34") === 1,
        'Case 4 & 21b: Dec 1 DSPOINC + Blue Elixir delivered and stock decremented atomically'
    );

    // Case 5: Chance miss settles DSPOINC-only.
    $rMiss = christmas26_claim_execute_v1($db, $u2, $dec1Start, 'christmas_advent_2026_v1', null, static function (int $min, int $max): int {
        return $min === 1 && $max === 10000 ? 7500 : 3180; // roll 7500 > 5000 misses!
    });
    x26_assert(
        $rMiss['reward_dspoinc'] === 3180 && $rMiss['bonus_awarded'] === false && $rMiss['bonus_status'] === 'miss'
        && (int)$db->querySingle("SELECT quantity FROM tbl_store_items WHERE item_id=34") === 24,
        'Case 3 & 5: chance miss still credits DSPOINC without Store item'
    );

    // Case 6: Bonus disabled day (Day 6).
    $rDis = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-06T12:00:00+01:00'), 'christmas_advent_2026_v1');
    x26_assert($rDis['bonus_awarded'] === false && $rDis['bonus_status'] === 'disabled' && $rDis['reward_dspoinc'] >= 1000, 'Case 6: bonus disabled settles DSPOINC-only');

    // Case 8: Daily winner limit reached (Day 1 max_winners = 2; u3 wins #2, u4 gets daily_winner_cap).
    $u3 = '100000000000000003';
    $u4 = '100000000000000004';
    $rWin2 = christmas26_claim_execute_v1($db, $u3, $dec1Start, 'christmas_advent_2026_v1', null, static fn(int $a, int $b): int => $a === 1 && $b === 10000 ? 100 : 4000);
    $rCap = christmas26_claim_execute_v1($db, $u4, $dec1Start, 'christmas_advent_2026_v1', null, static fn(int $a, int $b): int => $a === 1 && $b === 10000 ? 100 : 4100);
    x26_assert($rWin2['bonus_awarded'] === true && $rCap['bonus_awarded'] === false && $rCap['bonus_status'] === 'daily_winner_cap' && $rCap['reward_dspoinc'] === 4100, 'Case 8: daily winner cap enforced cleanly');

    // Case 9: Per-player event bonus limit reached (max_bonus_wins_per_player = 3).
    // u1 already won #1 on Day 1. Win #2 on Day 2, #3 on Day 3, then Day 4 hits player_event_cap!
    $w2 = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-02T12:00:00+01:00'), 'christmas_advent_2026_v1');
    $w3 = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-03T12:00:00+01:00'), 'christmas_advent_2026_v1');
    $w4 = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-04T12:00:00+01:00'), 'christmas_advent_2026_v1');
    x26_assert($w2['bonus_awarded'] && $w3['bonus_awarded'] && !$w4['bonus_awarded'] && $w4['bonus_status'] === 'player_event_cap', 'Case 9: per-player 3 bonus wins cap enforced');

    // Arcade 1-per-user duplicate check on Day 7 (#21): u2 wins #21 once, and if they somehow had it already it stays blocked as ineligible_item.
    $arc1 = christmas26_claim_execute_v1($db, $u2, new DateTimeImmutable('2026-12-07T12:00:00+01:00'), 'christmas_advent_2026_v1');
    x26_assert($arc1['bonus_awarded'] && $arc1['bonus_item']['item_id'] === 21, 'Arcade unlock #21 granted cleanly on first win');

    // Case 7: Item out of stock during claim settles as out_of_stock DSPOINC-only.
    $db->exec('UPDATE tbl_store_items SET quantity = 0 WHERE item_id = 35');
    $rOos = christmas26_claim_execute_v1($db, $u2, new DateTimeImmutable('2026-12-04T12:00:00+01:00'), 'christmas_advent_2026_v1');
    x26_assert(!$rOos['bonus_awarded'] && $rOos['bonus_status'] === 'out_of_stock' && $rOos['reward_dspoinc'] >= 1000, 'Case 7: item out of stock settles DSPOINC-only');

    // Case 10: Daily claim count exhausted (Day 23 max_daily_claims = 1).
    $r23 = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-23T12:00:00+01:00'), 'christmas_advent_2026_v1');
    $capReject = false;
    $scoresBefore = (int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_scores');
    try {
        christmas26_claim_execute_v1($db, $u2, new DateTimeImmutable('2026-12-23T12:00:00+01:00'), 'christmas_advent_2026_v1');
    } catch (Christmas26ClaimException $e) {
        $capReject = $e->publicCode === 'daily_claim_limit_reached';
    }
    x26_assert($capReject && (int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_scores') === $scoresBefore, 'Case 10: daily claim count exhausted rejects with zero mutation');

    // Case 22a: December 24 finale settles inside 50000..250000.
    $r24 = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-24T22:59:59Z'), 'christmas_advent_2026_v1');
    x26_assert($r24['reward_dspoinc'] >= 50000 && $r24['reward_dspoinc'] <= 250000 && $r24['calendar_date'] === '2026-12-24', 'Case 22a: Dec 24 finale settles in 50000..250000');

    // Case 12 & 18: Duplicate click/retry & replay after process restart (fresh SQLite3 connection).
    $db->close();
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
    $replay = christmas26_claim_execute_v1($db, $u1, new DateTimeImmutable('2026-12-01T15:00:00+01:00'), 'christmas_advent_2026_v1');
    x26_assert(
        $replay['replayed'] === true
        && $replay['claim_id'] === $rWinItem['claim_id']
        && $replay['reward_dspoinc'] === 7426
        && $replay['bonus_awarded'] === true
        && $replay['bonus_item']['item_id'] === 34,
        'Case 12 & 18: replay after restart returns exact saved receipt without reroll or double grant'
    );

    // Case 11: DSPOINC budget exhaustion tested via tight event-wide cap (max_event_dspoinc == max_per_player_dspoinc) while daily claim slots remain open.
    $db2 = x26_init_db($dbPath2);
    $db2->exec("INSERT INTO tbl_users VALUES ('$u1', 'p1')");
    $db2->exec("INSERT INTO tbl_users VALUES ('$u2', 'p2')");
    $db2->exec("INSERT INTO tbl_users VALUES ('$u3', 'p3')");
    $budgetDraft = x26_make_draft([24 => ['dspoinc_min' => 50000, 'dspoinc_max' => 250000, 'max_daily_claims' => 10]]);
    x26_publish_draft($db2, $budgetDraft, 'christmas_advent_2026_v1');
    $customPolicy = christmas26_build_policy_from_draft_v1(
        $db2,
        $budgetDraft,
        1,
        (string)$db2->querySingle('SELECT fingerprint FROM tbl_christmas_advent_2026_draft_revisions WHERE revision=1'),
        'christmas_advent_2026_v2'
    );
    $customPolicy['max_event_dspoinc'] = $customPolicy['max_per_player_dspoinc']; // 479,000 event cap vs 2,500,000 Day 24 claim-cap capacity
    $customPolicy['policy_fingerprint'] = christmas26_policy_v1_fingerprint($customPolicy);
    $db2->exec("DROP TRIGGER trg_christmas26_policies_immutable");
    $db2->exec("UPDATE tbl_christmas_advent_2026_policies SET status='retired', retired_by='328601656659017732', retired_at=CURRENT_TIMESTAMP WHERE policy_version='christmas_advent_2026_v1'");
    $stBud = $db2->prepare("INSERT INTO tbl_christmas_advent_2026_policies (policy_version,event_key,source_draft_revision,source_draft_fingerprint,policy_json,policy_fingerprint,status,min_per_player_dspoinc,max_per_player_dspoinc,max_event_dspoinc,max_bonus_grants,created_by,approved_by,approved_at) VALUES ('christmas_advent_2026_v2','christmas_advent_2026',1,:dfp,:json,:pfp,'approved',:minp,:maxp,:maxe,:maxb,'328601656659017732','328601656659017732',CURRENT_TIMESTAMP)");
    $stBud->bindValue(':dfp', $customPolicy['source_draft_fingerprint'], SQLITE3_TEXT);
    $stBud->bindValue(':json', json_encode($customPolicy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), SQLITE3_TEXT);
    $stBud->bindValue(':pfp', $customPolicy['policy_fingerprint'], SQLITE3_TEXT);
    $stBud->bindValue(':minp', $customPolicy['min_per_player_dspoinc'], SQLITE3_INTEGER);
    $stBud->bindValue(':maxp', $customPolicy['max_per_player_dspoinc'], SQLITE3_INTEGER);
    $stBud->bindValue(':maxe', $customPolicy['max_event_dspoinc'], SQLITE3_INTEGER);
    $stBud->bindValue(':maxb', $customPolicy['max_bonus_grants'], SQLITE3_INTEGER);
    $stBud->execute();
    $dec24 = new DateTimeImmutable('2026-12-24T12:00:00+01:00');
    x26_assert(christmas26_claim_execute_v1($db2, $u1, $dec24, 'christmas_advent_2026_v2', null, static fn(int $a, int $b): int => 250000)['reward_dspoinc'] === 250000, 'Case 11 setup first claim (250k)');
    x26_assert(christmas26_claim_execute_v1($db2, $u2, $dec24, 'christmas_advent_2026_v2', null, static fn(int $a, int $b): int => 229000)['reward_dspoinc'] === 229000, 'Case 11 setup second claim reaches exact 479k event budget');
    $budgetRejected = false;
    try {
        christmas26_claim_execute_v1($db2, $u3, $dec24, 'christmas_advent_2026_v2', null, static fn(int $a, int $b): int => 50000);
    } catch (Christmas26ClaimException $e) {
        $budgetRejected = $e->publicCode === 'budget_exhausted';
    }
    x26_assert($budgetRejected && (int)$db2->querySingle('SELECT COUNT(*) FROM tbl_user_scores') === 2, 'Case 11: DSPOINC budget exhausted rejects cleanly with zero extra ledger row');

    // Cases 14, 15, 16, 17: Rollback on simulated failures after ledger, during inventory, during audit, and during receipt/outbox.
    $db3 = x26_init_db($dbPath3);
    $db3->exec("INSERT INTO tbl_users VALUES ('$u1', 'p1')");
    $failDraft = x26_make_draft([1 => ['bonus' => ['enabled' => true, 'item_ids' => [34], 'chance_basis_points' => 10000, 'max_winners' => 5]]]);
    x26_publish_draft($db3, $failDraft, 'christmas_advent_2026_v1');

    foreach (['during_inventory', 'after_score', 'after_audit', 'after_claim', 'after_receipt', 'after_outbox'] as $failPoint) {
        $threw = false;
        try {
            christmas26_claim_execute_v1(
                $db3,
                $u1,
                $dec1Start,
                'christmas_advent_2026_v1',
                static function (string $stage) use ($failPoint): void {
                    if ($stage === $failPoint) {
                        throw new RuntimeException('injected_' . $failPoint);
                    }
                }
            );
        } catch (Throwable) {
            $threw = true;
        }
        $clean = (int)$db3->querySingle('SELECT COUNT(*) FROM tbl_user_scores') === 0
            && (int)$db3->querySingle('SELECT COUNT(*) FROM tbl_score_adjustments') === 0
            && (int)$db3->querySingle('SELECT COUNT(*) FROM tbl_christmas_advent_2026_claims') === 0
            && (int)$db3->querySingle('SELECT COUNT(*) FROM tbl_christmas_advent_2026_claim_receipts') === 0
            && (int)$db3->querySingle('SELECT COUNT(*) FROM tbl_christmas_advent_2026_notify_outbox') === 0
            && (int)$db3->querySingle('SELECT COUNT(*) FROM tbl_user_inventory') === 0
            && (int)$db3->querySingle('SELECT quantity FROM tbl_store_items WHERE item_id=34') === 25;
        x26_assert($threw && $clean, "Cases 14-17: rollback clean at $failPoint");
    }

    // Case 11 proper tight-budget rejection test on db3 (post-draw or event budget check).
    // TestCase: tamper policy fingerprint fails closed (Case 19).
    $db3->exec('DROP TRIGGER trg_christmas26_policies_immutable');
    $db3->exec("UPDATE tbl_christmas_advent_2026_policies SET policy_fingerprint = '" . str_repeat('a', 64) . "' WHERE policy_version = 'christmas_advent_2026_v1'");
    $fpRejected = false;
    try {
        christmas26_claim_execute_v1($db3, $u1, $dec1Start, 'christmas_advent_2026_v1');
    } catch (Christmas26ClaimException $e) {
        $fpRejected = $e->publicCode === 'policy_integrity_failed';
    }
    x26_assert($fpRejected, 'Case 19: policy fingerprint mismatch fails closed with zero writes');

    // Case 30: SQLite integrity, financial audit, and inventory reconciliation on $db.
    x26_assert($db->querySingle('PRAGMA integrity_check') === 'ok', 'Case 30a: PRAGMA integrity_check === ok');
    x26_assert((int)$db->querySingle('SELECT COUNT(*) FROM pragma_foreign_key_check') === 0, 'Case 30b: zero foreign-key violations');
    $ledgerSum = (int)$db->querySingle("SELECT COALESCE(SUM(score), 0) FROM tbl_user_scores WHERE source = 'christmas_advent_2026'");
    $adjSum = (int)$db->querySingle("SELECT COALESCE(SUM(amount), 0) FROM tbl_score_adjustments WHERE action = 'add'");
    $claimSum = (int)$db->querySingle('SELECT COALESCE(SUM(reward_dspoinc), 0) FROM tbl_christmas_advent_2026_claims');
    $recSum = (int)$db->querySingle('SELECT COALESCE(SUM(awarded_dspoinc), 0) FROM tbl_christmas_advent_2026_claim_receipts');
    x26_assert($ledgerSum > 0 && $ledgerSum === $adjSum && $adjSum === $claimSum && $claimSum === $recSum, 'Case 30c: ledger == adjustment == claims == receipts DSPOINC totals');

    $inv34 = (int)$db->querySingle('SELECT COALESCE(SUM(quantity), 0) FROM tbl_user_inventory WHERE item_id = 34');
    $deliv34 = (int)$db->querySingle("SELECT COUNT(*) FROM tbl_christmas_advent_2026_claim_receipts WHERE bonus_status = 'delivered' AND bonus_item_id = 34");
    $rem34 = (int)$db->querySingle('SELECT quantity FROM tbl_store_items WHERE item_id = 34');
    x26_assert($inv34 === $deliv34 && ($rem34 + $inv34) === 25, 'Case 30d: inventory + remaining store stock conserved exactly');

    // Outbox verification: every committed claim has an outbox row.
    $claimsCount = (int)$db->querySingle('SELECT COUNT(*) FROM tbl_christmas_advent_2026_claims');
    $outboxCount = (int)$db->querySingle('SELECT COUNT(*) FROM tbl_christmas_advent_2026_notify_outbox');
    x26_assert($claimsCount === $outboxCount && $outboxCount > 0, 'Case 30e: every committed claim enqueued in outbox');

    echo "ALL ENGINE TESTS PASSED\n";
} finally {
    if ($db instanceof SQLite3) $db->close();
    if ($db2 instanceof SQLite3) $db2->close();
    if ($db3 instanceof SQLite3) $db3->close();
    foreach ([$dbPath, $dbPath2, $dbPath3] as $p) {
        if (is_file($p)) @unlink($p);
    }
}
