<?php
/**
 * Christmas Advent 2026 — Atomic Claim Engine V1.
 *
 * Implements two independently evaluated daily rewards inside one SQLite BEGIN IMMEDIATE:
 * A. Guaranteed server-drawn integer DSPOINC within [dspoinc_min, dspoinc_max].
 * B. Optional auto-grantable Normal Store Item bonus (no substitute, no reroll).
 * Plus durable claim receipts and transactional Discord #lootwinners outbox queueing.
 */
declare(strict_types=1);

require_once __DIR__ . '/christmas-advent-2026.php';
require_once __DIR__ . '/christmas-advent-2026-policy-v1.php';

const CHRISTMAS26_PAYOUT_GATE = 'NARRRFS_CHRISTMAS_ADVENT_2026_PAYOUT_ENABLED';
const CHRISTMAS26_POLICY_VERSION_ENV = 'NARRRFS_CHRISTMAS_ADVENT_2026_POLICY_VERSION';
const CHRISTMAS26_POLICY_VERSION = 'christmas_advent_2026_v1';
const CHRISTMAS26_CSRF_SESSION_KEY = 'christmas_advent_2026_claim_csrf';

final class Christmas26ClaimException extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $publicCode)
    {
        parent::__construct($publicCode);
    }
}

function christmas26_claim_payout_is_enabled(?string $gate, ?string $version): bool
{
    return $gate === '1' && is_string($version) && christmas26_policy_version_is_valid($version);
}

/**
 * Runtime HTTP gate: allows PAYOUT_ENABLED=1 when policy version env is 'active',
 * empty, or any valid policy_version string, and resolves the single approved DB policy.
 */
function christmas26_claim_runtime_gate_is_enabled(?string $gate, ?string $versionEnv = null): bool
{
    if ($gate !== '1') {
        return false;
    }
    if ($versionEnv === null || $versionEnv === '' || $versionEnv === 'active') {
        return true;
    }
    return christmas26_policy_version_is_valid($versionEnv);
}

function christmas26_claim_origin_is_allowed(array $server): bool
{
    return in_array(
        strtolower(trim((string)($server['HTTP_ORIGIN'] ?? ''))),
        ['https://narrrfs.world', 'http://localhost', 'http://127.0.0.1'],
        true
    );
}

function christmas26_claim_issue_csrf(array &$session): string
{
    $token = bin2hex(random_bytes(32));
    $session[CHRISTMAS26_CSRF_SESSION_KEY] = ['token' => $token, 'created_at' => time()];
    return $token;
}

function christmas26_claim_csrf_is_valid(array &$session, ?string $token): bool
{
    $entry = $session[CHRISTMAS26_CSRF_SESSION_KEY] ?? null;
    unset($session[CHRISTMAS26_CSRF_SESSION_KEY]);
    return is_array($entry)
        && is_string($token)
        && is_string($entry['token'] ?? null)
        && is_int($entry['created_at'] ?? null)
        && time() - $entry['created_at'] >= 0
        && time() - $entry['created_at'] <= 300
        && hash_equals($entry['token'], $token);
}

/** Legacy fixed-amount V1 helpers retained for regression compatibility. */
function christmas26_claim_policy_fingerprint(array $policy): string
{
    $v = [
        'event' => CHRISTMAS26_EVENT_KEY,
        'start' => '2026-12-01',
        'end' => '2026-12-24',
        'version' => $policy['version'] ?? null,
        'rewards' => $policy['rewards'] ?? null,
        'daily_caps' => $policy['daily_caps'] ?? null,
        'per_user_cap' => $policy['per_user_cap'] ?? null,
        'event_cap' => $policy['event_cap'] ?? null,
    ];
    return hash('sha256', json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function christmas26_claim_policy_reason(array $policy): string
{
    return CHRISTMAS26_EVENT_KEY . '|' . $policy['version'] . '|' . christmas26_claim_policy_fingerprint($policy);
}

function christmas26_claim_policy_is_valid(array $policy): bool
{
    if (($policy['version'] ?? null) !== CHRISTMAS26_POLICY_VERSION
        || !is_string($policy['fingerprint'] ?? null)
        || !is_int($policy['per_user_cap'] ?? null)
        || !is_int($policy['event_cap'] ?? null)) {
        return false;
    }
    for ($d = 1; $d <= 24; $d++) {
        if (!is_int($policy['rewards'][$d] ?? null)
            || $policy['rewards'][$d] <= 0
            || !is_int($policy['daily_caps'][$d] ?? null)
            || $policy['daily_caps'][$d] <= 0) {
            return false;
        }
    }
    return hash_equals(christmas26_claim_policy_fingerprint($policy), $policy['fingerprint']);
}

function christmas26_claim_replay_receipt(SQLite3 $db, string $userId, string $date, array $policy): ?array
{
    $q = $db->prepare(
        'SELECT c.claim_id,c.reward_dspoinc,c.claimed_at,c.actor_user_id,
                s.user_id su,s.game,s.source,s.score,
                a.user_id au,a.admin_id,a.amount,a.action,a.reason
         FROM tbl_christmas_advent_2026_claims c
         LEFT JOIN tbl_user_scores s ON s.id=c.score_id
         LEFT JOIN tbl_score_adjustments a ON a.id=c.adjustment_id
         WHERE c.event_key=:e AND c.user_id=:u AND c.calendar_date=:d'
    );
    if (!$q instanceof SQLite3Stmt) {
        throw new RuntimeException('claim_unavailable');
    }
    $q->bindValue(':e', CHRISTMAS26_EVENT_KEY);
    $q->bindValue(':u', $userId);
    $q->bindValue(':d', $date);
    $z = $q->execute();
    $r = $z instanceof SQLite3Result ? $z->fetchArray(SQLITE3_ASSOC) : false;
    if ($r === false) {
        return null;
    }
    if ($z->fetchArray(SQLITE3_ASSOC) !== false
        || (string)$r['actor_user_id'] !== $userId
        || (string)$r['su'] !== $userId
        || (string)$r['game'] !== CHRISTMAS26_EVENT_KEY
        || (string)$r['source'] !== CHRISTMAS26_EVENT_KEY
        || (int)$r['score'] !== (int)$r['reward_dspoinc']
        || (string)$r['au'] !== $userId
        || (string)$r['admin_id'] !== $userId
        || (int)$r['amount'] !== (int)$r['reward_dspoinc']
        || (string)$r['action'] !== 'add'
        || !hash_equals(christmas26_claim_policy_reason($policy), (string)$r['reason'])) {
        throw new RuntimeException('claim_unavailable');
    }
    return [
        'claim_id' => (int)$r['claim_id'],
        'reward_dspoinc' => (int)$r['reward_dspoinc'],
        'claimed_at' => (string)$r['claimed_at'],
        'calendar_date' => $date,
        'replayed' => true,
    ];
}

function christmas26_claim_execute(SQLite3 $db, string $userId, DateTimeImmutable $now, array $policy, ?callable $hook = null): array
{
    if (!preg_match('/^\d{17,20}$/', $userId) || !christmas26_claim_policy_is_valid($policy) || christmas26_event_state($now) !== 'active') {
        throw new RuntimeException('claim_unavailable');
    }
    $v = christmas26_vienna_time($now);
    $d = (int)$v->format('j');
    $date = $v->format('Y-m-d');
    $tx = false;
    try {
        if (!$db->exec('PRAGMA foreign_keys=ON')
            || (int)$db->querySingle('PRAGMA foreign_keys') !== 1
            || christmas26_claims_schema_error($db) !== null
            || !$db->exec('BEGIN IMMEDIATE')) {
            throw new RuntimeException('claim_unavailable');
        }
        $tx = true;
        $q = "'" . SQLite3::escapeString($userId) . "'";
        if (!$db->querySingle("SELECT 1 FROM tbl_users WHERE discord_id=$q")) {
            throw new RuntimeException('claim_unavailable');
        }
        $rows = christmas26_verified_claims($db, $userId);
        if (isset($rows[$d])) {
            $r = christmas26_claim_replay_receipt($db, $userId, $date, $policy);
            if ($r === null) {
                throw new RuntimeException('claim_unavailable');
            }
            $db->exec('COMMIT');
            $tx = false;
            return $r;
        }
        $x = $db->querySingle(
            "SELECT COALESCE(SUM(reward_dspoinc),0) e,
                    COALESCE(SUM(CASE WHEN calendar_date='$date' THEN reward_dspoinc ELSE 0 END),0) d,
                    COALESCE(SUM(CASE WHEN user_id=$q THEN reward_dspoinc ELSE 0 END),0) u
             FROM tbl_christmas_advent_2026_claims
             WHERE event_key='" . CHRISTMAS26_EVENT_KEY . "'",
            true
        );
        $a = $policy['rewards'][$d];
        if (!is_array($x) || $x['e'] + $a > $policy['event_cap'] || $x['d'] + $a > $policy['daily_caps'][$d] || $x['u'] + $a > $policy['per_user_cap']) {
            throw new RuntimeException('budget_exhausted');
        }
        $st = $db->prepare('INSERT INTO tbl_user_scores(user_id,game,score,source)VALUES(:u,:g,:a,:g)');
        $st->bindValue(':u', $userId);
        $st->bindValue(':g', CHRISTMAS26_EVENT_KEY);
        $st->bindValue(':a', $a, SQLITE3_INTEGER);
        $st->execute();
        $sid = $db->lastInsertRowID();
        $hook && $hook('after_score', $db);

        $st = $db->prepare("INSERT INTO tbl_score_adjustments(user_id,admin_id,amount,action,reason)VALUES(:u,:u,:a,'add',:r)");
        $st->bindValue(':u', $userId);
        $st->bindValue(':a', $a, SQLITE3_INTEGER);
        $st->bindValue(':r', christmas26_claim_policy_reason($policy));
        $st->execute();
        $aid = $db->lastInsertRowID();
        $hook && $hook('after_audit', $db);

        $st = $db->prepare('INSERT INTO tbl_christmas_advent_2026_claims(event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id)VALUES(:e,:u,:dt,:d,:a,:u,:s,:i)');
        foreach ([':e' => CHRISTMAS26_EVENT_KEY, ':u' => $userId, ':dt' => $date, ':d' => $d, ':a' => $a, ':s' => $sid, ':i' => $aid] as $k => $z) {
            $st->bindValue($k, $z, is_int($z) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $st->execute();
        $id = $db->lastInsertRowID();
        $hook && $hook('after_marker', $db);
        if (!isset(christmas26_verified_claims($db, $userId)[$d])) {
            throw new RuntimeException('claim_unavailable');
        }
        $at = (string)$db->querySingle("SELECT claimed_at FROM tbl_christmas_advent_2026_claims WHERE claim_id=$id");
        $db->exec('COMMIT');
        $tx = false;
        return ['claim_id' => $id, 'calendar_date' => $date, 'reward_dspoinc' => $a, 'claimed_at' => $at, 'replayed' => false];
    } catch (Throwable $e) {
        if ($tx) {
            $db->exec('ROLLBACK');
        }
        if ($e->getMessage() === 'budget_exhausted') {
            throw $e;
        }
        throw new RuntimeException('claim_unavailable');
    }
}

/** Check whether player already owns or previously received a lifetime 1-per-user Arcade unlock. */
function christmas26_player_has_arcade_item(SQLite3 $db, string $userId, int $itemId): bool
{
    $tableExists = static function (string $table) use ($db): bool {
        $q = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:n LIMIT 1");
        $q->bindValue(':n', $table, SQLITE3_TEXT);
        $r = $q->execute();
        return $r instanceof SQLite3Result && $r->fetchArray(SQLITE3_NUM) !== false;
    };

    $queries = [
        ['tbl_user_inventory', 'SELECT 1 FROM tbl_user_inventory WHERE user_id = :u AND item_id = :id AND COALESCE(quantity, 0) > 0 LIMIT 1'],
        ['tbl_item_usage_requests', "SELECT 1 FROM tbl_item_usage_requests WHERE user_id = :u AND item_id = :id AND status IN ('pending','approved') LIMIT 1"],
        ['tbl_item_usage_history', "SELECT 1 FROM tbl_item_usage_history WHERE user_id = :u AND item_id = :id AND status IN ('pending','approved','used') LIMIT 1"],
        ['tbl_purchase_history', 'SELECT 1 FROM tbl_purchase_history WHERE user_id = :u AND item_id = :id LIMIT 1'],
        ['tbl_reward_box_open_history', "SELECT 1 FROM tbl_reward_box_open_history WHERE user_id = :u AND reward_type = 'store_item' AND reward_reference_id = :id LIMIT 1"],
        [CHRISTMAS26_CLAIM_RECEIPTS_TABLE, "SELECT 1 FROM tbl_christmas_advent_2026_claim_receipts WHERE user_id = :u AND bonus_status = 'delivered' AND bonus_item_id = :id LIMIT 1"],
    ];

    foreach ($queries as [$table, $sql]) {
        if (!$tableExists($table)) {
            continue;
        }
        $st = $db->prepare($sql);
        $st->bindValue(':u', $userId, SQLITE3_TEXT);
        $st->bindValue(':id', $itemId, SQLITE3_INTEGER);
        $res = $st->execute();
        if ($res instanceof SQLite3Result && $res->fetchArray(SQLITE3_NUM) !== false) {
            return true;
        }
    }
    return false;
}

/** Verify all V1 additive tables needed by the range+item+outbox claim engine exist. */
function christmas26_v1_schema_error(SQLite3 $db): ?string
{
    $base = christmas26_claims_schema_error($db);
    if ($base !== null) {
        return $base;
    }
    foreach ([CHRISTMAS26_POLICIES_TABLE, CHRISTMAS26_CLAIM_RECEIPTS_TABLE, CHRISTMAS26_NOTIFY_OUTBOX_TABLE, 'tbl_user_inventory', 'tbl_store_items'] as $table) {
        $st = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:n LIMIT 1");
        $st->bindValue(':n', $table, SQLITE3_TEXT);
        $res = $st->execute();
        if (!$res instanceof SQLite3Result || $res->fetchArray(SQLITE3_NUM) === false) {
            return 'claim_schema_unavailable_' . $table;
        }
    }
    return null;
}

function christmas26_v1_policy_reason(string $policyVersion, string $policyFingerprint): string
{
    return CHRISTMAS26_EVENT_KEY . '|' . $policyVersion . '|' . $policyFingerprint;
}

/** Format an authoritative claim receipt for API / UI consumption. */
function christmas26_format_receipt_v1(array $row, bool $replayed): array
{
    $bonusDelivered = ($row['bonus_status'] ?? '') === 'delivered';
    return [
        'claim_id' => (int)$row['claim_id'],
        'day_number' => (int)$row['day_number'],
        'calendar_date' => (string)$row['calendar_date'],
        'claimed_at' => (string)$row['claimed_at'],
        'policy_version' => (string)$row['policy_version'],
        'reward_dspoinc' => (int)$row['awarded_dspoinc'],
        'bonus_status' => (string)$row['bonus_status'],
        'bonus_awarded' => $bonusDelivered,
        'bonus_item' => $bonusDelivered ? [
            'item_id' => (int)$row['bonus_item_id'],
            'item_name' => (string)$row['bonus_item_name'],
            'quantity' => (int)$row['bonus_quantity'],
        ] : null,
        'replayed' => $replayed,
        'saved' => true,
    ];
}

/** Read-only player receipts map keyed by day_number (1..24) for Profile UI reload/relogin. */
function christmas26_player_receipts_v1(SQLite3 $db, string $userId): array
{
    $hasReceipts = (int)$db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . CHRISTMAS26_CLAIM_RECEIPTS_TABLE . "'") === 1;
    if (!$hasReceipts) {
        return [];
    }
    $st = $db->prepare(
        'SELECT c.claim_id, c.day_number, c.calendar_date, c.claimed_at,
                r.policy_version, r.awarded_dspoinc, r.bonus_status, r.bonus_item_id, r.bonus_item_name, r.bonus_quantity
         FROM tbl_christmas_advent_2026_claims c
         INNER JOIN tbl_christmas_advent_2026_claim_receipts r ON r.claim_id = c.claim_id
         WHERE c.event_key = :e AND c.user_id = :u
         ORDER BY c.day_number ASC'
    );
    $st->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
    $st->bindValue(':u', $userId, SQLITE3_TEXT);
    $res = $st->execute();
    $out = [];
    while ($res instanceof SQLite3Result && ($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
        $out[(int)$row['day_number']] = christmas26_format_receipt_v1($row, false);
    }
    return $out;
}

/** Reconcile and return an already-settled V1 daily claim without rolling or mutating. */
function christmas26_replay_receipt_v1(SQLite3 $db, string $userId, int $day, string $date): ?array
{
    $q = $db->prepare(
        'SELECT c.claim_id, c.event_key, c.user_id, c.actor_user_id, c.calendar_date, c.day_number, c.reward_dspoinc, c.claimed_at,
                r.policy_version, r.policy_fingerprint, r.dspoinc_min, r.dspoinc_max, r.awarded_dspoinc,
                r.bonus_status, r.bonus_item_id, r.bonus_item_name, r.bonus_quantity,
                s.user_id AS su, s.game, s.source, s.score,
                a.user_id AS au, a.admin_id, a.amount, a.action, a.reason
         FROM tbl_christmas_advent_2026_claims c
         INNER JOIN tbl_christmas_advent_2026_claim_receipts r ON r.claim_id = c.claim_id
         LEFT JOIN tbl_user_scores s ON s.id = c.score_id
         LEFT JOIN tbl_score_adjustments a ON a.id = c.adjustment_id
         WHERE c.event_key = :e AND c.user_id = :u AND c.day_number = :d'
    );
    $q->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
    $q->bindValue(':u', $userId, SQLITE3_TEXT);
    $q->bindValue(':d', $day, SQLITE3_INTEGER);
    $z = $q->execute();
    $row = $z instanceof SQLite3Result ? $z->fetchArray(SQLITE3_ASSOC) : false;
    if ($row === false) {
        return null;
    }

    $awarded = (int)$row['awarded_dspoinc'];
    $expectedReason = christmas26_v1_policy_reason((string)$row['policy_version'], (string)$row['policy_fingerprint']);
    $replayPolicy = christmas26_load_verified_policy($db, (string)$row['policy_version'], true);

    if ($z->fetchArray(SQLITE3_ASSOC) !== false
        || !is_array($replayPolicy)
        || !hash_equals((string)$replayPolicy['policy_fingerprint'], (string)$row['policy_fingerprint'])
        || (string)$row['calendar_date'] !== $date
        || (string)$row['actor_user_id'] !== $userId
        || (int)$row['reward_dspoinc'] !== $awarded
        || (string)$row['su'] !== $userId
        || (string)$row['game'] !== CHRISTMAS26_EVENT_KEY
        || (string)$row['source'] !== CHRISTMAS26_EVENT_KEY
        || (int)$row['score'] !== $awarded
        || (string)$row['au'] !== $userId
        || (string)$row['admin_id'] !== $userId
        || (int)$row['amount'] !== $awarded
        || (string)$row['action'] !== 'add'
        || !hash_equals($expectedReason, (string)$row['reason'])) {
        throw new Christmas26ClaimException(503, 'claim_integrity_failed');
    }

    return christmas26_format_receipt_v1($row, true);
}

/**
 * Atomically settle or replay one Christmas Advent 2026 daily claim:
 * - Guaranteed cryptographically-drawn DSPOINC integer within approved policy [min, max].
 * - Independent optional auto-grantable Normal Store Item bonus.
 * - Durable receipt and outbox enqueue inside the same BEGIN IMMEDIATE transaction.
 */
function christmas26_claim_execute_v1(
    SQLite3 $db,
    string $userId,
    DateTimeImmutable $now,
    string $expectedPolicyVersion,
    ?callable $hook = null,
    ?callable $rng = null
): array {
    if (!preg_match('/^\d{17,20}$/D', $userId)
        || ($expectedPolicyVersion !== '' && $expectedPolicyVersion !== 'active' && !christmas26_policy_version_is_valid($expectedPolicyVersion))) {
        throw new Christmas26ClaimException(400, 'claim_unavailable');
    }

    $eventState = christmas26_event_state($now);
    if ($eventState === 'pre_event') {
        throw new Christmas26ClaimException(409, 'event_not_started');
    }
    if ($eventState !== 'active') {
        throw new Christmas26ClaimException(409, 'event_expired');
    }

    $vienna = christmas26_vienna_time($now);
    $day = (int)$vienna->format('j');
    $date = $vienna->format('Y-m-d');
    if ($day < 1 || $day > 24 || $date !== sprintf('2026-12-%02d', $day)) {
        throw new Christmas26ClaimException(409, 'event_expired');
    }

    $draw = $rng ?? static fn(int $min, int $max): int => random_int($min, $max);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $tx = false;

    try {
        try {
            if (!$db->exec('PRAGMA foreign_keys = ON')
                || (int)$db->querySingle('PRAGMA foreign_keys') !== 1
                || christmas26_v1_schema_error($db) !== null
                || !$db->exec('BEGIN IMMEDIATE')) {
                throw new Christmas26ClaimException(503, 'claim_unavailable');
            }
            $tx = true;
        } catch (Christmas26ClaimException $e) {
            throw $e;
        } catch (Throwable $e) {
            if (in_array($db->lastErrorCode() & 255, [5, 6], true)) {
                throw new Christmas26ClaimException(503, 'database_busy');
            }
            throw new Christmas26ClaimException(503, 'claim_unavailable');
        }

        // Verify user exists in tbl_users.
        $uStmt = $db->prepare('SELECT 1 FROM tbl_users WHERE discord_id = :u LIMIT 1');
        $uStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $uRes = $uStmt->execute();
        if (!$uRes instanceof SQLite3Result || $uRes->fetchArray(SQLITE3_NUM) === false) {
            throw new Christmas26ClaimException(404, 'claim_unavailable');
        }

        // Replay existing claim BEFORE any randomness, cap rejection, or write.
        $claims = christmas26_verified_claims($db, $userId);
        if (isset($claims[$day])) {
            $replay = christmas26_replay_receipt_v1($db, $userId, $day, $date);
            if ($replay === null) {
                throw new Christmas26ClaimException(503, 'claim_integrity_failed');
            }
            $db->exec('COMMIT');
            $tx = false;
            return $replay;
        }

        // Load and cryptographically verify the currently approved immutable policy.
        try {
            $policy = ($expectedPolicyVersion === '' || $expectedPolicyVersion === 'active')
                ? christmas26_load_active_approved_policy($db)
                : christmas26_load_verified_policy($db, $expectedPolicyVersion, false);
        } catch (RuntimeException $e) {
            throw new Christmas26ClaimException(503, $e->getMessage());
        }
        if (!is_array($policy)) {
            throw new Christmas26ClaimException(503, 'claim_policy_unavailable');
        }

        $daySpec = $policy['days'][$day - 1] ?? null;
        if (!is_array($daySpec) || ($daySpec['day_number'] ?? 0) !== $day || ($daySpec['calendar_date'] ?? '') !== $date) {
            throw new Christmas26ClaimException(503, 'policy_integrity_failed');
        }

        // Check daily claim count limit and DSPOINC budgets separately.
        $aggStmt = $db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN calendar_date = :dt THEN 1 ELSE 0 END), 0) AS daily_claims,
                COALESCE(SUM(CASE WHEN calendar_date = :dt THEN reward_dspoinc ELSE 0 END), 0) AS daily_dspoinc,
                COALESCE(SUM(reward_dspoinc), 0) AS event_dspoinc,
                COALESCE(SUM(CASE WHEN user_id = :u THEN reward_dspoinc ELSE 0 END), 0) AS player_dspoinc
             FROM tbl_christmas_advent_2026_claims
             WHERE event_key = :e"
        );
        $aggStmt->bindValue(':dt', $date, SQLITE3_TEXT);
        $aggStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $aggStmt->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
        $aggRes = $aggStmt->execute();
        $agg = $aggRes instanceof SQLite3Result ? $aggRes->fetchArray(SQLITE3_ASSOC) : false;
        if (!is_array($agg)) {
            throw new Christmas26ClaimException(503, 'claim_unavailable');
        }

        if ((int)$agg['daily_claims'] >= (int)$daySpec['max_daily_claims']) {
            throw new Christmas26ClaimException(409, 'daily_claim_limit_reached');
        }

        $min = (int)$daySpec['dspoinc_min'];
        $max = (int)$daySpec['dspoinc_max'];
        if ((int)$agg['daily_dspoinc'] + $min > (int)$daySpec['daily_dspoinc_budget']
            || (int)$agg['event_dspoinc'] + $min > (int)$policy['max_event_dspoinc']
            || (int)$agg['player_dspoinc'] + $min > (int)$policy['max_per_player_dspoinc']) {
            throw new Christmas26ClaimException(409, 'budget_exhausted');
        }

        // Server-side random DSPOINC integer draw within [min, max].
        $awardedDspoinc = $draw($min, $max);
        if (!is_int($awardedDspoinc) || $awardedDspoinc < $min || $awardedDspoinc > $max) {
            throw new Christmas26ClaimException(500, 'invalid_draw_result');
        }
        if ((int)$agg['daily_dspoinc'] + $awardedDspoinc > (int)$daySpec['daily_dspoinc_budget']
            || (int)$agg['event_dspoinc'] + $awardedDspoinc > (int)$policy['max_event_dspoinc']
            || (int)$agg['player_dspoinc'] + $awardedDspoinc > (int)$policy['max_per_player_dspoinc']) {
            throw new Christmas26ClaimException(409, 'budget_exhausted');
        }

        $totStmt = $db->prepare('SELECT COALESCE(SUM(score), 0) AS total_score FROM tbl_user_scores WHERE user_id = :u');
        $totStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $totRes = $totStmt->execute();
        $currentTotal = $totRes instanceof SQLite3Result ? (int)($totRes->fetchArray(SQLITE3_ASSOC)['total_score'] ?? 0) : 0;
        if ($currentTotal > PHP_INT_MAX - $awardedDspoinc) {
            throw new Christmas26ClaimException(409, 'budget_exhausted');
        }

        // Component B: Optional Store Item evaluation (independent of DSPOINC).
        $bonusStatus = 'disabled';
        $bonusItemId = null;
        $bonusItemName = null;
        $bonusQty = 0;
        $inventoryId = null;
        $inventoryAfter = null;

        $bonus = $daySpec['bonus'];
        if ($bonus['enabled']) {
            $dwStmt = $db->prepare("SELECT COUNT(*) FROM tbl_christmas_advent_2026_claim_receipts WHERE day_number = :d AND bonus_status = 'delivered'");
            $dwStmt->bindValue(':d', $day, SQLITE3_INTEGER);
            $dailyWinners = (int)$dwStmt->execute()->fetchArray(SQLITE3_NUM)[0];

            $pwStmt = $db->prepare("SELECT COUNT(*) FROM tbl_christmas_advent_2026_claim_receipts WHERE user_id = :u AND bonus_status = 'delivered'");
            $pwStmt->bindValue(':u', $userId, SQLITE3_TEXT);
            $playerWins = (int)$pwStmt->execute()->fetchArray(SQLITE3_NUM)[0];

            if ($dailyWinners >= (int)$bonus['max_winners']) {
                $bonusStatus = 'daily_winner_cap';
            } elseif ($playerWins >= (int)$policy['max_bonus_wins_per_player']) {
                $bonusStatus = 'player_event_cap';
            } else {
                $roll = $draw(1, 10000);
                if (!is_int($roll) || $roll < 1 || $roll > 10000) {
                    throw new Christmas26ClaimException(500, 'invalid_draw_result');
                }
                if ($roll > (int)$bonus['chance_basis_points']) {
                    $bonusStatus = 'miss';
                } else {
                    // Roll hit! Check live catalog active status and per-user eligibility.
                    // Event allocation is governed by each day's bonus.max_winners cap (matching Reward Chamber / Store).
                    $eligibleItems = [];
                    foreach ($bonus['items'] as $candidate) {
                        $candId = (int)$candidate['item_id'];
                        $cls = christmas26_classify_store_item($db, $candId);
                        if (!$cls['auto_grantable']
                            || $cls['item_name'] !== $candidate['item_name']
                            || $cls['delivery_mode'] !== $candidate['delivery_mode']) {
                            continue;
                        }
                        if ($cls['delivery_mode'] === 'arcade_unlock_once' && christmas26_player_has_arcade_item($db, $userId, $candId)) {
                            continue;
                        }
                        $eligibleItems[] = $cls;
                    }

                    if ($eligibleItems === []) {
                        $bonusStatus = 'ineligible_item';
                    } else {
                        $pickIndex = count($eligibleItems) === 1 ? 0 : $draw(0, count($eligibleItems) - 1);
                        if (!is_int($pickIndex) || !isset($eligibleItems[$pickIndex])) {
                            throw new Christmas26ClaimException(500, 'invalid_draw_result');
                        }
                        $chosen = $eligibleItems[$pickIndex];
                        $bonusItemId = (int)$chosen['item_id'];
                        $bonusItemName = (string)$chosen['item_name'];
                        $bonusQty = 1;

                        // From here on, any inventory grant failure MUST rollback the entire claim.
                        $hook && $hook('during_inventory', $db);

                        $invSel = $db->prepare('SELECT inventory_id, quantity FROM tbl_user_inventory WHERE user_id = :u AND item_id = :id LIMIT 1');
                        $invSel->bindValue(':u', $userId, SQLITE3_TEXT);
                        $invSel->bindValue(':id', $bonusItemId, SQLITE3_INTEGER);
                        $invRow = $invSel->execute()->fetchArray(SQLITE3_ASSOC);
                        if (is_array($invRow)) {
                            $inventoryId = (int)($invRow['inventory_id'] ?? 0);
                            $beforeQty = $invRow['quantity'] ?? null;
                            if ($inventoryId < 1 || !is_int($beforeQty) || $beforeQty < 0 || $beforeQty > PHP_INT_MAX - 1) {
                                throw new RuntimeException('invalid_inventory_row');
                            }
                            $inventoryAfter = $beforeQty + 1;
                            $invUpd = $db->prepare(
                                'UPDATE tbl_user_inventory SET quantity = :after
                                 WHERE inventory_id = :inv AND user_id = :u AND item_id = :id AND quantity = :before'
                            );
                            $invUpd->bindValue(':after', $inventoryAfter, SQLITE3_INTEGER);
                            $invUpd->bindValue(':inv', $inventoryId, SQLITE3_INTEGER);
                            $invUpd->bindValue(':u', $userId, SQLITE3_TEXT);
                            $invUpd->bindValue(':id', $bonusItemId, SQLITE3_INTEGER);
                            $invUpd->bindValue(':before', $beforeQty, SQLITE3_INTEGER);
                            $invUpd->execute();
                            if ($db->changes() !== 1) {
                                throw new RuntimeException('inventory_update_failed');
                            }
                        } else {
                            $invIns = $db->prepare(
                                'INSERT INTO tbl_user_inventory (user_id, item_id, quantity, acquired_at)
                                 VALUES (:u, :id, 1, CURRENT_TIMESTAMP)'
                            );
                            $invIns->bindValue(':u', $userId, SQLITE3_TEXT);
                            $invIns->bindValue(':id', $bonusItemId, SQLITE3_INTEGER);
                            $invIns->execute();
                            if ($db->changes() !== 1) {
                                throw new RuntimeException('inventory_insert_failed');
                            }
                            $inventoryId = (int)$db->lastInsertRowID();
                            $inventoryAfter = 1;
                        }
                        $bonusStatus = 'delivered';
                    }
                }
            }
        }

        // Ledger entry.
        $scoreStmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, game, score, source) VALUES (:u, :g, :s, :g)');
        $scoreStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $scoreStmt->bindValue(':g', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
        $scoreStmt->bindValue(':s', $awardedDspoinc, SQLITE3_INTEGER);
        $scoreStmt->execute();
        $scoreId = (int)$db->lastInsertRowID();
        $hook && $hook('after_score', $db);

        // Audit entry.
        $policyReason = christmas26_v1_policy_reason((string)$policy['policy_version'], (string)$policy['policy_fingerprint']);
        $adjStmt = $db->prepare("INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (:u, :u, :a, 'add', :r)");
        $adjStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $adjStmt->bindValue(':a', $awardedDspoinc, SQLITE3_INTEGER);
        $adjStmt->bindValue(':r', $policyReason, SQLITE3_TEXT);
        $adjStmt->execute();
        $adjustmentId = (int)$db->lastInsertRowID();
        $hook && $hook('after_audit', $db);

        // Core marker row.
        $claimStmt = $db->prepare(
            'INSERT INTO tbl_christmas_advent_2026_claims
             (event_key, user_id, calendar_date, day_number, reward_dspoinc, actor_user_id, score_id, adjustment_id)
             VALUES (:e, :u, :dt, :d, :a, :u, :s, :i)'
        );
        $claimStmt->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
        $claimStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $claimStmt->bindValue(':dt', $date, SQLITE3_TEXT);
        $claimStmt->bindValue(':d', $day, SQLITE3_INTEGER);
        $claimStmt->bindValue(':a', $awardedDspoinc, SQLITE3_INTEGER);
        $claimStmt->bindValue(':s', $scoreId, SQLITE3_INTEGER);
        $claimStmt->bindValue(':i', $adjustmentId, SQLITE3_INTEGER);
        $claimStmt->execute();
        $claimId = (int)$db->lastInsertRowID();
        $hook && $hook('after_claim', $db);

        // Durable V1 claim receipt (DSPOINC bounds + bonus decision + inventory tracking).
        $recStmt = $db->prepare(
            'INSERT INTO tbl_christmas_advent_2026_claim_receipts
             (claim_id, user_id, day_number, calendar_date, policy_version, policy_fingerprint,
              dspoinc_min, dspoinc_max, awarded_dspoinc, bonus_status, bonus_item_id, bonus_item_name,
              bonus_quantity, inventory_id, inventory_quantity_after)
             VALUES
             (:cid, :u, :d, :dt, :pv, :pfp, :min, :max, :amt, :bst, :bid, :bnm, :bq, :inv, :inva)'
        );
        $recStmt->bindValue(':cid', $claimId, SQLITE3_INTEGER);
        $recStmt->bindValue(':u', $userId, SQLITE3_TEXT);
        $recStmt->bindValue(':d', $day, SQLITE3_INTEGER);
        $recStmt->bindValue(':dt', $date, SQLITE3_TEXT);
        $recStmt->bindValue(':pv', (string)$policy['policy_version'], SQLITE3_TEXT);
        $recStmt->bindValue(':pfp', (string)$policy['policy_fingerprint'], SQLITE3_TEXT);
        $recStmt->bindValue(':min', $min, SQLITE3_INTEGER);
        $recStmt->bindValue(':max', $max, SQLITE3_INTEGER);
        $recStmt->bindValue(':amt', $awardedDspoinc, SQLITE3_INTEGER);
        $recStmt->bindValue(':bst', $bonusStatus, SQLITE3_TEXT);
        $recStmt->bindValue(':bid', $bonusItemId, $bonusItemId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $recStmt->bindValue(':bnm', $bonusItemName, $bonusItemName === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $recStmt->bindValue(':bq', $bonusQty, SQLITE3_INTEGER);
        $recStmt->bindValue(':inv', $inventoryId, $inventoryId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $recStmt->bindValue(':inva', $inventoryAfter, $inventoryAfter === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $recStmt->execute();
        $hook && $hook('after_receipt', $db);

        // Queue public Discord #lootwinners announcement inside the same DB transaction.
        $outStmt = $db->prepare("INSERT INTO tbl_christmas_advent_2026_notify_outbox (claim_id, status, attempts) VALUES (:cid, 'pending', 0)");
        $outStmt->bindValue(':cid', $claimId, SQLITE3_INTEGER);
        $outStmt->execute();
        $hook && $hook('after_outbox', $db);

        if (!isset(christmas26_verified_claims($db, $userId)[$day])) {
            throw new Christmas26ClaimException(503, 'claim_integrity_failed');
        }
        $claimedAt = (string)$db->querySingle("SELECT claimed_at FROM tbl_christmas_advent_2026_claims WHERE claim_id = {$claimId}");
        $db->exec('COMMIT');
        $tx = false;

        return christmas26_format_receipt_v1([
            'claim_id' => $claimId,
            'day_number' => $day,
            'calendar_date' => $date,
            'claimed_at' => $claimedAt,
            'policy_version' => (string)$policy['policy_version'],
            'awarded_dspoinc' => $awardedDspoinc,
            'bonus_status' => $bonusStatus,
            'bonus_item_id' => $bonusItemId,
            'bonus_item_name' => $bonusItemName,
            'bonus_quantity' => $bonusQty,
        ], false);
    } catch (Throwable $e) {
        if ($tx) {
            try { $db->exec('ROLLBACK'); } catch (Throwable) {}
        }
        if ($e instanceof Christmas26ClaimException) {
            throw $e;
        }
        if (in_array($db->lastErrorCode() & 255, [5, 6], true)) {
            throw new Christmas26ClaimException(503, 'database_busy');
        }
        throw new Christmas26ClaimException(503, 'claim_unavailable');
    }
}
