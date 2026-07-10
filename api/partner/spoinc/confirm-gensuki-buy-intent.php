<?php
/**
 * SPOINC Bridge API — Confirm Gensuki SOL_TO_SPOINC buy intent.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint confirms a Gensuki /buy transaction after Phantom or Ledger
 * returns a Solana signature.
 *
 * Buying SPOINC is on-chain only.
 * This endpoint must never credit DSPOINC.
 * This endpoint must never deduct DSPOINC.
 *
 * Public users are allowed only when the global bridge config and the exact
 * SOL_TO_SPOINC route are opened in the database. Internal testers can still
 * use this endpoint before public activation.
 */

require_once __DIR__ . '/bridge-helpers.php';
require_once __DIR__ . '/bridge-tester-helpers.php';

spoinc_bridge_boot_json_api(['POST', 'OPTIONS']);

const SPOINC_GENSUKI_CONFIRM_PATH = '/api/custom-token-presale/confirm';

const SPOINC_BUY_ROUTE_KEY_SOL = 'SOL_TO_SPOINC';
const SPOINC_BUY_ROUTE_KEY_EMPIRE = 'EMPIRE_TO_SPOINC';
const SPOINC_BUY_ROUTE_KEY_FOOK = 'FOOK_TO_SPOINC';

const SPOINC_BUY_DIRECTION_SOL = 'sol_to_spoinc';
const SPOINC_BUY_DIRECTION_EMPIRE = 'empire_to_spoinc';
const SPOINC_BUY_DIRECTION_FOOK = 'fook_to_spoinc';

const SPOINC_BUY_INPUT_TOKEN_SOL = 'SOL';
const SPOINC_BUY_INPUT_TOKEN_EMPIRE = 'EMPIRE';
const SPOINC_BUY_INPUT_TOKEN_FOOK = 'FOOK';

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
 * Validate a Solana transaction signature / hash shape.
 *
 * Plain language for DEVS FOR DECADES:
 * Wallet addresses and transaction signatures are both base58, but they are not
 * the same length. Wallet addresses are usually 32-44 chars. Transaction
 * signatures are normally much longer, often 87-88 chars. Do not validate
 * transaction signatures with the wallet-address helper.
 */
function spoinc_bridge_is_valid_solana_signature(string $value): bool
{
    $trimmedValue = trim($value);

    if ($trimmedValue === '') {
        return false;
    }

    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,128}$/', $trimmedValue);
}

function spoinc_bridge_transaction_hash_was_used(PDO $pdo, string $transactionHash): bool
{
    $stmt = $pdo->prepare("\n        SELECT transaction_id\n        FROM tbl_spoinc_bridge_transactions\n        WHERE transaction_hash = ? OR signature = ?\n        LIMIT 1\n    ");
    $stmt->execute([$transactionHash, $transactionHash]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Return route metadata for one Gensuki buy route.
 *
 * Plain language for DEVS FOR DECADES:
 * Buy confirmations never move DSPOINC. This helper only maps a known intent
 * route to labels used for logging, transaction rows, and user feedback.
 */
function get_spoinc_buy_confirm_route_info(string $routeKey): array
{
    if ($routeKey === SPOINC_BUY_ROUTE_KEY_SOL) {
        return [
            'route_key' => SPOINC_BUY_ROUTE_KEY_SOL,
            'direction' => SPOINC_BUY_DIRECTION_SOL,
            'input_token' => SPOINC_BUY_INPUT_TOKEN_SOL,
            'public_route_allowed' => true
        ];
    }

    if ($routeKey === SPOINC_BUY_ROUTE_KEY_EMPIRE) {
        return [
            'route_key' => SPOINC_BUY_ROUTE_KEY_EMPIRE,
            'direction' => SPOINC_BUY_DIRECTION_EMPIRE,
            'input_token' => SPOINC_BUY_INPUT_TOKEN_EMPIRE,
            'public_route_allowed' => false
        ];
    }

    if ($routeKey === SPOINC_BUY_ROUTE_KEY_FOOK) {
        return [
            'route_key' => SPOINC_BUY_ROUTE_KEY_FOOK,
            'direction' => SPOINC_BUY_DIRECTION_FOOK,
            'input_token' => SPOINC_BUY_INPUT_TOKEN_FOOK,
            'public_route_allowed' => false
        ];
    }

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Unsupported SPOINC buy confirmation route.',
        'route_key' => $routeKey
    ], 400);
}

/**
 * Load one Gensuki buy intent for the current user.
 *
 * Plain language for DEVS FOR DECADES:
 * This intentionally loads only buy routes. SPOINC_TO_DSPOINC claim intents use
 * their own confirm endpoint because that path can credit DSPOINC.
 */
function load_spoinc_buy_confirm_intent(PDO $pdo, int $intentId, string $userId): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_spoinc_bridge_intents
        WHERE intent_id = ?
          AND discord_id = ?
          AND route_key IN (?, ?, ?)
        LIMIT 1
    ");
    $stmt->execute([
        $intentId,
        $userId,
        SPOINC_BUY_ROUTE_KEY_SOL,
        SPOINC_BUY_ROUTE_KEY_EMPIRE,
        SPOINC_BUY_ROUTE_KEY_FOOK
    ]);

    $intent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$intent) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC buy intent not found for this user.'
        ], 404);
    }

    return $intent;
}

try {
            $requestData = spoinc_bridge_get_request_data();
    $pdo = spoinc_bridge_open_database();

    $intentId = (int)($requestData['intent_id'] ?? 0);
    $transactionHash = trim((string)($requestData['transactionHash'] ?? ($requestData['transaction_hash'] ?? ($requestData['signature'] ?? ''))));
    $requestedStatus = strtolower(trim((string)($requestData['status'] ?? '')));

    if ($intentId <= 0) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'intent_id is required.'
        ], 400);
    }

    if (!spoinc_bridge_is_valid_solana_signature($transactionHash)) {
    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'A valid Solana transaction signature is required.'
    ], 400);
}

    if (!in_array($requestedStatus, ['complete', 'failed'], true)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'status must be complete or failed.'
        ], 400);
    }

        $config = spoinc_bridge_load_config($pdo);

    if (!$config) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC bridge config not found.'
        ], 500);
    }

        $projectId = trim((string)($config['project_id'] ?? ''));
    if ($projectId === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki project_id is not configured.'
        ], 500);
    }

    $sessionUserId = spoinc_bridge_resolve_session_user_id($requestData);
    if ($sessionUserId === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $intent = load_spoinc_buy_confirm_intent($pdo, $intentId, $sessionUserId);
    $routeInfo = get_spoinc_buy_confirm_route_info((string)$intent['route_key']);
    $routeKey = (string)$routeInfo['route_key'];

    $userId = spoinc_bridge_require_public_route_access(
        $pdo,
        $requestData,
        $routeKey,
        false
    );

    if ($userId !== $sessionUserId) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Session user mismatch for this buy intent.'
        ], 403);
    }

    if (!$routeInfo['public_route_allowed'] && !spoinc_bridge_is_internal_tester_user($userId)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => $routeKey . ' is private tester only until final public activation.'
        ], 403);
    }

    if (in_array((string)($intent['status'] ?? ''), ['confirmed', 'settled', 'failed'], true)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This buy intent is already finalized.'
        ], 409);
    }

    if (spoinc_bridge_transaction_hash_was_used($pdo, $transactionHash)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This Solana transaction signature was already used for a bridge confirmation.'
        ], 409);
    }

    $apiKey = spoinc_bridge_get_gensuki_outbound_api_key();
    if ($apiKey === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki outbound API key is not configured on the server.'
        ], 500);
    }

$solanaSignatureStatus = spoinc_bridge_wait_for_solana_signature_status($transactionHash);
    if (empty($solanaSignatureStatus['rpc_success'])) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Solana RPC status check failed. No Gensuki confirm was sent.',
            'safety' => [
                'gensuki_confirm_sent' => false,
                'onchain_status_required' => true
            ],
            'solana_status' => $solanaSignatureStatus
        ], 502);
    }

    if (empty($solanaSignatureStatus['found'])) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Solana transaction signature is not confirmed yet. Try confirm again in a few seconds. No Gensuki confirm was sent.',
            'safety' => [
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
            'error' => 'Solana transaction is not confirmed/finalized yet. Try confirm again in a few seconds. No Gensuki confirm was sent.',
            'safety' => [
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

    $now = gmdate('Y-m-d H:i:s');
    $rawConfirmJson = json_encode([
        'confirm_endpoint' => $confirmEndpoint,
        'confirm_request' => $confirmRequest,
        'gensuki_confirm_http_code' => $gensukiConfirmResult['http_code'] ?? null,
        'gensuki_confirm_response' => $gensukiConfirmJson,
        'gensuki_confirm_raw_body' => $gensukiConfirmResult['raw_body'] ?? '',
        'gensuki_confirm_error' => $gensukiConfirmResult['error'] ?? '',
        'confirmed_by_endpoint' => basename(__FILE__),
        'solana_signature_status' => $solanaSignatureStatus,
        'ledger_movement' => false
    ]);

    if ($requestedStatus === 'failed') {
        $pdo->beginTransaction();

        $pdo->prepare("\n            UPDATE tbl_spoinc_bridge_intents\n            SET status = 'failed',\n                gensuki_status = 'buy_failed_by_user',\n                narrrfs_status = 'buy_failed_no_ledger_movement',\n                failed_at = CURRENT_TIMESTAMP,\n                updated_at = CURRENT_TIMESTAMP,\n                raw_response_json = :raw_response_json\n            WHERE intent_id = :intent_id\n        ")->execute([
            ':raw_response_json' => $rawConfirmJson,
            ':intent_id' => $intentId
        ]);

        $pdo->commit();

        spoinc_bridge_json_response([
            'success' => true,
            'data' => [
                'intent_id' => $intentId,
                'transaction_hash' => $transactionHash,
                'status' => 'failed',
                'ledger_movement' => false,
                'message' => 'Buy transaction marked failed. No DSPOINC movement happened.'
            ]
        ]);
    }

    if (!$gensukiConfirmSuccess) {
        $pdo->beginTransaction();
        $pdo->prepare("\n            UPDATE tbl_spoinc_bridge_intents\n            SET status = 'confirm_failed',\n                gensuki_status = 'buy_confirm_failed',\n                narrrfs_status = 'buy_confirm_failed_no_ledger_movement',\n                error_message = :error_message,\n                raw_response_json = :raw_response_json,\n                updated_at = CURRENT_TIMESTAMP\n            WHERE intent_id = :intent_id\n        ")->execute([
            ':error_message' => 'Gensuki confirm failed. No DSPOINC movement happened.',
            ':raw_response_json' => $rawConfirmJson,
            ':intent_id' => $intentId
        ]);
        $pdo->commit();

        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki buy confirm failed. No DSPOINC movement happened.',
            'gensuki_http_code' => $gensukiConfirmResult['http_code'] ?? null,
            'gensuki_response' => $gensukiConfirmJson
        ], 502);
    }

    $pdo->beginTransaction();

    if (spoinc_bridge_transaction_hash_was_used($pdo, $transactionHash)) {
        $pdo->rollBack();
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This Solana transaction signature was already used for a bridge confirmation.'
        ], 409);
    }

    $transactionStmt = $pdo->prepare("\n        INSERT INTO tbl_spoinc_bridge_transactions (\n            intent_id,\n            idempotency_id,\n            partner_name,\n            project_id,\n            transaction_hash,\n            signature,\n            wallet,\n            route_key,\n            direction,\n            gensuki_status,\n            narrrfs_status,\n            raw_confirm_request_json,\n            raw_confirm_response_json,\n            created_at,\n            updated_at,\n            confirmed_at\n        ) VALUES (\n            :intent_id,\n            :idempotency_id,\n            :partner_name,\n            :project_id,\n            :transaction_hash,\n            :signature,\n            :wallet,\n            :route_key,\n            :direction,\n            :gensuki_status,\n            :narrrfs_status,\n            :raw_confirm_request_json,\n            :raw_confirm_response_json,\n            :created_at,\n            :updated_at,\n            :confirmed_at\n        )\n    ");

    $transactionStmt->execute([
        ':intent_id' => $intentId,
        ':idempotency_id' => (string)$intent['idempotency_id'],
        ':partner_name' => SPOINC_BRIDGE_PARTNER_NAME,
        ':project_id' => $projectId,
        ':transaction_hash' => $transactionHash,
        ':signature' => $transactionHash,
        ':wallet' => (string)$intent['wallet'],
                ':route_key' => $routeKey,
        ':direction' => $routeInfo['direction'],
        ':gensuki_status' => 'buy_confirmed_complete',
        ':narrrfs_status' => 'buy_confirmed_no_ledger_movement',
        ':raw_confirm_request_json' => json_encode($confirmRequest),
        ':raw_confirm_response_json' => $rawConfirmJson,
        ':created_at' => $now,
        ':updated_at' => $now,
        ':confirmed_at' => $now
    ]);

    $transactionId = (int)$pdo->lastInsertId();

    $pdo->prepare("\n        UPDATE tbl_spoinc_bridge_intents\n        SET status = 'confirmed',\n            gensuki_status = 'buy_confirmed_complete',\n            narrrfs_status = 'buy_confirmed_no_ledger_movement',\n            submitted_at = COALESCE(submitted_at, CURRENT_TIMESTAMP),\n            confirmed_at = CURRENT_TIMESTAMP,\n            updated_at = CURRENT_TIMESTAMP,\n            raw_response_json = :raw_response_json\n        WHERE intent_id = :intent_id\n    ")->execute([
        ':raw_response_json' => $rawConfirmJson,
        ':intent_id' => $intentId
    ]);

    $pdo->commit();

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'intent_id' => $intentId,
            'transaction_id' => $transactionId,
            'transaction_hash' => $transactionHash,
                        'route_key' => $routeKey,
            'payment_amount' => (string)$intent['input_amount'],
            'payment_token' => $routeInfo['input_token'],
            'ledger_movement' => false,
            'message' => 'Gensuki ' . $routeKey . ' buy confirmed. No DSPOINC movement happened.'
        ]
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

        error_log('❌ SPOINC Gensuki buy confirm error: ' . $error->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Failed to confirm SPOINC Gensuki buy intent.',
        'details' => $error->getMessage()
    ], 500);
}
/**
 * Check Solana before Narrrfs sends complete/failed to Gensuki.
 *
 * Plain language for DEVS FOR DECADES:
 * Gensuki accepts the status Narrrfs sends. That means Narrrfs must first check
 * if the wallet transaction was actually accepted, failed, or is still pending.
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
        'id' => 'spoinc-buy-signature-status',
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
 * Wait briefly for Solana RPC to see the wallet transaction.
 *
 * Plain language for DEVS FOR DECADES:
 * Phantom can return a signature before every RPC node has indexed it.
 * We retry a few times so a valid wallet transaction is not rejected too early.
 */
function spoinc_bridge_wait_for_solana_signature_status(string $signature): array
{
    $lastStatus = [
        'rpc_success' => false,
        'found' => false,
        'confirmed_success' => false,
        'confirmed_failed' => false,
        'error' => 'Solana status check did not run.'
    ];

    for ($attempt = 1; $attempt <= 8; $attempt++) {
        $lastStatus = spoinc_bridge_get_solana_signature_status($signature);
        $lastStatus['wait_attempt'] = $attempt;
        $lastStatus['max_wait_attempts'] = 8;

        if (empty($lastStatus['rpc_success'])) {
            return $lastStatus;
        }

        if (!empty($lastStatus['confirmed_success']) || !empty($lastStatus['confirmed_failed'])) {
            return $lastStatus;
        }

        if ($attempt < 8) {
            sleep(2);
        }
    }

    return $lastStatus;
}
