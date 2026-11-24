<?php
declare(strict_types=1);

/**
 * Guarded Cheese Hunt V1 state machine.
 * Discord role delivery is queued into tbl_owner_cheese_hunt_role_delivery as an
 * explicit outbox record rather than invoked synchronously over HTTP.
 */
final class CheeseHuntException extends RuntimeException
{
    public function __construct(public int $status, public string $publicCode)
    {
        parent::__construct($publicCode);
    }
}

/** Start an immediate write transaction with a bounded lock wait. */
function cheese_hunt_begin(SQLite3 $db): void
{
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    try {
        $db->exec('BEGIN IMMEDIATE');
    } catch (Throwable $e) {
        if (in_array($db->lastErrorCode(), [5, 6], true)) {
            throw new CheeseHuntException(503, 'database_busy');
        }
        throw $e;
    }
}

/** Roll back safely without masking the triggering domain exception. */
function cheese_hunt_rollback_quietly(SQLite3 $db, bool $open): void
{
    if (!$open) {
        return;
    }
    try {
        $db->exec('ROLLBACK');
    } catch (Throwable) {
        error_log('[OWNER CHEESE HUNT] rollback failed');
    }
}

/** Append one immutable audit row inside the caller's active transaction. */
function cheese_hunt_audit(
    SQLite3 $db,
    string $actor,
    string $event,
    ?int $missionId,
    ?int $claimId,
    string $requestHash,
    array $detail
): int {
    $stmt = $db->prepare(
        'INSERT INTO tbl_owner_cheese_hunt_audit
            (actor_subject, event_type, mission_id, claim_id, request_hash, detail)
         VALUES (:actor, :event, :mission_id, :claim_id, :request_hash, :detail)'
    );
    $stmt->bindValue(':actor', $actor, SQLITE3_TEXT);
    $stmt->bindValue(':event', $event, SQLITE3_TEXT);
    $stmt->bindValue(':mission_id', $missionId, $missionId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
    $stmt->bindValue(':claim_id', $claimId, $claimId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
    $stmt->bindValue(':request_hash', $requestHash, SQLITE3_TEXT);
    $stmt->bindValue(':detail', json_encode($detail, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), SQLITE3_TEXT);
    $stmt->execute();

    return (int)$db->lastInsertRowID();
}

/** Check an idempotency receipt and fail with 409 if the same key is reused for a different payload. */
function cheese_hunt_receipt(
    SQLite3 $db,
    string $actor,
    string $operation,
    string $key,
    string $requestHash
): ?array {
    $stmt = $db->prepare(
        'SELECT request_hash, audit_id
         FROM tbl_owner_cheese_hunt_receipts
         WHERE actor_subject = :actor
           AND operation = :operation
           AND idempotency_key_hash = :key_hash'
    );
    $stmt->bindValue(':actor', $actor, SQLITE3_TEXT);
    $stmt->bindValue(':operation', $operation, SQLITE3_TEXT);
    $stmt->bindValue(':key_hash', hash('sha256', $key), SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (is_array($row)) {
        if (!hash_equals((string)$row['request_hash'], $requestHash)) {
            throw new CheeseHuntException(409, 'idempotency_key_reused');
        }
        return $row;
    }

    return null;
}

/** Store one immutable idempotency receipt inside the active transaction. */
function cheese_hunt_store_receipt(
    SQLite3 $db,
    string $actor,
    string $operation,
    string $key,
    string $requestHash,
    int $auditId
): void {
    $stmt = $db->prepare(
        'INSERT INTO tbl_owner_cheese_hunt_receipts
            (actor_subject, operation, idempotency_key_hash, request_hash, audit_id)
         VALUES (:actor, :operation, :key_hash, :request_hash, :audit_id)'
    );
    $stmt->bindValue(':actor', $actor, SQLITE3_TEXT);
    $stmt->bindValue(':operation', $operation, SQLITE3_TEXT);
    $stmt->bindValue(':key_hash', hash('sha256', $key), SQLITE3_TEXT);
    $stmt->bindValue(':request_hash', $requestHash, SQLITE3_TEXT);
    $stmt->bindValue(':audit_id', $auditId, SQLITE3_INTEGER);
    $stmt->execute();
}

/** Validate a canonical UTC timestamp or null for optional mission expiration. */
function cheese_hunt_normalize_expires_at(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }
    if (!is_string($value)) {
        throw new CheeseHuntException(400, 'invalid_mission');
    }
    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $trimmed) !== 1) {
        throw new CheeseHuntException(400, 'invalid_mission');
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $trimmed, new DateTimeZone('UTC'));
    if (!$dt || $dt->format('Y-m-d H:i:s') !== $trimmed) {
        throw new CheeseHuntException(400, 'invalid_mission');
    }
    return $trimmed;
}

/** Execute owner-only mission lifecycle mutations (create, activate, deactivate). */
function cheese_hunt_mission(SQLite3 $db, string $actor, array $in): array
{
    if (!preg_match('/^\d{17,20}$/D', $actor)) {
        throw new CheeseHuntException(400, 'invalid_request');
    }

    $expectedKeys = ['action', 'idempotency_key', 'mission'];
    $actualKeys = array_keys($in);
    sort($actualKeys);
    if (
        $actualKeys !== $expectedKeys ||
        !is_string($in['action']) ||
        !in_array($in['action'], ['create', 'activate', 'deactivate'], true) ||
        !is_string($in['idempotency_key']) ||
        preg_match('/^[A-Za-z0-9_-]{16,128}$/D', $in['idempotency_key']) !== 1 ||
        !is_array($in['mission'])
    ) {
        throw new CheeseHuntException(400, 'invalid_request');
    }

    $action = $in['action'];
    $m = $in['mission'];
    $allowedPatterns = ['random', 'edge_hunter', 'sneaky', 'corner_lurker', 'scatter_zone', 'fair_shuffle'];
    $allowedSpeeds = ['slow', 'normal', 'fast'];

    $normalizedMission = [];
    if ($action === 'create') {
        $required = [
            'description',
            'reward_amount',
            'required_clicks',
            'movement_pattern',
            'movement_speed',
            'hidden_areas',
            'winner_message',
        ];
        $allowed = array_merge($required, ['role_id', 'expires_at']);
        if (array_diff(array_keys($m), $allowed) !== []) {
            throw new CheeseHuntException(400, 'invalid_mission');
        }
        foreach ($required as $key) {
            if (!array_key_exists($key, $m)) {
                throw new CheeseHuntException(400, 'invalid_mission');
            }
        }

        if (
            !is_string($m['description']) ||
            preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $m['description']) === 1 ||
            strlen(trim($m['description'])) < 3 ||
            strlen(trim($m['description'])) > 500 ||
            !is_int($m['reward_amount']) ||
            $m['reward_amount'] < 1 ||
            $m['reward_amount'] > 1000000000 ||
            !is_int($m['required_clicks']) ||
            $m['required_clicks'] < 1 ||
            $m['required_clicks'] > 100 ||
            !is_string($m['movement_pattern']) ||
            !in_array($m['movement_pattern'], $allowedPatterns, true) ||
            !is_string($m['movement_speed']) ||
            !in_array($m['movement_speed'], $allowedSpeeds, true) ||
            !is_bool($m['hidden_areas']) ||
            !is_string($m['winner_message']) ||
            preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $m['winner_message']) === 1 ||
            strlen(trim($m['winner_message'])) < 1 ||
            strlen(trim($m['winner_message'])) > 500
        ) {
            throw new CheeseHuntException(400, 'invalid_mission');
        }

        $roleId = $m['role_id'] ?? null;
        if ($roleId !== null) {
            if (!is_string($roleId)) {
                throw new CheeseHuntException(400, 'invalid_mission');
            }
            $roleId = trim($roleId);
            if ($roleId === '') {
                $roleId = null;
            } elseif (preg_match('/^\d{17,20}$/D', $roleId) !== 1) {
                throw new CheeseHuntException(400, 'invalid_mission');
            }
        }

        $expiresAt = cheese_hunt_normalize_expires_at($m['expires_at'] ?? null);

        $normalizedMission = [
            'description' => trim($m['description']),
            'reward_amount' => $m['reward_amount'],
            'required_clicks' => $m['required_clicks'],
            'movement_pattern' => $m['movement_pattern'],
            'movement_speed' => $m['movement_speed'],
            'hidden_areas' => $m['hidden_areas'] ? 1 : 0,
            'winner_message' => trim($m['winner_message']),
            'role_id' => $roleId,
            'expires_at' => $expiresAt,
        ];
    } else {
        if (array_keys($m) !== ['mission_id'] || !is_int($m['mission_id']) || $m['mission_id'] < 1) {
            throw new CheeseHuntException(400, 'invalid_mission');
        }
        $normalizedMission = ['mission_id' => $m['mission_id']];
    }

    $hash = hash('sha256', json_encode([$action, $normalizedMission], JSON_THROW_ON_ERROR));

    $open = false;
    cheese_hunt_begin($db);
    $open = true;
    try {
        $existing = cheese_hunt_receipt($db, $actor, 'mission', $in['idempotency_key'], $hash);
        if ($existing !== null) {
            $db->exec('COMMIT');
            $open = false;
            return [
                'replayed' => true,
                'audit_id' => (int)$existing['audit_id'],
            ];
        }

        if ($action === 'create') {
            $stmt = $db->prepare(
                'INSERT INTO tbl_owner_cheese_hunt_missions (
                    description, reward_amount, required_clicks, movement_pattern,
                    movement_speed, hidden_areas, winner_message, role_id, expires_at, created_by
                ) VALUES (
                    :description, :reward_amount, :required_clicks, :movement_pattern,
                    :movement_speed, :hidden_areas, :winner_message, :role_id, :expires_at, :created_by
                )'
            );
            $stmt->bindValue(':description', $normalizedMission['description'], SQLITE3_TEXT);
            $stmt->bindValue(':reward_amount', $normalizedMission['reward_amount'], SQLITE3_INTEGER);
            $stmt->bindValue(':required_clicks', $normalizedMission['required_clicks'], SQLITE3_INTEGER);
            $stmt->bindValue(':movement_pattern', $normalizedMission['movement_pattern'], SQLITE3_TEXT);
            $stmt->bindValue(':movement_speed', $normalizedMission['movement_speed'], SQLITE3_TEXT);
            $stmt->bindValue(':hidden_areas', $normalizedMission['hidden_areas'], SQLITE3_INTEGER);
            $stmt->bindValue(':winner_message', $normalizedMission['winner_message'], SQLITE3_TEXT);
            $stmt->bindValue(
                ':role_id',
                $normalizedMission['role_id'],
                $normalizedMission['role_id'] === null ? SQLITE3_NULL : SQLITE3_TEXT
            );
            $stmt->bindValue(
                ':expires_at',
                $normalizedMission['expires_at'],
                $normalizedMission['expires_at'] === null ? SQLITE3_NULL : SQLITE3_TEXT
            );
            $stmt->bindValue(':created_by', $actor, SQLITE3_TEXT);
            $stmt->execute();
            $id = (int)$db->lastInsertRowID();
        } else {
            $id = $normalizedMission['mission_id'];
            if ($action === 'activate') {
                $db->exec(
                    "UPDATE tbl_owner_cheese_hunt_missions
                     SET status = 'inactive', version = version + 1, updated_at = CURRENT_TIMESTAMP
                     WHERE status = 'active' AND mission_id != " . $id
                );
                $stmt = $db->prepare(
                    "UPDATE tbl_owner_cheese_hunt_missions
                     SET status = 'active', activated_at = CURRENT_TIMESTAMP, version = version + 1, updated_at = CURRENT_TIMESTAMP
                     WHERE mission_id = :id AND status IN ('draft', 'inactive')"
                );
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();
                if ($db->changes() !== 1) {
                    throw new CheeseHuntException(409, 'mission_not_activatable');
                }
            } else {
                $stmt = $db->prepare(
                    "UPDATE tbl_owner_cheese_hunt_missions
                     SET status = 'inactive', version = version + 1, updated_at = CURRENT_TIMESTAMP
                     WHERE mission_id = :id AND status = 'active'"
                );
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();
                if ($db->changes() !== 1) {
                    throw new CheeseHuntException(409, 'mission_not_active');
                }
            }
        }

        $auditId = cheese_hunt_audit($db, $actor, 'mission_' . $action, $id, null, $hash, $normalizedMission);
        cheese_hunt_store_receipt($db, $actor, 'mission', $in['idempotency_key'], $hash, $auditId);
        $db->exec('COMMIT');
        $open = false;

        return [
            'replayed' => false,
            'mission_id' => $id,
            'audit_id' => $auditId,
        ];
    } catch (Throwable $e) {
        cheese_hunt_rollback_quietly($db, $open);
        throw $e;
    }
}

/** Record one authenticated player cheese capture for the active mission. */
function cheese_hunt_capture(SQLite3 $db, string $player, array $in): array
{
    if (
        !preg_match('/^\d{17,20}$/D', $player) ||
        array_keys($in) !== ['idempotency_key'] ||
        !is_string($in['idempotency_key']) ||
        preg_match('/^[A-Za-z0-9_-]{16,128}$/D', $in['idempotency_key']) !== 1
    ) {
        throw new CheeseHuntException(400, 'invalid_request');
    }

    $hash = hash('sha256', 'capture|' . $player);
    $open = false;
    cheese_hunt_begin($db);
    $open = true;
    try {
        $existing = cheese_hunt_receipt($db, $player, 'capture', $in['idempotency_key'], $hash);
        if ($existing !== null) {
            $db->exec('COMMIT');
            $open = false;
            return ['replayed' => true];
        }

        $m = $db->querySingle(
            "SELECT *
             FROM tbl_owner_cheese_hunt_missions
             WHERE status = 'active'
               AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
             LIMIT 1",
            true
        );
        if (!is_array($m)) {
            throw new CheeseHuntException(409, 'no_active_mission');
        }

        $missionId = (int)$m['mission_id'];
        $requiredClicks = (int)$m['required_clicks'];

        $pStmt = $db->prepare(
            'SELECT click_count, last_capture_at, completed_at
             FROM tbl_owner_cheese_hunt_progress
             WHERE mission_id = :mission_id AND player_id = :player_id'
        );
        $pStmt->bindValue(':mission_id', $missionId, SQLITE3_INTEGER);
        $pStmt->bindValue(':player_id', $player, SQLITE3_TEXT);
        $p = $pStmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (is_array($p) && $p['completed_at'] !== null) {
            throw new CheeseHuntException(409, 'mission_already_completed');
        }

        $now = time();
        if (is_array($p) && $p['last_capture_at'] !== null && ($now - (int)$p['last_capture_at']) < 2) {
            throw new CheeseHuntException(429, 'capture_cooldown');
        }

        $count = (is_array($p) ? (int)$p['click_count'] : 0) + 1;
        $done = $count >= $requiredClicks;

        $upsert = $db->prepare(
            'INSERT INTO tbl_owner_cheese_hunt_progress (
                mission_id, player_id, click_count, last_capture_at, completed_at
            ) VALUES (
                :mission_id, :player_id, :click_count, :last_capture_at, :completed_at
            ) ON CONFLICT(mission_id, player_id) DO UPDATE SET
                click_count = excluded.click_count,
                last_capture_at = excluded.last_capture_at,
                completed_at = excluded.completed_at'
        );
        $upsert->bindValue(':mission_id', $missionId, SQLITE3_INTEGER);
        $upsert->bindValue(':player_id', $player, SQLITE3_TEXT);
        $upsert->bindValue(':click_count', $count, SQLITE3_INTEGER);
        $upsert->bindValue(':last_capture_at', $now, SQLITE3_INTEGER);
        $upsert->bindValue(':completed_at', $done ? gmdate('Y-m-d H:i:s', $now) : null, $done ? SQLITE3_TEXT : SQLITE3_NULL);
        $upsert->execute();

        $claimId = null;
        if ($done) {
            $claimStmt = $db->prepare(
                "INSERT INTO tbl_owner_cheese_hunt_claims (
                    mission_id, player_id, reward_amount, role_id, status
                ) VALUES (
                    :mission_id, :player_id, :reward_amount, :role_id, 'pending'
                )"
            );
            $claimStmt->bindValue(':mission_id', $missionId, SQLITE3_INTEGER);
            $claimStmt->bindValue(':player_id', $player, SQLITE3_TEXT);
            $claimStmt->bindValue(':reward_amount', (int)$m['reward_amount'], SQLITE3_INTEGER);
            $claimStmt->bindValue(
                ':role_id',
                $m['role_id'],
                $m['role_id'] === null ? SQLITE3_NULL : SQLITE3_TEXT
            );
            $claimStmt->execute();
            $claimId = (int)$db->lastInsertRowID();
        }

        $auditId = cheese_hunt_audit(
            $db,
            $player,
            'capture',
            $missionId,
            $claimId,
            $hash,
            ['click_count' => $count, 'required_clicks' => $requiredClicks, 'claim_pending' => $done]
        );
        cheese_hunt_store_receipt($db, $player, 'capture', $in['idempotency_key'], $hash, $auditId);
        $db->exec('COMMIT');
        $open = false;

        return [
            'replayed' => false,
            'mission_id' => $missionId,
            'click_count' => $count,
            'required_clicks' => $requiredClicks,
            'claim_pending' => $done,
            'claim_id' => $claimId,
            'reward_amount' => (int)$m['reward_amount'],
            'winner_message' => (string)$m['winner_message'],
        ];
    } catch (Throwable $e) {
        cheese_hunt_rollback_quietly($db, $open);
        throw $e;
    }
}

/** Approve or reject one pending Cheese Hunt claim atomically, crediting DSPOINC and queueing role outbox on approval. */
function cheese_hunt_review(SQLite3 $db, string $actor, array $in): array
{
    if (!preg_match('/^\d{17,20}$/D', $actor)) {
        throw new CheeseHuntException(400, 'invalid_request');
    }

    $expectedKeys = ['action', 'claim_id', 'idempotency_key', 'reason'];
    $actualKeys = array_keys($in);
    sort($actualKeys);
    if (
        $actualKeys !== $expectedKeys ||
        !is_int($in['claim_id']) ||
        $in['claim_id'] < 1 ||
        !is_string($in['action']) ||
        !in_array($in['action'], ['approve', 'reject'], true) ||
        !is_string($in['idempotency_key']) ||
        preg_match('/^[A-Za-z0-9_-]{16,128}$/D', $in['idempotency_key']) !== 1 ||
        !is_string($in['reason']) ||
        preg_match('/[\x00-\x1F\x7F]/', $in['reason']) === 1 ||
        strlen(trim($in['reason'])) < 3 ||
        strlen(trim($in['reason'])) > 256
    ) {
        throw new CheeseHuntException(400, 'invalid_request');
    }

    $reason = trim($in['reason']);
    $action = $in['action'];
    $claimId = $in['claim_id'];
    $hash = hash('sha256', json_encode([$claimId, $action, $reason], JSON_THROW_ON_ERROR));

    $open = false;
    cheese_hunt_begin($db);
    $open = true;
    try {
        $existing = cheese_hunt_receipt($db, $actor, 'review', $in['idempotency_key'], $hash);
        if ($existing !== null) {
            $db->exec('COMMIT');
            $open = false;
            return [
                'replayed' => true,
                'audit_id' => (int)$existing['audit_id'],
            ];
        }

        $cStmt = $db->prepare('SELECT * FROM tbl_owner_cheese_hunt_claims WHERE claim_id = :claim_id');
        $cStmt->bindValue(':claim_id', $claimId, SQLITE3_INTEGER);
        $c = $cStmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!is_array($c) || $c['status'] !== 'pending') {
            throw new CheeseHuntException(409, 'claim_not_pending');
        }

        $nextStatus = $action === 'approve' ? 'approved' : 'rejected';
        $creditedAt = $action === 'approve' ? gmdate('Y-m-d H:i:s') : null;

        $uStmt = $db->prepare(
            "UPDATE tbl_owner_cheese_hunt_claims
             SET status = :status,
                 reviewed_by = :actor,
                 review_reason = :reason,
                 reviewed_at = CURRENT_TIMESTAMP,
                 credited_at = :credited_at,
                 version = version + 1
             WHERE claim_id = :claim_id AND status = 'pending'"
        );
        $uStmt->bindValue(':status', $nextStatus, SQLITE3_TEXT);
        $uStmt->bindValue(':actor', $actor, SQLITE3_TEXT);
        $uStmt->bindValue(':reason', $reason, SQLITE3_TEXT);
        $uStmt->bindValue(':credited_at', $creditedAt, $creditedAt === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $uStmt->bindValue(':claim_id', $claimId, SQLITE3_INTEGER);
        $uStmt->execute();
        if ($db->changes() !== 1) {
            throw new CheeseHuntException(409, 'claim_not_pending');
        }

        $roleQueued = null;
        $creditedAmount = 0;
        if ($action === 'approve') {
            $creditedAmount = (int)$c['reward_amount'];
            $scoreStmt = $db->prepare(
                "INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp)
                 VALUES (:user_id, :score, 'cheese_hunt', 'owner_cheese_hunt', CURRENT_TIMESTAMP)"
            );
            $scoreStmt->bindValue(':user_id', (string)$c['player_id'], SQLITE3_TEXT);
            $scoreStmt->bindValue(':score', $creditedAmount, SQLITE3_INTEGER);
            $scoreStmt->execute();

            if ($c['role_id'] !== null && $c['role_id'] !== '') {
                $roleStmt = $db->prepare(
                    "INSERT INTO tbl_owner_cheese_hunt_role_delivery (claim_id, role_id, status)
                     VALUES (:claim_id, :role_id, 'pending')"
                );
                $roleStmt->bindValue(':claim_id', $claimId, SQLITE3_INTEGER);
                $roleStmt->bindValue(':role_id', (string)$c['role_id'], SQLITE3_TEXT);
                $roleStmt->execute();
                $roleQueued = 'pending';
            }
        }

        $auditId = cheese_hunt_audit(
            $db,
            $actor,
            'claim_' . $action,
            (int)$c['mission_id'],
            $claimId,
            $hash,
            ['reason' => $reason, 'credited_amount' => $creditedAmount, 'role_delivery' => $roleQueued]
        );
        cheese_hunt_store_receipt($db, $actor, 'review', $in['idempotency_key'], $hash, $auditId);
        $db->exec('COMMIT');
        $open = false;

        return [
            'replayed' => false,
            'claim_id' => $claimId,
            'status' => $nextStatus,
            'credited_amount' => $creditedAmount,
            'role_delivery' => $roleQueued,
            'audit_id' => $auditId,
        ];
    } catch (Throwable $e) {
        cheese_hunt_rollback_quietly($db, $open);
        throw $e;
    }
}
