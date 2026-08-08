<?php
/**
 * Partner Game Bridge V1 — Browser-Safe Session Info
 *
 * Plain language for DEVS FOR DECADES:
 * The Cheese Runner iframe calls this endpoint with only the short-lived
 * public partner session token. It returns display-safe partner identity and
 * isolated partner stats.
 *
 * This endpoint is read-only.
 * It does not:
 * - require or expose the partner API key;
 * - perform Narrrfs Discord OAuth;
 * - auto-create Narrrfs users;
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
header('Access-Control-Allow-Headers: Content-Type');

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
    $sessionToken = trim((string)($_GET['session'] ?? ''));

    if ($sessionToken === '') {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Missing session'
        ]);
    }

    if (preg_match('/^pgst_[A-Fa-f0-9]{64}$/', $sessionToken) !== 1) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Invalid session token'
        ]);
    }

    $db = partnerBridgeGetDb();
    $sessionTokenHash = partnerBridgeHashToken($sessionToken);

    $sessionStmt = $db->prepare("
        SELECT
            session_id,
            partner_id,
            external_user_id,
            game,
            discord_name,
            wallet_address,
            status,
            metadata_json,
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
        partnerBridgeRespond(404, [
            'success' => false,
            'error' => 'Session not found'
        ]);
    }

    $expiresAtUnix = strtotime((string)$session['expires_at'] . ' UTC');
    $effectiveStatus = (string)$session['status'];

    if ($effectiveStatus === 'open' && (!$expiresAtUnix || $expiresAtUnix < time())) {
        $effectiveStatus = 'expired';
    }

    $partnerIdentity = partnerBridgeBuildPartnerIdentity($session);
    $partnerStats = partnerBridgeGetPartnerStats(
        $db,
        (string)$session['partner_id'],
        (string)$session['external_user_id'],
        (string)$session['game']
    );

    partnerBridgeRespond(200, [
        'success' => true,
        'mode' => 'partner_bridge_v1',
        'partner_id' => $session['partner_id'],
        'game' => $session['game'],
        'session' => [
            'session_id' => $session['session_id'],
            'external_user_id' => $session['external_user_id'],
            'status' => $effectiveStatus,
            'created_at' => $session['created_at'],
            'expires_at' => $session['expires_at'],
            'closed_at' => $session['closed_at']
        ],
        'partner_identity' => $partnerIdentity,
        'partner_stats' => $partnerStats,
        'safety' => [
            'read_only' => true,
            'browser_safe' => true,
            'narrrfs_oauth_login' => false,
            'narrrfs_dspoinc_write' => false,
            'partner_coin_write' => false,
            'scoreboard_write' => false,
            'webhook_delivery' => false
        ]
    ]);
} catch (Throwable $error) {
    error_log('[PartnerBridge session-info] ' . $error->getMessage());

    partnerBridgeRespond(500, [
        'success' => false,
        'error' => 'Partner session info failed',
        'details' => partnerBridgeIsLocalhost() ? $error->getMessage() : 'hidden'
    ]);
}
?>
