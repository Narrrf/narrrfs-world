<?php
/**
 * Halloween 2026 Great Cheesy Pumpkin — single-user, one-time DSPOINC credit.
 *
 * DEVS FOR DECADES: the permanent claim marker is the exact matching
 * tbl_score_adjustments row. BEGIN IMMEDIATE serializes the marker check and
 * both ledger writes across requests sharing this SQLite database. Never
 * separate this marker from the credit or permit client-selected users/prizes.
 * This helper has no HTTP/session responsibilities so it can be tested against
 * disposable SQLite fixtures without touching a real player's economy.
 */

const HALLOWEEN26_EVENT_KEY = 'halloween_pumpkin_2026';
const HALLOWEEN26_GAME_KEY = 'halloween_pumpkin';
const HALLOWEEN26_REASON = 'Halloween 2026 - Great Cheesy Pumpkin (one-time gift)';
const HALLOWEEN26_START_UTC = '2026-10-28T00:00:00+00:00';
// Exclusive end: October 31, 2026 23:59:59 CET (Europe/Vienna) is included.
const HALLOWEEN26_END_EXCLUSIVE_UTC = '2026-10-31T23:00:00+00:00';

/** Return true only within the strictly server-enforced giveaway window. */
function halloween26_is_open(DateTimeImmutable $nowUtc): bool
{
    return $nowUtc >= new DateTimeImmutable(HALLOWEEN26_START_UTC)
        && $nowUtc < new DateTimeImmutable(HALLOWEEN26_END_EXCLUSIVE_UTC);
}

/**
 * Choose one reward using cryptographically secure randomness.
 * Prize weights are a PROPOSAL, not an approved payout schedule. Keep feature
 * flag OFF until owner specifically approves this payout distribution.
 * 35%, 25%, 17%, 12%, 7%, 3%, 1% across amounts 10k..500k.
 * Proposed expected cost per eligible claim: 58,500 DSPOINC.
 * These weights and amounts must receive explicit owner approval before activation.
 */
function halloween26_roll_amount(): int
{
    $prizesByWeight = [
        10000 => 35,
        20000 => 25,
        50000 => 17,
        100000 => 12,
        200000 => 7,
        350000 => 3,
        500000 => 1,
    ];
    $roll = random_int(1, 100);
    foreach ($prizesByWeight as $amount => $weight) {
        $roll -= $weight;
        if ($roll <= 0) {
            return (int)$amount;
        }
    }
    throw new RuntimeException('halloween26_invalid_prize_weights');
}

/**
 * Inspect both permanent records. Missing/mismatched records fail closed.
 * Returns an unclaimed state or a single matching claimed amount.
 */
function halloween26_get_claim_state(PDO $db, string $userId): array
{
    $audit = $db->prepare(
        'SELECT id, amount FROM tbl_score_adjustments
         WHERE user_id = ? AND admin_id = ? AND action = ? AND reason = ?
         ORDER BY id LIMIT 2'
    );
    $audit->execute([$userId, HALLOWEEN26_EVENT_KEY, 'add', HALLOWEEN26_REASON]);
    $auditRows = $audit->fetchAll(PDO::FETCH_ASSOC);

    $ledger = $db->prepare(
        'SELECT id, score FROM tbl_user_scores
         WHERE user_id = ? AND game = ? AND source = ?
         ORDER BY id LIMIT 2'
    );
    $ledger->execute([$userId, HALLOWEEN26_GAME_KEY, HALLOWEEN26_EVENT_KEY]);
    $ledgerRows = $ledger->fetchAll(PDO::FETCH_ASSOC);

    if (!$auditRows && !$ledgerRows) {
        return ['claimed' => false, 'amount' => null];
    }

    if (count($auditRows) !== 1 || count($ledgerRows) !== 1
        || (int)$auditRows[0]['amount'] !== (int)$ledgerRows[0]['score']
        || (int)$auditRows[0]['amount'] < 10000
        || (int)$auditRows[0]['amount'] > 500000) {
        throw new RuntimeException('halloween26_claim_record_integrity_failed');
    }

    return ['claimed' => true, 'amount' => (int)$auditRows[0]['amount']];
}

/**
 * Claim once for one already-authenticated server-selected Discord user.
 * $now and $chooseAmount are injectable ONLY by isolated test code; the HTTP
 * entrypoint never supplies a browser-selected time or reward amount.
 * Throws on any transaction or integrity failure, including an absent account.
 */
function halloween26_claim(PDO $db, string $userId, ?DateTimeImmutable $now = null, ?callable $chooseAmount = null): array
{
    $openTransaction = false;
    $db->exec('BEGIN IMMEDIATE');
    $openTransaction = true;

    try {
        // Re-read claim marker AFTER acquiring SQLite's exclusive writer slot.
        $state = halloween26_get_claim_state($db, $userId);
        if ($state['claimed']) {
            $db->exec('COMMIT');
            $openTransaction = false;
            return ['claimed' => true, 'already_claimed' => true, 'amount' => $state['amount']];
        }

        $serverNow = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!halloween26_is_open($serverNow)) {
            throw new DomainException('halloween26_event_closed');
        }

        // Server session identity must correspond to an existing game account.
        $account = $db->prepare('SELECT 1 FROM tbl_users WHERE discord_id = ? LIMIT 1');
        $account->execute([$userId]);
        if ($account->fetchColumn() === false) {
            throw new DomainException('halloween26_account_not_found');
        }

        $amount = (int)($chooseAmount ? $chooseAmount() : halloween26_roll_amount());
        if ($amount < 10000 || $amount > 500000) {
            throw new RuntimeException('halloween26_reward_out_of_range');
        }

        $credit = $db->prepare(
            'INSERT INTO tbl_user_scores (user_id, game, score, source, timestamp)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)'
        );
        $credit->execute([$userId, HALLOWEEN26_GAME_KEY, $amount, HALLOWEEN26_EVENT_KEY]);
        if ((int)$db->query('SELECT changes()')->fetchColumn() !== 1) {
            throw new RuntimeException('halloween26_ledger_write_count_invalid');
        }

        $audit = $db->prepare(
            'INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
             VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)'
        );
        $audit->execute([$userId, HALLOWEEN26_EVENT_KEY, $amount, 'add', HALLOWEEN26_REASON]);
        if ((int)$db->query('SELECT changes()')->fetchColumn() !== 1) {
            throw new RuntimeException('halloween26_audit_write_count_invalid');
        }

        $verified = halloween26_get_claim_state($db, $userId);
        if (!$verified['claimed'] || $verified['amount'] !== $amount) {
            throw new RuntimeException('halloween26_postwrite_integrity_failed');
        }

        $db->exec('COMMIT');
        $openTransaction = false;
        return ['claimed' => true, 'already_claimed' => false, 'amount' => $amount];
    } catch (Throwable $error) {
        if ($openTransaction) {
            try {
                $db->exec('ROLLBACK');
            } catch (Throwable $rollbackError) {
                error_log('Halloween26 rollback failed: ' . $rollbackError->getMessage());
            }
        }
        throw $error;
    }
}
