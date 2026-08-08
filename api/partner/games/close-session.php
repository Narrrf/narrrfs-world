<?php
/**
 * Partner Game Bridge V1 — Close Session
 *
 * Plain language for DEVS FOR DECADES:
 * The partner iframe/game calls this endpoint after a run ends.
 * It validates the short-lived public session token, closes the session once,
 * stores an isolated partner result, and returns a webhook-preview payload.
 *
 * This endpoint does not:
 * - write Narrrfs DSPOINC;
 * - write SPOINC;
 * - write tbl_user_scores;
 * - write tbl_tetris_scores;
 * - update public Narrrfs leaderboards;
 * - move rewards;
 * - change Genesis ownership;
 * - change Lab progression;
 * - change Genetic Items;
 * - touch MouseFight economy, escrow, recovery, burns, refunds, or settlement.
 */

require_once __DIR__ . '/_partner-helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    partnerBridgeRespond(200, [
        'success' => true,
        'message' => 'Partner bridge preflight ok'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    partnerBridgeRespond(405, [
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
}

try {
    $body = partnerBridgeReadJsonBody();

    $sessionToken = trim((string)($body['session_token'] ?? ''));
    $game = strtolower(trim((string)($body['game'] ?? '')));
    $score = (int)($body['score'] ?? 0);
    $clientRunId = trim((string)($body['client_run_id'] ?? ''));

    if ($sessionToken === '') {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Missing session_token'
        ]);
    }

    if ($game === '') {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Missing game'
        ]);
    }

    if ($score < 0 || $score > 10000000) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Invalid score'
        ]);
    }

    if (!preg_match('/^[A-Za-z0-9._:@-]{1,120}$/', $clientRunId)) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Invalid client_run_id'
        ]);
    }

    $db = partnerBridgeGetDb();
    $sessionTokenHash = partnerBridgeHashToken($sessionToken);

    $db->beginTransaction();

    $sessionStmt = $db->prepare("
        SELECT
            session_id,
            partner_id,
            external_user_id,
            game,
            discord_name,
            wallet_address,
            metadata_json,
            status,
            created_at,
            expires_at,
            closed_at
        FROM tbl_partner_game_sessions
        WHERE session_token_hash = :session_token_hash
        LIMIT 1
    ");
    $sessionStmt->execute([
        ':session_token_hash' => $sessionTokenHash
    ]);

    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        $db->rollBack();

        partnerBridgeRespond(404, [
            'success' => false,
            'error' => 'Session not found'
        ]);
    }

    $partnerConfig = partnerBridgeGetProjectConfig((string)$session['partner_id']);

    if (!$partnerConfig) {
        $db->rollBack();

        partnerBridgeRespond(403, [
            'success' => false,
            'error' => 'Unknown partner for session'
        ]);
    }

    if ((string)$session['game'] !== $game) {
        $db->rollBack();

        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Game does not match session'
        ]);
    }

    if (!partnerBridgeValidateGame($game, $partnerConfig)) {
        $db->rollBack();

        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Unsupported game for partner'
        ]);
    }

    if ((string)$session['status'] !== 'open') {
        $existingResultStmt = $db->prepare("
            SELECT
                result_id,
                raw_score,
                partner_points,
                idempotency_key,
                created_at
            FROM tbl_partner_game_results
            WHERE session_id = :session_id
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $existingResultStmt->execute([
            ':session_id' => $session['session_id']
        ]);
        $existingResult = $existingResultStmt->fetch(PDO::FETCH_ASSOC);

        $db->rollBack();

        partnerBridgeRespond(409, [
            'success' => false,
            'error' => 'Session already closed',
            'session_id' => $session['session_id'],
            'existing_result' => $existingResult ?: null
        ]);
    }

    $expiresAtUnix = strtotime((string)$session['expires_at'] . ' UTC');
    if (!$expiresAtUnix || $expiresAtUnix < time()) {
        $expireStmt = $db->prepare("
            UPDATE tbl_partner_game_sessions
            SET status = 'expired'
            WHERE session_id = :session_id
              AND status = 'open'
        ");
        $expireStmt->execute([
            ':session_id' => $session['session_id']
        ]);

        $db->commit();

        partnerBridgeRespond(410, [
            'success' => false,
            'error' => 'Session expired',
            'session_id' => $session['session_id']
        ]);
    }

    $idempotencyKey = $session['partner_id'] . ':' . $session['session_id'] . ':' . $clientRunId;

    $duplicateStmt = $db->prepare("
        SELECT
            result_id,
            raw_score,
            partner_points,
            idempotency_key,
            created_at
        FROM tbl_partner_game_results
        WHERE idempotency_key = :idempotency_key
        LIMIT 1
    ");
    $duplicateStmt->execute([
        ':idempotency_key' => $idempotencyKey
    ]);
    $duplicateResult = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

    if ($duplicateResult) {
        $db->rollBack();

        partnerBridgeRespond(409, [
            'success' => false,
            'error' => 'Duplicate run',
            'existing_result' => $duplicateResult
        ]);
    }

    $partnerPoints = partnerBridgeCalculatePartnerPoints($score, $partnerConfig);
    $partnerIdentity = partnerBridgeBuildPartnerIdentity($session);
    $resultId = partnerBridgeCreateToken('pgr_', 12);
    $closedAt = gmdate('Y-m-d H:i:s');

    $webhookPreview = [
        'event' => 'narrrfs.game.session_closed',
        'mode' => 'partner_bridge_v1',
        'partner_id' => $session['partner_id'],
        'session_id' => $session['session_id'],
        'external_user_id' => $session['external_user_id'],
        'partner_identity' => $partnerIdentity,
        'game' => $game,
        'score' => $score,
        'partner_points' => $partnerPoints,
        'client_run_id' => $clientRunId,
        'idempotency_key' => $idempotencyKey,
        'completed_at' => $closedAt . ' UTC'
    ];

    $insertResultStmt = $db->prepare("
        INSERT INTO tbl_partner_game_results
            (
                result_id,
                session_id,
                partner_id,
                external_user_id,
                game,
                raw_score,
                partner_points,
                client_run_id,
                idempotency_key,
                validation_status,
                webhook_preview_json,
                created_at
            )
        VALUES
            (
                :result_id,
                :session_id,
                :partner_id,
                :external_user_id,
                :game,
                :raw_score,
                :partner_points,
                :client_run_id,
                :idempotency_key,
                'accepted',
                :webhook_preview_json,
                :created_at
            )
    ");

    $insertResultStmt->execute([
        ':result_id' => $resultId,
        ':session_id' => $session['session_id'],
        ':partner_id' => $session['partner_id'],
        ':external_user_id' => $session['external_user_id'],
        ':game' => $game,
        ':raw_score' => $score,
        ':partner_points' => $partnerPoints,
        ':client_run_id' => $clientRunId,
        ':idempotency_key' => $idempotencyKey,
        ':webhook_preview_json' => json_encode($webhookPreview, JSON_UNESCAPED_SLASHES),
        ':created_at' => $closedAt
    ]);

    $closeSessionStmt = $db->prepare("
        UPDATE tbl_partner_game_sessions
        SET
            status = 'closed',
            closed_at = :closed_at
        WHERE session_id = :session_id
          AND status = 'open'
    ");

    $closeSessionStmt->execute([
        ':closed_at' => $closedAt,
        ':session_id' => $session['session_id']
    ]);

    if ($closeSessionStmt->rowCount() !== 1) {
        throw new Exception('Session close update failed.');
    }

    $db->commit();

    partnerBridgeRespond(200, [
        'success' => true,
        'mode' => 'partner_bridge_v1',
        'status' => 'complete',
        'result_id' => $resultId,
        'session_id' => $session['session_id'],
        'partner_id' => $session['partner_id'],
        'external_user_id' => $session['external_user_id'],
        'partner_identity' => $partnerIdentity,
        'game' => $game,
        'score' => $score,
        'partner_points' => $partnerPoints,
        'idempotency_key' => $idempotencyKey,
        'webhook_preview' => $webhookPreview,
        'safety' => [
            'narrrfs_dspoinc_write' => false,
            'partner_coin_write' => false,
            'scoreboard_write' => false,
            'webhook_delivery' => false
        ]
    ]);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('[PartnerBridge close-session] ' . $error->getMessage());

    partnerBridgeRespond(500, [
        'success' => false,
        'error' => 'Partner session close failed',
        'details' => partnerBridgeIsLocalhost() ? $error->getMessage() : 'hidden'
    ]);
}
?>