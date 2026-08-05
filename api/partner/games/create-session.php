<?php
/**
 * Partner Game Bridge V1 — Create Session
 *
 * Plain language for DEVS FOR DECADES:
 * Zeno/Samuzi backend calls this endpoint with a server-side API key.
 * Narrrfs returns a short-lived public session token and iframe URL.
 *
 * This endpoint creates only an isolated partner game session row.
 * It does not:
 * - write Narrrfs DSPOINC;
 * - write SPOINC;
 * - write tbl_user_scores;
 * - write tbl_tetris_scores;
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
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

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

    $partnerId = strtolower(trim((string)($body['partner_id'] ?? '')));
    $externalUserId = trim((string)($body['external_user_id'] ?? ''));
    $game = strtolower(trim((string)($body['game'] ?? '')));
    $discordName = trim((string)($body['discord_name'] ?? ''));
    $walletAddress = trim((string)($body['wallet_address'] ?? ''));

    if ($partnerId === '') {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Missing partner_id'
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

    if (!partnerBridgeValidateExternalUserId($externalUserId)) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Invalid external_user_id'
        ]);
    }

    if (!partnerBridgeValidateGame($game, $partnerConfig)) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Unsupported game for partner',
            'allowed_games' => $partnerConfig['allowed_games']
        ]);
    }

    $db = partnerBridgeGetDb();

    $sessionId = partnerBridgeCreateToken('pgs_', 12);
    $sessionToken = partnerBridgeCreateToken('pgst_', 32);
    $sessionTokenHash = partnerBridgeHashToken($sessionToken);

    $ttlSeconds = max(60, (int)($partnerConfig['session_ttl_seconds'] ?? 1800));
    $createdAt = gmdate('Y-m-d H:i:s');
    $expiresAt = gmdate('Y-m-d H:i:s', time() + $ttlSeconds);

    $metadata = [
        'source' => 'partner_bridge_v1',
        'partner_name' => $partnerConfig['partner_name'] ?? $partnerId,
        'created_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    $insertStmt = $db->prepare("
        INSERT INTO tbl_partner_game_sessions
            (
                session_id,
                session_token_hash,
                partner_id,
                external_user_id,
                game,
                discord_name,
                wallet_address,
                status,
                created_at,
                expires_at,
                metadata_json
            )
        VALUES
            (
                :session_id,
                :session_token_hash,
                :partner_id,
                :external_user_id,
                :game,
                :discord_name,
                :wallet_address,
                'open',
                :created_at,
                :expires_at,
                :metadata_json
            )
    ");

    $insertStmt->execute([
        ':session_id' => $sessionId,
        ':session_token_hash' => $sessionTokenHash,
        ':partner_id' => $partnerId,
        ':external_user_id' => $externalUserId,
        ':game' => $game,
        ':discord_name' => $discordName !== '' ? $discordName : null,
        ':wallet_address' => $walletAddress !== '' ? $walletAddress : null,
        ':created_at' => $createdAt,
        ':expires_at' => $expiresAt,
        ':metadata_json' => json_encode($metadata, JSON_UNESCAPED_SLASHES)
    ]);

    $iframeUrl = partnerBridgeGetPublicBaseUrl()
        . '/partner-games/cheeseman.html?session='
        . rawurlencode($sessionToken);

    partnerBridgeRespond(200, [
        'success' => true,
        'mode' => 'partner_bridge_v1',
        'partner_id' => $partnerId,
        'session_id' => $sessionId,
        'session_token' => $sessionToken,
        'game' => $game,
        'external_user_id' => $externalUserId,
        'iframe_url' => $iframeUrl,
        'expires_at' => $expiresAt . ' UTC',
        'safety' => [
            'narrrfs_dspoinc_write' => false,
            'partner_coin_write' => false,
            'scoreboard_write' => false,
            'webhook_delivery' => false
        ]
    ]);
} catch (Throwable $error) {
    error_log('[PartnerBridge create-session] ' . $error->getMessage());

    partnerBridgeRespond(500, [
        'success' => false,
        'error' => 'Partner session create failed',
        'details' => partnerBridgeIsLocalhost() ? $error->getMessage() : 'hidden'
    ]);
}
?>
