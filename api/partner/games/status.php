<?php
/**
 * Partner Game Bridge V1 — Session Status
 *
 * Plain language for DEVS FOR DECADES:
 * Zeno/Samuzi backend can call this endpoint to inspect one partner game
 * session/result after create-session or close-session.
 *
 * This endpoint is read-only.
 * It does not:
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    partnerBridgeRespond(200, [
        'success' => true,
        'message' => 'Partner bridge preflight ok'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    partnerBridgeRespond(405, [
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
}

try {
    $partnerId = strtolower(trim((string)($_GET['partner_id'] ?? '')));
    $sessionId = trim((string)($_GET['session_id'] ?? ''));

    if ($partnerId === '') {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Missing partner_id'
        ]);
    }

    if ($sessionId === '') {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Missing session_id'
        ]);
    }

    if (!preg_match('/^pgs_[A-Fa-f0-9]{24}$/', $sessionId)) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Invalid session_id'
        ]);
    }

    $partnerConfig = partnerBridgeGetProjectConfig($partnerId);

    if (!$partnerConfig) {
        partnerBridgeRespond(403, [
            'success' => false,
            'error' => 'Unknown partner_id'
        ]);
    }

    $providedApiKey = partnerBridgeGetHeader('x-api-key');
    $expectedApiKey = partnerBridgeGetExpectedApiKey($partnerConfig);

    if ($expectedApiKey === '' || $providedApiKey === '' || !hash_equals($expectedApiKey, $providedApiKey)) {
        partnerBridgeRespond(403, [
            'success' => false,
            'error' => 'Invalid partner API key'
        ]);
    }

    $db = partnerBridgeGetDb();

    $sessionStmt = $db->prepare("
        SELECT
            session_id,
            partner_id,
            external_user_id,
            game,
            discord_name,
            wallet_address,
            status,
            iframe_origin,
            metadata_json,
            created_at,
            expires_at,
            closed_at
        FROM tbl_partner_game_sessions
        WHERE partner_id = :partner_id
          AND session_id = :session_id
        LIMIT 1
    ");

    $sessionStmt->execute([
        ':partner_id' => $partnerId,
        ':session_id' => $sessionId
    ]);

    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        partnerBridgeRespond(404, [
            'success' => false,
            'error' => 'Session not found'
        ]);
    }

    $resultStmt = $db->prepare("
        SELECT
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
        FROM tbl_partner_game_results
        WHERE partner_id = :partner_id
          AND session_id = :session_id
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $resultStmt->execute([
        ':partner_id' => $partnerId,
        ':session_id' => $sessionId
    ]);

    $result = $resultStmt->fetch(PDO::FETCH_ASSOC);

    $partnerIdentity = partnerBridgeBuildPartnerIdentity($session);

    $webhookPreview = null;
    if ($result && !empty($result['webhook_preview_json'])) {
        $decodedWebhookPreview = json_decode((string)$result['webhook_preview_json'], true);
        if (is_array($decodedWebhookPreview)) {
            $webhookPreview = $decodedWebhookPreview;
        }
    }

    partnerBridgeRespond(200, [
        'success' => true,
        'mode' => 'partner_bridge_v1',
        'partner_id' => $partnerId,
        'session' => [
            'session_id' => $session['session_id'],
            'external_user_id' => $session['external_user_id'],
            'game' => $session['game'],
            'status' => $session['status'],
            'discord_name' => $session['discord_name'],
            'wallet_address' => $session['wallet_address'],
            'partner_identity' => $partnerIdentity,
            'created_at' => $session['created_at'],
            'expires_at' => $session['expires_at'],
            'closed_at' => $session['closed_at']
        ],
        'result' => $result ? [
            'result_id' => $result['result_id'],
            'raw_score' => (int)$result['raw_score'],
            'partner_points' => (int)$result['partner_points'],
            'client_run_id' => $result['client_run_id'],
            'idempotency_key' => $result['idempotency_key'],
            'validation_status' => $result['validation_status'],
            'created_at' => $result['created_at'],
            'partner_identity' => $partnerIdentity,
            'webhook_preview' => $webhookPreview
        ] : null,
        'safety' => [
            'read_only' => true,
            'narrrfs_dspoinc_write' => false,
            'partner_coin_write' => false,
            'scoreboard_write' => false,
            'webhook_delivery' => false
        ]
    ]);
} catch (Throwable $error) {
    error_log('[PartnerBridge status] ' . $error->getMessage());

    partnerBridgeRespond(500, [
        'success' => false,
        'error' => 'Partner session status failed',
        'details' => partnerBridgeIsLocalhost() ? $error->getMessage() : 'hidden'
    ]);
}
?>