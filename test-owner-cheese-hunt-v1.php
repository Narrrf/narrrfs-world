<?php
declare(strict_types=1);

require_once __DIR__ . '/api/lib/owner-cheese-hunt-v1.php';

function ch_assert(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

function ch_fixture(): SQLite3
{
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    $db->exec("
        CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT);
        CREATE TABLE tbl_user_scores (user_id TEXT, score INTEGER, game TEXT, source TEXT, timestamp TEXT);
        INSERT INTO tbl_users VALUES ('111111111111111111', 'playerone'), ('222222222222222222', 'playertwo'), ('999999999999999999', 'owner');
    ");
    $db->exec((string)file_get_contents(__DIR__ . '/db/migrations/20261124_owner_control_cheese_hunt_v1.sql'));
    return $db;
}

$owner = '999999999999999999';
$p1 = '111111111111111111';
$p2 = '222222222222222222';
$db = ch_fixture();

// 1. Create mission draft & replay idempotency
$m1Payload = [
    'action' => 'create',
    'idempotency_key' => 'mission_create_key_0001',
    'mission' => [
        'description' => 'Catch 2 sneaky cheeses around Narrrfs World!',
        'reward_amount' => 500,
        'required_clicks' => 2,
        'movement_pattern' => 'scatter_zone',
        'movement_speed' => 'fast',
        'hidden_areas' => true,
        'winner_message' => 'You caught both cheeses!',
        'role_id' => '1399651053682692208',
        'expires_at' => null,
    ],
];
$r1 = cheese_hunt_mission($db, $owner, $m1Payload);
ch_assert(!$r1['replayed'] && $r1['mission_id'] === 1, 'create mission 1');
ch_assert(cheese_hunt_mission($db, $owner, $m1Payload)['replayed'] === true, 'create replay');

try {
    $changed = $m1Payload;
    $changed['mission']['reward_amount'] = 501;
    cheese_hunt_mission($db, $owner, $changed);
    throw new RuntimeException('changed mission replay accepted');
} catch (CheeseHuntException $e) {
    ch_assert($e->status === 409 && $e->publicCode === 'idempotency_key_reused', 'changed mission idempotency');
}

// 2. Activate mission 1, create & activate mission 2 -> single active invariant, deactivate, reactivate 1
cheese_hunt_mission($db, $owner, [
    'action' => 'activate',
    'idempotency_key' => 'mission_activate_key_0001',
    'mission' => ['mission_id' => 1],
]);
ch_assert($db->querySingle("SELECT status FROM tbl_owner_cheese_hunt_missions WHERE mission_id=1") === 'active', 'm1 active');

$m2Payload = $m1Payload;
$m2Payload['idempotency_key'] = 'mission_create_key_0002';
$m2Payload['mission']['description'] = 'Second fair shuffle mission';
$m2Payload['mission']['role_id'] = null;
$r2 = cheese_hunt_mission($db, $owner, $m2Payload);
ch_assert($r2['mission_id'] === 2, 'create mission 2');

cheese_hunt_mission($db, $owner, [
    'action' => 'activate',
    'idempotency_key' => 'mission_activate_key_0002',
    'mission' => ['mission_id' => 2],
]);
ch_assert($db->querySingle("SELECT status FROM tbl_owner_cheese_hunt_missions WHERE mission_id=1") === 'inactive', 'm1 auto deactivated');
ch_assert($db->querySingle("SELECT status FROM tbl_owner_cheese_hunt_missions WHERE mission_id=2") === 'active', 'm2 active');

cheese_hunt_mission($db, $owner, [
    'action' => 'deactivate',
    'idempotency_key' => 'mission_deactivate_key_002',
    'mission' => ['mission_id' => 2],
]);
ch_assert($db->querySingle("SELECT COUNT(*) FROM tbl_owner_cheese_hunt_missions WHERE status='active'") === 0, 'zero active after deactivate');

cheese_hunt_mission($db, $owner, [
    'action' => 'activate',
    'idempotency_key' => 'mission_activate_key_0003',
    'mission' => ['mission_id' => 1],
]);

// 3. Player capture, cooldown, completion & replay
$c1 = cheese_hunt_capture($db, $p1, ['idempotency_key' => 'capture_p1_key_00001']);
ch_assert(!$c1['replayed'] && $c1['click_count'] === 1 && $c1['claim_pending'] === false, 'capture 1');
ch_assert(cheese_hunt_capture($db, $p1, ['idempotency_key' => 'capture_p1_key_00001'])['replayed'] === true, 'capture replay');

try {
    cheese_hunt_capture($db, $p1, ['idempotency_key' => 'capture_p1_key_00002']);
    throw new RuntimeException('cooldown bypassed');
} catch (CheeseHuntException $e) {
    ch_assert($e->status === 429 && $e->publicCode === 'capture_cooldown', 'capture cooldown');
}

$db->exec("UPDATE tbl_owner_cheese_hunt_progress SET last_capture_at = last_capture_at - 5 WHERE player_id = '$p1'");
$c2 = cheese_hunt_capture($db, $p1, ['idempotency_key' => 'capture_p1_key_00003']);
ch_assert(!$c2['replayed'] && $c2['click_count'] === 2 && $c2['claim_pending'] === true && $c2['claim_id'] === 1, 'capture 2 completion');

try {
    cheese_hunt_capture($db, $p1, ['idempotency_key' => 'capture_p1_key_00004']);
    throw new RuntimeException('post completion capture accepted');
} catch (CheeseHuntException $e) {
    ch_assert($e->status === 409 && $e->publicCode === 'mission_already_completed', 'completed capture denied');
}

// Player 2 completes as well so we can test reject
cheese_hunt_capture($db, $p2, ['idempotency_key' => 'capture_p2_key_00001']);
$db->exec("UPDATE tbl_owner_cheese_hunt_progress SET last_capture_at = last_capture_at - 5 WHERE player_id = '$p2'");
$p2Done = cheese_hunt_capture($db, $p2, ['idempotency_key' => 'capture_p2_key_00002']);
ch_assert($p2Done['claim_id'] === 2, 'p2 claim created');

// 4. Approve claim 1 (DSPOINC credit + role outbox) & idempotency
$rev1 = [
    'claim_id' => 1,
    'action' => 'approve',
    'reason' => 'Verified gameplay catch',
    'idempotency_key' => 'review_claim1_key_0001',
];
$approved = cheese_hunt_review($db, $owner, $rev1);
ch_assert(!$approved['replayed'] && $approved['status'] === 'approved' && $approved['credited_amount'] === 500 && $approved['role_delivery'] === 'pending', 'approve claim 1');
ch_assert((int)$db->querySingle("SELECT COALESCE(SUM(score),0) FROM tbl_user_scores WHERE user_id='$p1' AND source='owner_cheese_hunt'") === 500, 'ledger credited once');
ch_assert($db->querySingle("SELECT status FROM tbl_owner_cheese_hunt_role_delivery WHERE claim_id=1") === 'pending', 'role outbox queued');
ch_assert(cheese_hunt_review($db, $owner, $rev1)['replayed'] === true, 'review replay');
ch_assert((int)$db->querySingle("SELECT COALESCE(SUM(score),0) FROM tbl_user_scores WHERE user_id='$p1'") === 500, 'replay did not double credit');

try {
    $rev1Again = $rev1;
    $rev1Again['idempotency_key'] = 'review_claim1_key_0002';
    cheese_hunt_review($db, $owner, $rev1Again);
    throw new RuntimeException('double review accepted');
} catch (CheeseHuntException $e) {
    ch_assert($e->status === 409 && $e->publicCode === 'claim_not_pending', 'non-pending claim denied');
}

// 5. Reject claim 2 (zero credit, zero outbox)
$rejected = cheese_hunt_review($db, $owner, [
    'claim_id' => 2,
    'action' => 'reject',
    'reason' => 'Test rejection case',
    'idempotency_key' => 'review_claim2_key_0001',
]);
ch_assert($rejected['status'] === 'rejected' && $rejected['credited_amount'] === 0 && $rejected['role_delivery'] === null, 'reject claim 2');
ch_assert((int)$db->querySingle("SELECT COUNT(*) FROM tbl_user_scores WHERE user_id='$p2'") === 0, 'no score on reject');
ch_assert((int)$db->querySingle("SELECT COUNT(*) FROM tbl_owner_cheese_hunt_role_delivery WHERE claim_id=2") === 0, 'no outbox on reject');

// 6. Immutable audit & receipt triggers
foreach (['UPDATE tbl_owner_cheese_hunt_audit SET detail="{}"', 'DELETE FROM tbl_owner_cheese_hunt_receipts'] as $sql) {
    try {
        $db->exec($sql);
        throw new RuntimeException('immutable trigger bypassed: ' . $sql);
    } catch (Throwable $e) {
        if (str_starts_with($e->getMessage(), 'immutable trigger bypassed')) {
            throw $e;
        }
        // Expected abort
    }
}
$db->close();

// 7. Atomicity & rollback when audit, receipt, or role_delivery fails during claim approval
foreach (['tbl_owner_cheese_hunt_audit', 'tbl_owner_cheese_hunt_receipts', 'tbl_owner_cheese_hunt_role_delivery'] as $failTable) {
    $rbDb = ch_fixture();
    cheese_hunt_mission($rbDb, $owner, $m1Payload);
    cheese_hunt_mission($rbDb, $owner, ['action' => 'activate', 'idempotency_key' => 'rb_act_000000000001', 'mission' => ['mission_id' => 1]]);
    cheese_hunt_capture($rbDb, $p1, ['idempotency_key' => 'rb_cap1_000000000001']);
    $rbDb->exec("UPDATE tbl_owner_cheese_hunt_progress SET last_capture_at = last_capture_at - 5");
    cheese_hunt_capture($rbDb, $p1, ['idempotency_key' => 'rb_cap2_000000000001']);
    $rbDb->exec("CREATE TRIGGER fail_tx BEFORE INSERT ON {$failTable} BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
    try {
        cheese_hunt_review($rbDb, $owner, $rev1);
        throw new RuntimeException('failed tx accepted on ' . $failTable);
    } catch (Throwable $e) {
        if (str_starts_with($e->getMessage(), 'failed tx accepted')) {
            throw $e;
        }
        // Expected abort
    }
    ch_assert($rbDb->querySingle('SELECT status FROM tbl_owner_cheese_hunt_claims WHERE claim_id=1') === 'pending', 'claim rolled back ' . $failTable);
    ch_assert((int)$rbDb->querySingle('SELECT COUNT(*) FROM tbl_user_scores') === 0, 'score rolled back ' . $failTable);
    ch_assert((int)$rbDb->querySingle('SELECT COUNT(*) FROM tbl_owner_cheese_hunt_role_delivery') === 0, 'role delivery rolled back ' . $failTable);
    $rbDb->close();
}

echo "PASS: Cheese Hunt state machine, cooldown, single-active invariant, outbox, replay, immutability, and rollback\n";
