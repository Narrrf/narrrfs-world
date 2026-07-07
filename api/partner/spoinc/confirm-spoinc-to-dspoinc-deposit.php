<?php
/**
 * SPOINC Bridge API — Confirm SPOINC → DSPOINC Gensuki Claim.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint confirms the Gensuki /claim transaction for SPOINC → DSPOINC
 * after Phantom or Ledger returns a Solana transaction signature.
 *
 * This is the DSPOINC credit endpoint.
 * Public users are allowed only when the global bridge config, settlement flag,
 * and the exact SPOINC_TO_DSPOINC route are opened in the database. Internal
 * testers can still use this endpoint before public activation.
 *
 * It only credits DSPOINC after:
 * - an existing local claim intent exists,
 * - the transaction hash/signature has not been used before,
 * - Gensuki /api/custom-token-presale/confirm accepts status = complete,
 * - the DSPOINC ledger write succeeds inside one DB transaction.
 *
 * Safety:
 * - Public route gate required for normal users.
 * - Narrrf + justme internal testers still allowed before activation.
 * - SPOINC_TO_DSPOINC only.
 * - Failed status never credits DSPOINC.
 * - Replay signatures are blocked.
 * - Frontend never decides credit.
 */

require_once __DIR__ . '/bridge-helpers.php';
require_once __DIR__ . '/bridge-tester-helpers.php';

spoinc_bridge_boot_json_api(['POST', 'OPTIONS']);

const SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY = 'SPOINC_TO_DSPOINC';
const SPOINC_TO_DSPOINC_CONFIRM_DIRECTION = 'spoinc_to_dspoinc';
const SPOINC_TO_DSPOINC_LEDGER_SOURCE = 'spoinc_bridge';
const SPOINC_TO_DSPOINC_LEDGER_ACTION = 'credit_dspoinc_from_gensuki_spoinc_claim';
const SPOINC_GENSUKI_CONFIRM_PATH = '/api/custom-token-presale/confirm';
const SPOINC_GENSUKI_STATUS_PATH = '/api/custom-token-presale/status';

/**
 * Return the first configured outbound Gensuki API key.
 *
 * Plain language for DEVS FOR DECADES:
 * Production must use server environment variables. Local XAMPP may use the
 * ignored api/config/gensuki-outbound-local.php file because Apache/PHP does not
 * automatically read Git Bash exported variables. This key must never reach the
 * frontend, GitHub, Discord, screenshots, or QUICK_STATUS.
 */
function spoinc_bridge_get_gensuki_outbound_api_key(): string
{
    $candidateNames = [
        'GENSUKI_SPOINC_API_KEY',
        'GENSUKI_OUTBOUND_API_KEY',
        'SPOINC_GENSUKI_API_KEY',
        'GENSUKI_API_KEY'
    ];

    foreach ($candidateNames as $candidateName) {
        $value = trim((string)getenv($candidateName));
        if ($value !== '') {
            return $value;
        }
    }

    $localConfigPath = __DIR__ . '/../../config/gensuki-outbound-local.php';
    $isLocalhost = function_exists('spoinc_bridge_is_localhost') && spoinc_bridge_is_localhost();

    if ($isLocalhost && is_file($localConfigPath)) {
        $GENSUKI_OUTBOUND_API_KEY = '';
        require $localConfigPath;

        $localKey = trim((string)$GENSUKI_OUTBOUND_API_KEY);
        if ($localKey !== '') {
            return $localKey;
        }
    }

    return '';
}

/**
 * Build a Gensuki endpoint URL from bridge config.
 */
function spoinc_bridge_build_gensuki_endpoint(array $config, string $path): string
{
    $baseUrl = trim((string)($config['api_base_url'] ?? ''));

    if ($baseUrl === '') {
        $baseUrl = 'https://app.gensuki.xyz';
    }

    $baseUrl = rtrim($baseUrl, '/');

    if (preg_match('#/api/custom-token-presale$#', $baseUrl)) {
        return $baseUrl . '/' . ltrim(str_replace('/api/custom-token-presale', '', $path), '/');
    }

    if (preg_match('#/api$#', $baseUrl)) {
        return $baseUrl . '/custom-token-presale/' . ltrim(str_replace('/api/custom-token-presale', '', $path), '/');
    }

    return $baseUrl . $path;
}

/**
 * POST JSON to Gensuki with x-api-key authentication.
 */
function spoinc_bridge_post_gensuki_json(string $url, string $apiKey, array $payload): array
{
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'http_code' => 0,
            'json' => null,
            'raw_body' => '',
            'error' => 'PHP cURL extension is not available.'
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'x-api-key: ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $rawBody = (string)curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($rawBody, true);

    return [
        'success' => $curlError === '' && $httpCode >= 200 && $httpCode < 300 && is_array($json),
        'http_code' => $httpCode,
        'json' => is_array($json) ? $json : null,
        'raw_body' => $rawBody,
        'error' => $curlError
    ];
}

/**
 * Return the active Narrrfs season name for ledger rows.
 */
function load_spoinc_bridge_active_season(PDO $pdo): string
{
    $stmt = $pdo->prepare("\n        SELECT season_name\n        FROM tbl_seasons\n        WHERE is_active = 1\n        LIMIT 1\n    ");
    $stmt->execute();

    $season = trim((string)$stmt->fetchColumn());

    return $season !== '' ? $season : 'Season 12';
}

/**
 * Validate the shape of a Solana transaction signature.
 *
 * Plain language for DEVS:
 * This is only a format check. Gensuki /confirm is the partner lifecycle check.
 */
function spoinc_bridge_is_valid_transaction_hash(string $signature): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,100}$/', $signature);
}

/**
 * Check the Solana signature status before Narrrfs tells Gensuki complete/failed.
 *
 * Plain language for DEVS FOR DECADES:
 * The frontend may report a transaction hash, but the server must check Solana
 * before it sends a final status to Gensuki. This prevents DSPOINC credit when
 * a wallet transaction is missing, still pending, or failed on-chain.
 */
function spoinc_bridge_get_solana_signature_status(string $signature): array
{
    if (!function_exists('curl_init')) {
        return [
            'rpc_success' => false,
            'found' => false,
            'confirmed_success' => false,
            'confirmed_failed' => false,
            'error' => 'PHP cURL extension is not available.'
        ];
    }

    $rpcUrl = trim((string)getenv('SOLANA_RPC_URL'));
    if ($rpcUrl === '') {
        $rpcUrl = trim((string)getenv('HELIUS_RPC_URL'));
    }
    if ($rpcUrl === '') {
        $rpcUrl = 'https://api.mainnet-beta.solana.com';
    }

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 'spoinc-bridge-signature-status',
        'method' => 'getSignatureStatuses',
        'params' => [
            [$signature],
            ['searchTransactionHistory' => true]
        ]
    ];

    $ch = curl_init($rpcUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $rawBody = (string)curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($rawBody, true);

    if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300 || !is_array($json)) {
        return [
            'rpc_success' => false,
            'found' => false,
            'confirmed_success' => false,
            'confirmed_failed' => false,
            'http_code' => $httpCode,
            'raw_body' => $rawBody,
            'error' => $curlError !== '' ? $curlError : 'Invalid Solana RPC response.'
        ];
    }

    $status = $json['result']['value'][0] ?? null;
    if (!is_array($status)) {
        return [
            'rpc_success' => true,
            'found' => false,
            'confirmed_success' => false,
            'confirmed_failed' => false,
            'http_code' => $httpCode,
            'raw_body' => $rawBody,
            'error' => 'Solana signature not found yet.'
        ];
    }

    $confirmationStatus = strtolower(trim((string)($status['confirmationStatus'] ?? '')));
    $hasError = array_key_exists('err', $status) && $status['err'] !== null;
    $isConfirmedEnough = in_array($confirmationStatus, ['confirmed', 'finalized'], true);

    return [
        'rpc_success' => true,
        'found' => true,
        'confirmed_success' => !$hasError && $isConfirmedEnough,
        'confirmed_failed' => $hasError,
        'confirmation_status' => $confirmationStatus,
        'slot' => $status['slot'] ?? null,
        'err' => $status['err'] ?? null,
        'http_code' => $httpCode,
        'raw_body' => $rawBody,
        'error' => ''
    ];
}

/**
 * Load a pending SPOINC_TO_DSPOINC Gensuki claim intent for this user.
 */
function load_spoinc_to_dspoinc_claim_intent(PDO $pdo, int $intentId, string $userId): ?array
{
    $stmt = $pdo->prepare("\n        SELECT *\n        FROM tbl_spoinc_bridge_intents\n        WHERE intent_id = ?\n          AND discord_id = ?\n          AND route_key = ?\n          AND status IN ('claim_payload_ready', 'claim_pending', 'claim_signature_returned', 'claim_confirm_pending')\n          AND narrrfs_status IN ('awaiting_user_signature', 'awaiting_gensuki_confirm', 'awaiting_gensuki_claim_payload')\n        LIMIT 1\n    ");
    $stmt->execute([
        $intentId,
        $userId,
        SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY
    ]);

    $intent = $stmt->fetch(PDO::FETCH_ASSOC);

    return $intent ?: null;
}

/**
 * Return true if a Solana signature was already used in this bridge.
 */
function spoinc_bridge_signature_was_used(PDO $pdo, string $signature): bool
{
    $stmt = $pdo->prepare("\n        SELECT COUNT(*) AS used_count\n        FROM tbl_spoinc_bridge_transactions\n        WHERE signature = ?\n           OR transaction_hash = ?\n    ");
    $stmt->execute([$signature, $signature]);

    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Mark an intent failed without changing DSPOINC.
 */
function mark_spoinc_claim_intent_failed(PDO $pdo, int $intentId, string $reason, array $rawResponse = []): void
{
    $stmt = $pdo->prepare("\n        UPDATE tbl_spoinc_bridge_intents\n        SET status = 'failed',\n            gensuki_status = 'confirm_failed',\n            narrrfs_status = 'failed_no_ledger_movement',\n            failed_at = CURRENT_TIMESTAMP,\n            error_message = :error_message,\n            raw_response_json = :raw_response_json,\n            updated_at = CURRENT_TIMESTAMP\n        WHERE intent_id = :intent_id\n    ");
    $stmt->execute([
        ':error_message' => $reason,
        ':raw_response_json' => json_encode($rawResponse),
        ':intent_id' => $intentId
    ]);
}

try {
        $requestData = spoinc_bridge_get_request_data();
    $pdo = spoinc_bridge_open_database();
    $userId = spoinc_bridge_require_public_route_access(
        $pdo,
        $requestData,
        SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY,
        true
    );

    $intentId = (int)($requestData['intent_id'] ?? 0);
    $transactionHash = trim((string)($requestData['transactionHash'] ?? ($requestData['transaction_hash'] ?? ($requestData['signature'] ?? ($requestData['tx_signature'] ?? '')))));
    $requestedStatus = strtolower(trim((string)($requestData['status'] ?? '')));

    if ($intentId <= 0) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'intent_id is required.'
        ], 400);
    }

    if (!in_array($requestedStatus, ['complete', 'failed'], true)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'status must be complete or failed.'
        ], 400);
    }

    if (!spoinc_bridge_is_valid_transaction_hash($transactionHash)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Valid Solana transactionHash/signature is required.'
        ], 400);
    }

        $intent = load_spoinc_to_dspoinc_claim_intent($pdo, $intentId, $userId);

    if (!$intent) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'No matching pending SPOINC_TO_DSPOINC Gensuki claim intent found.'
        ], 404);
    }

    $expiresAt = trim((string)($intent['expires_at'] ?? ''));
    if ($expiresAt !== '' && strtotime($expiresAt . ' UTC') < time()) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This claim intent has expired. Create a fresh intent.'
        ], 410);
    }

    if (spoinc_bridge_signature_was_used($pdo, $transactionHash)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This Solana transaction signature was already used for a bridge confirmation.'
        ], 409);
    }

    $config = spoinc_bridge_load_config($pdo);
    if (!$config) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC bridge config not found.'
        ], 500);
    }

    $projectId = trim((string)($intent['project_id'] ?? ($config['project_id'] ?? '')));
    if ($projectId === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki project_id is not configured.'
        ], 500);
    }

    $apiKey = spoinc_bridge_get_gensuki_outbound_api_key();
    if ($apiKey === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki outbound API key is not configured on the server.',
            'hint' => 'Set GENSUKI_SPOINC_API_KEY or GENSUKI_OUTBOUND_API_KEY in local/server environment.'
        ], 500);
    }

    $senderWallet = trim((string)$intent['wallet']);
    $spoincAmount = trim((string)$intent['spoinc_amount']);
    $dspoincAmount = (int)$intent['dspoinc_amount'];

    if (!spoinc_bridge_is_valid_solana_address($senderWallet)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Intent sender wallet is invalid.'
        ], 500);
    }

    if ($dspoincAmount <= 0) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Intent DSPOINC amount is invalid.'
        ], 500);
    }

        $solanaSignatureStatus = spoinc_bridge_get_solana_signature_status($transactionHash);

    if (empty($solanaSignatureStatus['rpc_success'])) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Solana RPC status check failed. No Gensuki confirm was sent and no DSPOINC was credited.',
            'safety' => [
                'dspoinc_credited' => false,
                'gensuki_confirm_sent' => false,
                'onchain_status_required' => true
            ],
            'solana_status' => $solanaSignatureStatus
        ], 502);
    }

    if (empty($solanaSignatureStatus['found'])) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Solana transaction signature is not confirmed yet. Try confirm again in a few seconds. No DSPOINC was credited.',
            'safety' => [
                'dspoinc_credited' => false,
                'gensuki_confirm_sent' => false,
                'onchain_status_required' => true
            ],
            'solana_status' => $solanaSignatureStatus
        ], 425);
    }

    if (!empty($solanaSignatureStatus['confirmed_failed'])) {
        $requestedStatus = 'failed';
    } elseif (!empty($solanaSignatureStatus['confirmed_success'])) {
        $requestedStatus = 'complete';
    } else {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Solana transaction is not confirmed/finalized yet. Try confirm again in a few seconds. No DSPOINC was credited.',
            'safety' => [
                'dspoinc_credited' => false,
                'gensuki_confirm_sent' => false,
                'onchain_status_required' => true
            ],
            'solana_status' => $solanaSignatureStatus
        ], 425);
    }

    $confirmEndpoint = spoinc_bridge_build_gensuki_endpoint($config, SPOINC_GENSUKI_CONFIRM_PATH);
    $confirmRequest = [
        'projectId' => $projectId,
        'transactionHash' => $transactionHash,
        'status' => $requestedStatus
    ];

    $gensukiConfirmResult = spoinc_bridge_post_gensuki_json($confirmEndpoint, $apiKey, $confirmRequest);
    $gensukiConfirmJson = $gensukiConfirmResult['json'] ?? [];
    $gensukiConfirmSuccess = !empty($gensukiConfirmResult['success']) && !empty($gensukiConfirmJson['success']);

    if ($requestedStatus === 'failed') {
        $pdo->beginTransaction();
        mark_spoinc_claim_intent_failed($pdo, (int)$intent['intent_id'], 'Frontend reported transaction failed. No DSPOINC credited.', [
            'confirm_endpoint' => $confirmEndpoint,
            'confirm_request' => $confirmRequest,
            'gensuki_confirm_http_code' => $gensukiConfirmResult['http_code'] ?? null,
            'gensuki_confirm_response' => $gensukiConfirmJson,
            'gensuki_confirm_raw_body' => $gensukiConfirmResult['raw_body'] ?? '',
            'gensuki_confirm_error' => $gensukiConfirmResult['error'] ?? ''
        ]);
        $pdo->commit();

        spoinc_bridge_json_response([
            'success' => true,
            'data' => [
                'intent_id' => (int)$intent['intent_id'],
                'transaction_hash' => $transactionHash,
                'status' => 'failed',
                'credited_dspoinc_amount' => 0,
                'message' => 'Transaction marked failed. No DSPOINC credited.'
            ]
        ]);
    }

    if (!$gensukiConfirmSuccess) {
        $pdo->beginTransaction();
        mark_spoinc_claim_intent_failed($pdo, (int)$intent['intent_id'], 'Gensuki confirm failed. No DSPOINC credited.', [
            'confirm_endpoint' => $confirmEndpoint,
            'confirm_request' => $confirmRequest,
            'gensuki_confirm_http_code' => $gensukiConfirmResult['http_code'] ?? null,
            'gensuki_confirm_response' => $gensukiConfirmJson,
            'gensuki_confirm_raw_body' => $gensukiConfirmResult['raw_body'] ?? '',
            'gensuki_confirm_error' => $gensukiConfirmResult['error'] ?? ''
        ]);
        $pdo->commit();

        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki confirm failed. No DSPOINC was credited.',
            'gensuki_http_code' => $gensukiConfirmResult['http_code'] ?? null,
            'gensuki_response' => $gensukiConfirmJson
        ], 502);
    }

    $season = load_spoinc_bridge_active_season($pdo);
    $now = gmdate('Y-m-d H:i:s');
    $rawConfirmJson = json_encode([
        'confirm_endpoint' => $confirmEndpoint,
        'confirm_request' => $confirmRequest,
        'gensuki_confirm_http_code' => $gensukiConfirmResult['http_code'] ?? null,
        'gensuki_confirm_response' => $gensukiConfirmJson,
        'gensuki_confirm_raw_body' => $gensukiConfirmResult['raw_body'] ?? '',
        'confirmed_by_endpoint' => basename(__FILE__),
        'solana_signature_status' => $solanaSignatureStatus
    ]);

    $pdo->beginTransaction();

    if (spoinc_bridge_signature_was_used($pdo, $transactionHash)) {
        $pdo->rollBack();

        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This Solana transaction signature was already used for a bridge confirmation.'
        ], 409);
    }

    $reloadStmt = $pdo->prepare("\n        SELECT status, narrrfs_status\n        FROM tbl_spoinc_bridge_intents\n        WHERE intent_id = ?\n        LIMIT 1\n    ");
    $reloadStmt->execute([(int)$intent['intent_id']]);
    $latestIntentState = $reloadStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (($latestIntentState['status'] ?? '') === 'settled' || ($latestIntentState['narrrfs_status'] ?? '') === 'settled') {
        $pdo->rollBack();

        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This bridge intent was already settled.'
        ], 409);
    }

    $transactionStmt = $pdo->prepare("\n        INSERT INTO tbl_spoinc_bridge_transactions (\n            intent_id,\n            idempotency_id,\n            partner_name,\n            project_id,\n            transaction_hash,\n            signature,\n            wallet,\n            route_key,\n            direction,\n            gensuki_status,\n            narrrfs_status,\n            raw_get_transaction_response_json,\n            created_at,\n            updated_at,\n            confirmed_at\n        ) VALUES (\n            :intent_id,\n            :idempotency_id,\n            :partner_name,\n            :project_id,\n            :transaction_hash,\n            :signature,\n            :wallet,\n            :route_key,\n            :direction,\n            :gensuki_status,\n            :narrrfs_status,\n            :raw_get_transaction_response_json,\n            :created_at,\n            :updated_at,\n            :confirmed_at\n        )\n    ");

    $transactionStmt->execute([
        ':intent_id' => (int)$intent['intent_id'],
        ':idempotency_id' => $intent['idempotency_id'],
        ':partner_name' => SPOINC_BRIDGE_PARTNER_NAME,
        ':project_id' => $projectId,
        ':transaction_hash' => $transactionHash,
        ':signature' => $transactionHash,
        ':wallet' => $senderWallet,
        ':route_key' => SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY,
        ':direction' => SPOINC_TO_DSPOINC_CONFIRM_DIRECTION,
        ':gensuki_status' => 'complete',
        ':narrrfs_status' => 'gensuki_confirmed',
        ':raw_get_transaction_response_json' => $rawConfirmJson,
        ':created_at' => $now,
        ':updated_at' => $now,
        ':confirmed_at' => $now
    ]);

    $transactionId = (int)$pdo->lastInsertId();

    $scoreStmt = $pdo->prepare("\n        INSERT INTO tbl_user_scores (\n            user_id,\n            score,\n            game,\n            source,\n            season\n        ) VALUES (\n            :user_id,\n            :score,\n            :game,\n            :source,\n            :season\n        )\n    ");

    $scoreStmt->execute([
        ':user_id' => $userId,
        ':score' => $dspoincAmount,
        ':game' => 'spoinc_bridge',
        ':source' => SPOINC_TO_DSPOINC_LEDGER_SOURCE,
        ':season' => $season
    ]);

    $scoreId = (int)$pdo->lastInsertId();

    $reason = sprintf(
        'Gensuki SPOINC bridge claim confirmed: %s SPOINC -> %d DSPOINC (intent_id: %d, tx: %s)',
        $spoincAmount,
        $dspoincAmount,
        (int)$intent['intent_id'],
        $transactionHash
    );

    $adjustStmt = $pdo->prepare("\n        INSERT INTO tbl_score_adjustments (\n            user_id,\n            admin_id,\n            amount,\n            action,\n            reason\n        ) VALUES (\n            :user_id,\n            :admin_id,\n            :amount,\n            :action,\n            :reason\n        )\n    ");

    $adjustStmt->execute([
        ':user_id' => $userId,
        ':admin_id' => 'system-spoinc-bridge',
        ':amount' => $dspoincAmount,
        ':action' => 'add',
        ':reason' => $reason
    ]);

    $scoreAdjustmentId = (int)$pdo->lastInsertId();

    $metadataJson = json_encode([
        'gensuki_confirm_response' => $gensukiConfirmJson,
        'sender_wallet' => $senderWallet,
        'season' => $season,
                'confirmed_by_endpoint' => basename(__FILE__),
        'solana_signature_status' => $solanaSignatureStatus,
        'settlement_rule' => 'DSPOINC credited only after Solana signature status success and Gensuki /confirm status complete.'
    ]);

    $auditStmt = $pdo->prepare("\n        INSERT INTO tbl_spoinc_bridge_ledger_audit (\n            intent_id,\n            transaction_id,\n            idempotency_id,\n            transaction_hash,\n            discord_id,\n            wallet,\n            route_key,\n            direction,\n            ledger_action,\n            dspoinc_delta,\n            spoinc_amount,\n            conversion_rate_dspoinc_per_spoinc,\n            tbl_user_scores_id,\n            tbl_score_adjustment_id,\n            status,\n            metadata_json,\n            processed_at\n        ) VALUES (\n            :intent_id,\n            :transaction_id,\n            :idempotency_id,\n            :transaction_hash,\n            :discord_id,\n            :wallet,\n            :route_key,\n            :direction,\n            :ledger_action,\n            :dspoinc_delta,\n            :spoinc_amount,\n            :conversion_rate,\n            :tbl_user_scores_id,\n            :tbl_score_adjustment_id,\n            :status,\n            :metadata_json,\n            :processed_at\n        )\n    ");

    $auditStmt->execute([
        ':intent_id' => (int)$intent['intent_id'],
        ':transaction_id' => $transactionId,
        ':idempotency_id' => $intent['idempotency_id'],
        ':transaction_hash' => $transactionHash,
        ':discord_id' => $userId,
        ':wallet' => $senderWallet,
        ':route_key' => SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY,
        ':direction' => SPOINC_TO_DSPOINC_CONFIRM_DIRECTION,
        ':ledger_action' => SPOINC_TO_DSPOINC_LEDGER_ACTION,
        ':dspoinc_delta' => $dspoincAmount,
        ':spoinc_amount' => $spoincAmount,
        ':conversion_rate' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        ':tbl_user_scores_id' => $scoreId,
        ':tbl_score_adjustment_id' => $scoreAdjustmentId,
        ':status' => 'processed',
        ':metadata_json' => $metadataJson,
        ':processed_at' => $now
    ]);

    $responseData = [
        'intent_id' => (int)$intent['intent_id'],
        'transaction_id' => $transactionId,
        'transaction_hash' => $transactionHash,
        'signature' => $transactionHash,
        'discord_id' => $userId,
        'route_key' => SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY,
        'sender_wallet' => $senderWallet,
        'spoinc_amount' => $spoincAmount,
        'credited_dspoinc_amount' => $dspoincAmount,
        'tbl_user_scores_id' => $scoreId,
        'tbl_score_adjustment_id' => $scoreAdjustmentId,
        'status' => 'settled',
        'gensuki_status' => 'complete',
        'narrrfs_status' => 'settled',
        'settled_at' => $now,
                'safety' => [
            'private_tester_only' => false,
            'public_route_gate_checked' => true,
            'route_key' => SPOINC_TO_DSPOINC_CONFIRM_ROUTE_KEY,
            'requires_settlement_enabled' => true,
            'dspoinc_credited' => true,
            'dspoinc_debited' => false,
            'signature_reuse_blocked' => true,
            'gensuki_confirm_required' => true
        ]
    ];

    $intentUpdateStmt = $pdo->prepare("\n        UPDATE tbl_spoinc_bridge_intents\n        SET status = 'settled',\n            gensuki_status = 'complete',\n            narrrfs_status = 'settled',\n            submitted_at = COALESCE(submitted_at, :submitted_at),\n            confirmed_at = :confirmed_at,\n            settled_at = :settled_at,\n            raw_response_json = :raw_response_json,\n            updated_at = CURRENT_TIMESTAMP\n        WHERE intent_id = :intent_id\n    ");

    $intentUpdateStmt->execute([
        ':submitted_at' => $now,
        ':confirmed_at' => $now,
        ':settled_at' => $now,
        ':raw_response_json' => json_encode($responseData),
        ':intent_id' => (int)$intent['intent_id']
    ]);

    $pdo->commit();

    spoinc_bridge_json_response([
        'success' => true,
        'data' => $responseData
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('❌ SPOINC_TO_DSPOINC Gensuki confirm error: ' . $error->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Failed to confirm SPOINC_TO_DSPOINC Gensuki claim.',
        'details' => $error->getMessage()
    ], 500);
}
