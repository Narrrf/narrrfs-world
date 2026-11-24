<?php
/**
 * Christmas Advent 2026 — Narrow Discord #lootwinners Outbox Service V1.
 *
 * DEVS FOR DECADES:
 * This service leases committed claims, never touches balances or inventory,
 * never accepts raw SQL, and never exposes private financial data.
 */
declare(strict_types=1);

require_once __DIR__ . '/christmas-advent-2026.php';

final class Christmas26OutboxException extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $publicCode)
    {
        parent::__construct($publicCode);
    }
}

function christmas26_outbox_utc_now(?int $timestamp = null): string
{
    return gmdate('Y-m-d H:i:s', $timestamp ?? time());
}

function christmas26_outbox_assert_schema(SQLite3 $db): void
{
    foreach (['tbl_christmas_advent_2026_claims', 'tbl_christmas_advent_2026_claim_receipts', 'tbl_christmas_advent_2026_notify_outbox'] as $t) {
        $q = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:n LIMIT 1");
        $q->bindValue(':n', $t, SQLITE3_TEXT);
        $r = $q->execute();
        if (!$r instanceof SQLite3Result || $r->fetchArray(SQLITE3_NUM) === false) {
            throw new Christmas26OutboxException(503, 'outbox_schema_unavailable');
        }
    }
}

/** Lease up to $limit due pending committed claims for #lootwinners announcement. */
function christmas26_outbox_claim_batch(SQLite3 $db, int $limit = 10, ?int $nowTs = null): array
{
    $limit = max(1, min(10, $limit));
    $now = $nowTs ?? time();
    $nowSql = christmas26_outbox_utc_now($now);
    $leaseSql = christmas26_outbox_utc_now($now + 120);

    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $open = false;
    try {
        try {
            $db->exec('BEGIN IMMEDIATE');
            $open = true;
        } catch (Throwable $e) {
            if (in_array($db->lastErrorCode() & 255, [5, 6], true)) {
                throw new Christmas26OutboxException(503, 'database_busy');
            }
            throw $e;
        }

        christmas26_outbox_assert_schema($db);

        // Reclaim expired worker leases safely without losing duplicate awareness.
        $reclaim = $db->prepare(
            "UPDATE tbl_christmas_advent_2026_notify_outbox
             SET status = 'pending',
                 reclaimed_after_lease = 1,
                 claim_token = NULL,
                 lease_expires_at = NULL,
                 updated_at = :now
             WHERE status = 'processing'
               AND lease_expires_at IS NOT NULL
               AND lease_expires_at <= :now"
        );
        $reclaim->bindValue(':now', $nowSql, SQLITE3_TEXT);
        $reclaim->execute();

        $sel = $db->prepare(
            "SELECT o.claim_id, o.attempts, o.reclaimed_after_lease,
                    c.user_id, c.day_number, c.calendar_date, c.claimed_at,
                    r.awarded_dspoinc, r.bonus_status, r.bonus_item_name, r.bonus_quantity
             FROM tbl_christmas_advent_2026_notify_outbox o
             INNER JOIN tbl_christmas_advent_2026_claims c ON c.claim_id = o.claim_id
             INNER JOIN tbl_christmas_advent_2026_claim_receipts r ON r.claim_id = o.claim_id
             INNER JOIN tbl_user_scores s ON s.id = c.score_id
             INNER JOIN tbl_score_adjustments a ON a.id = c.adjustment_id
             WHERE o.status = 'pending'
               AND (o.next_attempt_at IS NULL OR o.next_attempt_at <= :now)
               AND c.event_key = :e
               AND c.reward_dspoinc = r.awarded_dspoinc
               AND s.user_id = c.user_id AND s.game = :e AND s.source = :e AND s.score = r.awarded_dspoinc
               AND a.user_id = c.user_id AND a.amount = r.awarded_dspoinc AND a.action = 'add'
             ORDER BY o.claim_id ASC
             LIMIT :lim"
        );
        $sel->bindValue(':now', $nowSql, SQLITE3_TEXT);
        $sel->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
        $sel->bindValue(':lim', $limit, SQLITE3_INTEGER);
        $res = $sel->execute();

        $items = [];
        while ($res instanceof SQLite3Result && ($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
            $token = bin2hex(random_bytes(24));
            $upd = $db->prepare(
                "UPDATE tbl_christmas_advent_2026_notify_outbox
                 SET status = 'processing',
                     claim_token = :tok,
                     lease_expires_at = :lease,
                     updated_at = :now
                 WHERE claim_id = :cid AND status = 'pending'"
            );
            $upd->bindValue(':tok', $token, SQLITE3_TEXT);
            $upd->bindValue(':lease', $leaseSql, SQLITE3_TEXT);
            $upd->bindValue(':now', $nowSql, SQLITE3_TEXT);
            $upd->bindValue(':cid', (int)$row['claim_id'], SQLITE3_INTEGER);
            $upd->execute();
            if ($db->changes() !== 1) {
                continue;
            }

            $bonusDelivered = ($row['bonus_status'] ?? '') === 'delivered';
            $items[] = [
                'claim_id' => (int)$row['claim_id'],
                'claim_token' => $token,
                'reclaimed_after_lease' => (int)$row['reclaimed_after_lease'] === 1,
                'attempts' => (int)$row['attempts'],
                'user_id' => (string)$row['user_id'],
                'day_number' => (int)$row['day_number'],
                'calendar_date' => (string)$row['calendar_date'],
                'claimed_at' => (string)$row['claimed_at'],
                'awarded_dspoinc' => (int)$row['awarded_dspoinc'],
                'bonus_awarded' => $bonusDelivered,
                'bonus_item_name' => $bonusDelivered ? (string)$row['bonus_item_name'] : null,
                'bonus_quantity' => $bonusDelivered ? (int)$row['bonus_quantity'] : 0,
            ];
        }

        $db->exec('COMMIT');
        $open = false;
        return ['items' => $items, 'server_utc' => $nowSql];
    } catch (Throwable $e) {
        if ($open) {
            try { $db->exec('ROLLBACK'); } catch (Throwable) {}
        }
        if ($e instanceof Christmas26OutboxException) {
            throw $e;
        }
        throw new Christmas26OutboxException(503, 'outbox_unavailable');
    }
}

/** Confirm Discord message send and record notification audit row safely. */
function christmas26_outbox_mark_sent(
    SQLite3 $db,
    int $claimId,
    string $claimToken,
    string $channelId,
    string $messageId
): array {
    if ($claimId < 1
        || preg_match('/^[a-f0-9]{48}$/D', $claimToken) !== 1
        || preg_match('/^\d{17,20}$/D', $channelId) !== 1
        || preg_match('/^\d{17,20}$/D', $messageId) !== 1) {
        throw new Christmas26OutboxException(400, 'invalid_request');
    }

    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $open = false;
    try {
        $db->exec('BEGIN IMMEDIATE');
        $open = true;
        christmas26_outbox_assert_schema($db);

        $chk = $db->prepare('SELECT status, claim_token, discord_message_id FROM tbl_christmas_advent_2026_notify_outbox WHERE claim_id = :cid LIMIT 1');
        $chk->bindValue(':cid', $claimId, SQLITE3_INTEGER);
        $row = $chk->execute()->fetchArray(SQLITE3_ASSOC);
        if (!is_array($row)) {
            throw new Christmas26OutboxException(404, 'outbox_item_not_found');
        }
        if ($row['status'] === 'sent' && (string)$row['discord_message_id'] === $messageId) {
            $db->exec('COMMIT');
            $open = false;
            return ['claim_id' => $claimId, 'status' => 'sent', 'already_sent' => true];
        }
        if ($row['status'] !== 'processing' || !is_string($row['claim_token'] ?? null) || !hash_equals($row['claim_token'], $claimToken)) {
            throw new Christmas26OutboxException(409, 'outbox_lease_mismatch');
        }

        $nowSql = christmas26_outbox_utc_now();
        $upd = $db->prepare(
            "UPDATE tbl_christmas_advent_2026_notify_outbox
             SET status = 'sent',
                 claim_token = NULL,
                 lease_expires_at = NULL,
                 discord_channel_id = :ch,
                 discord_message_id = :msg,
                 last_error = NULL,
                 sent_at = COALESCE(sent_at, :now),
                 updated_at = :now
             WHERE claim_id = :cid AND status = 'processing' AND claim_token = :tok"
        );
        $upd->bindValue(':ch', $channelId, SQLITE3_TEXT);
        $upd->bindValue(':msg', $messageId, SQLITE3_TEXT);
        $upd->bindValue(':now', $nowSql, SQLITE3_TEXT);
        $upd->bindValue(':cid', $claimId, SQLITE3_INTEGER);
        $upd->bindValue(':tok', $claimToken, SQLITE3_TEXT);
        $upd->execute();
        if ($db->changes() !== 1) {
            throw new Christmas26OutboxException(409, 'outbox_lease_mismatch');
        }

        $hasNotif = (int)$db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_discord_claim_notifications'") === 1;
        if ($hasNotif) {
            $nStmt = $db->prepare(
                "INSERT OR IGNORE INTO tbl_discord_claim_notifications
                 (claim_id, notification_type, discord_channel_id, discord_message_id)
                 VALUES (:cid, 'christmas_advent_2026_lootwinners', :ch, :msg)"
            );
            $nStmt->bindValue(':cid', $claimId, SQLITE3_INTEGER);
            $nStmt->bindValue(':ch', $channelId, SQLITE3_TEXT);
            $nStmt->bindValue(':msg', $messageId, SQLITE3_TEXT);
            $nStmt->execute();
        }

        $db->exec('COMMIT');
        $open = false;
        return ['claim_id' => $claimId, 'status' => 'sent', 'already_sent' => false];
    } catch (Throwable $e) {
        if ($open) {
            try { $db->exec('ROLLBACK'); } catch (Throwable) {}
        }
        if ($e instanceof Christmas26OutboxException) {
            throw $e;
        }
        throw new Christmas26OutboxException(503, 'outbox_unavailable');
    }
}

/** Schedule retry with exponential/explicit backoff without disturbing settlement. */
function christmas26_outbox_mark_retry(
    SQLite3 $db,
    int $claimId,
    string $claimToken,
    string $errorCode,
    int $retryAfterSeconds = 60,
    ?int $nowTs = null
): array {
    if ($claimId < 1 || preg_match('/^[a-f0-9]{48}$/D', $claimToken) !== 1) {
        throw new Christmas26OutboxException(400, 'invalid_request');
    }
    $cleanError = substr(preg_replace('/[\x00-\x1F\x7F]/', ' ', trim($errorCode)) ?: 'discord_send_failed', 0, 120);
    $delay = max(15, min(3600, $retryAfterSeconds));
    $now = $nowTs ?? time();
    $nowSql = christmas26_outbox_utc_now($now);
    $nextSql = christmas26_outbox_utc_now($now + $delay);

    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $open = false;
    try {
        $db->exec('BEGIN IMMEDIATE');
        $open = true;
        christmas26_outbox_assert_schema($db);

        $chk = $db->prepare('SELECT status, claim_token, attempts FROM tbl_christmas_advent_2026_notify_outbox WHERE claim_id = :cid LIMIT 1');
        $chk->bindValue(':cid', $claimId, SQLITE3_INTEGER);
        $row = $chk->execute()->fetchArray(SQLITE3_ASSOC);
        if (!is_array($row)) {
            throw new Christmas26OutboxException(404, 'outbox_item_not_found');
        }
        if ($row['status'] !== 'processing' || !is_string($row['claim_token'] ?? null) || !hash_equals($row['claim_token'], $claimToken)) {
            throw new Christmas26OutboxException(409, 'outbox_lease_mismatch');
        }

        $nextAttempts = (int)$row['attempts'] + 1;
        $nextStatus = $nextAttempts >= 20 ? 'failed' : 'pending';
        $upd = $db->prepare(
            "UPDATE tbl_christmas_advent_2026_notify_outbox
             SET status = :st,
                 attempts = :att,
                 claim_token = NULL,
                 lease_expires_at = NULL,
                 last_error = :err,
                 next_attempt_at = :nxt,
                 updated_at = :now
             WHERE claim_id = :cid AND status = 'processing' AND claim_token = :tok"
        );
        $upd->bindValue(':st', $nextStatus, SQLITE3_TEXT);
        $upd->bindValue(':att', $nextAttempts, SQLITE3_INTEGER);
        $upd->bindValue(':err', $cleanError, SQLITE3_TEXT);
        $upd->bindValue(':nxt', $nextSql, SQLITE3_TEXT);
        $upd->bindValue(':now', $nowSql, SQLITE3_TEXT);
        $upd->bindValue(':cid', $claimId, SQLITE3_INTEGER);
        $upd->bindValue(':tok', $claimToken, SQLITE3_TEXT);
        $upd->execute();

        $db->exec('COMMIT');
        $open = false;
        return ['claim_id' => $claimId, 'status' => $nextStatus, 'attempts' => $nextAttempts, 'next_attempt_at' => $nextSql];
    } catch (Throwable $e) {
        if ($open) {
            try { $db->exec('ROLLBACK'); } catch (Throwable) {}
        }
        if ($e instanceof Christmas26OutboxException) {
            throw $e;
        }
        throw new Christmas26OutboxException(503, 'outbox_unavailable');
    }
}
