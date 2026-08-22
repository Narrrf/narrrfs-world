<?php
/**
 * SPOINC Bridge API — Fail abandoned Gensuki buy intent.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint is for pre-broadcast buy failures only.
 * Example: Phantom/web3 could not decode, prepare, sign, or broadcast the
 * Gensuki /buy transaction payload.
 *
 * It may confirm a Gensuki transaction as failed only after server-side checks:
 * - the logged-in/session user owns the local intent;
 * - the intent is one of the buy routes only;
 * - the local intent has no DSPOINC/SPOINC ledger movement;
 * - Gensuki still has a pending transaction for the idempotency id;
 * - Solana getSignatureStatuses says the transaction hash is not found.
 *
 * It must never credit DSPOINC.
 * It must never debit DSPOINC.
 * It must never settle SPOINC_TO_DSPOINC.
 * It must never mark complete.
 */

require_once __DIR__ . '/bridge-helpers.php';
require_once __DIR__ . '/bridge-tester-helpers.php';

spoinc_bridge_boot_json_api(['POST', 'OPTIONS']);

const SPOINC_ABANDONED_BUY_CONFIRM_PATH = '/api/custom-token-presale/confirm';
const SPOINC_ABANDONED_BUY_GET_TRANSACTION_PATH = '/api/custom-token-presale/getTransaction';

const SPOINC_ABANDONED_BUY_ROUTE_SOL = 'SOL_TO_SPOINC';
const SPOINC_ABANDONED_BUY_ROUTE_EMPIRE = 'EMPIRE_TO_SPOINC';
const SPOINC_ABANDONED_BUY_ROUTE_FOOK = 'FOOK_TO_SPOINC';

const SPOINC_ABANDONED_BUY_PENDING_STATUSES = [
    'pending',
    'buy_pending',
    'buy_payload_ready',
    'created',
    'awaiting_signature',
    'awaiting_user_signature',
    'processing'
];

function spoinc_abandoned_buy_get_gensuki_api_key(): string
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

function spoinc_abandoned_buy_build_gensuki_endpoint(array $config, string $path): string
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

function spoinc_abandoned_buy_post_json(string $url, string $apiKey, array $payload): array
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

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($apiKey !== '') {
        $headers[] = 'x-api-key: ' . $apiKey;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => $headers,
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

function spoinc_abandoned_buy_get_json(string $url, string $apiKey): array
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

    $headers = [
        'Accept: application/json'
    ];

    if ($apiKey !== '') {
        $headers[] = 'x-api-key: ' . $apiKey;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => $headers
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

function spoinc_abandoned_buy_is_valid_signature(string $value): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,128}$/', trim($value));
}

function spoinc_abandoned_buy_first_value(array $row, array $keys): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && trim((string)$row[$key]) !== '') {
            return trim((string)$row[$key]);
        }
    }

    return '';
}

function spoinc_abandoned_buy_find_deep_value($value, array $keys): string
{
    if (!is_array($value)) {
        return '';
    }

    foreach ($keys as $key) {
        if (array_key_exists($key, $value) && trim((string)$value[$key]) !== '') {
            return trim((string)$value[$key]);
        }
    }

    foreach ($value as $nestedValue) {
        if (is_array($nestedValue)) {
            $found = spoinc_abandoned_buy_find_deep_value($nestedValue, $keys);
            if ($found !== '') {
                return $found;
            }
        }
    }

    return '';
}

function spoinc_abandoned_buy_get_solana_signature_status(string $signature): array
{
    if (!spoinc_abandoned_buy_is_valid_signature($signature)) {
        return [
            'rpc_success' => false,
            'state' => 'missing_or_invalid_signature',
            'can_fail' => false,
            'can_complete' => false,
            'needs_manual_review' => true,
            'error' => 'Invalid Solana signature format.'
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'rpc_success' => false,
            'state' => 'rpc_error',
            'can_fail' => false,
            'can_complete' => false,
            'needs_manual_review' => true,
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
        'id' => 'spoinc-abandoned-buy-signature-status',
        'method' => 'getSignatureStatuses',
        'params' => [
            [$signature],
            ['searchTransactionHistory' => true]
        ]
    ];

    $rpcResponse = spoinc_abandoned_buy_post_json($rpcUrl, '', $payload);

    if (!$rpcResponse['success']) {
        return [
            'rpc_success' => false,
            'state' => 'rpc_error',
            'can_fail' => false,
            'can_complete' => false,
            'needs_manual_review' => true,
            'http_code' => $rpcResponse['http_code'],
            'error' => $rpcResponse['error'] ?: 'Solana RPC request failed.'
        ];
    }

    $rpcJson = is_array($rpcResponse['json'] ?? null) ? $rpcResponse['json'] : [];

    if (isset($rpcJson['error'])) {
        return [
            'rpc_success' => false,
            'state' => 'rpc_error',
            'can_fail' => false,
            'can_complete' => false,
            'needs_manual_review' => true,
            'http_code' => $rpcResponse['http_code'],
            'error' => 'Solana RPC returned JSON-RPC error.'
        ];
    }

    if (!isset($rpcJson['result']) || !is_array($rpcJson['result']) || !array_key_exists('value', $rpcJson['result'])) {
        return [
            'rpc_success' => false,
            'state' => 'rpc_error',
            'can_fail' => false,
            'can_complete' => false,
            'needs_manual_review' => true,
            'http_code' => $rpcResponse['http_code'],
            'error' => 'Solana RPC response missing result.value.'
        ];
    }

    $status = $rpcJson['result']['value'][0] ?? null;

    if (!is_array($status)) {
        return [
            'rpc_success' => true,
            'state' => 'not_found',
            'can_fail' => true,
            'can_complete' => false,
            'needs_manual_review' => false,
            'error' => ''
        ];
    }

    $confirmationStatus = strtolower(trim((string)($status['confirmationStatus'] ?? '')));
    $hasError = array_key_exists('err', $status) && $status['err'] !== null;
    $isConfirmedEnough = in_array($confirmationStatus, ['confirmed', 'finalized'], true);

    if (!$hasError && $isConfirmedEnough) {
        return [
            'rpc_success' => true,
            'state' => 'complete_on_chain',
            'can_fail' => false,
            'can_complete' => true,
            'needs_manual_review' => true,
            'confirmation_status' => $confirmationStatus,
            'slot' => $status['slot'] ?? null,
            'err' => $status['err'] ?? null,
            'error' => ''
        ];
    }

    if ($hasError) {
        return [
            'rpc_success' => true,
            'state' => 'failed_on_chain',
            'can_fail' => true,
            'can_complete' => false,
            'needs_manual_review' => false,
            'confirmation_status' => $confirmationStatus,
            'slot' => $status['slot'] ?? null,
            'err' => $status['err'] ?? null,
            'error' => ''
        ];
    }

    return [
        'rpc_success' => true,
        'state' => 'pending_on_chain',
        'can_fail' => false,
        'can_complete' => false,
        'needs_manual_review' => true,
        'confirmation_status' => $confirmationStatus,
        'slot' => $status['slot'] ?? null,
        'err' => $status['err'] ?? null,
        'error' => ''
    ];
}

try {
    $requestData = spoinc_bridge_get_request_data();
    $pdo = spoinc_bridge_open_database();

    $intentId = (int)($requestData['intent_id'] ?? 0);
    $reason = trim((string)($requestData['reason'] ?? 'pre_broadcast_wallet_failure'));

    if ($intentId <= 0) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'intent_id is required.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 400);
    }

    $sessionUserId = spoinc_bridge_resolve_session_user_id($requestData);
    if ($sessionUserId === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Discord session is required for abandoned buy cleanup.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 401);
    }

    $intentStmt = $pdo->prepare("
        SELECT *
        FROM tbl_spoinc_bridge_intents
        WHERE intent_id = ?
          AND discord_id = ?
          AND route_key IN (?, ?, ?)
        LIMIT 1
    ");
    $intentStmt->execute([
        $intentId,
        $sessionUserId,
        SPOINC_ABANDONED_BUY_ROUTE_SOL,
        SPOINC_ABANDONED_BUY_ROUTE_EMPIRE,
        SPOINC_ABANDONED_BUY_ROUTE_FOOK
    ]);
    $intent = $intentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$intent) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC buy intent not found for this user.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 404);
    }

    $routeKey = (string)($intent['route_key'] ?? '');
    $localStatus = (string)($intent['status'] ?? '');

    if (in_array($localStatus, ['confirmed', 'settled', 'failed'], true)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'This buy intent is already closed.',
            'data' => [
                'intent_id' => $intentId,
                'status' => $localStatus
            ],
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 409);
    }

    if (
        (float)($intent['dspoinc_amount'] ?? 0) !== 0.0
        || (float)($intent['spoinc_amount'] ?? 0) !== 0.0
    ) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Abandoned buy cleanup blocked because local intent has token amount fields.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 409);
    }

    $config = spoinc_bridge_load_config($pdo);
    if (!$config) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC bridge config is missing.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 500);
    }

    $apiKey = spoinc_abandoned_buy_get_gensuki_api_key();
    if ($apiKey === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki outbound API key is not configured.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 500);
    }

    $idempotencyId = trim((string)($intent['idempotency_id'] ?? ''));
    if ($idempotencyId === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Local buy intent is missing idempotency_id.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 409);
    }

    $getTransactionEndpoint = spoinc_abandoned_buy_build_gensuki_endpoint(
        $config,
        SPOINC_ABANDONED_BUY_GET_TRANSACTION_PATH
    );

    $getTransactionUrl = $getTransactionEndpoint
        . '?projectId=' . rawurlencode((string)$config['project_id'])
        . '&idempotencyId=' . rawurlencode($idempotencyId);

    $getTransactionResult = spoinc_abandoned_buy_get_json($getTransactionUrl, $apiKey);
    $getTransactionJson = $getTransactionResult['json'] ?? null;

    if (!$getTransactionResult['success'] || !is_array($getTransactionJson)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki transaction lookup failed. No failed confirm was sent.',
            'gensuki_http_code' => $getTransactionResult['http_code'],
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 502);
    }

    $gensukiTransaction = $getTransactionJson['data'] ?? ($getTransactionJson['transaction'] ?? $getTransactionJson);
    if (isset($gensukiTransaction['transactions']) && is_array($gensukiTransaction['transactions'])) {
        $gensukiTransaction = $gensukiTransaction['transactions'][0] ?? [];
    }

    if (!is_array($gensukiTransaction)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki transaction lookup returned an unsupported shape. No failed confirm was sent.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 502);
    }

    $transactionHash = spoinc_abandoned_buy_find_deep_value($gensukiTransaction, [
        'transactionHash',
        'transaction_hash',
        'signature',
        'hash'
    ]);

    $gensukiStatus = strtolower(spoinc_abandoned_buy_find_deep_value($gensukiTransaction, [
        'status',
        'transactionStatus',
        'transaction_status'
    ]));

    if (!spoinc_abandoned_buy_is_valid_signature($transactionHash)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki transaction lookup did not return a valid Solana transaction hash. No failed confirm was sent.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 409);
    }

    if ($gensukiStatus !== '' && !in_array($gensukiStatus, SPOINC_ABANDONED_BUY_PENDING_STATUSES, true)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki transaction is not pending; abandoned cleanup blocked.',
            'data' => [
                'gensuki_status' => $gensukiStatus
            ],
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 409);
    }

    $solanaStatus = spoinc_abandoned_buy_get_solana_signature_status($transactionHash);

    if (
        empty($solanaStatus['rpc_success'])
        || ($solanaStatus['state'] ?? '') !== 'not_found'
        || empty($solanaStatus['can_fail'])
        || !empty($solanaStatus['can_complete'])
        || !empty($solanaStatus['needs_manual_review'])
    ) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Solana recheck did not allow abandoned buy cleanup. No failed confirm was sent.',
            'data' => [
                'solana_status' => $solanaStatus
            ],
            'safety' => [
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 409);
    }

    $confirmEndpoint = spoinc_abandoned_buy_build_gensuki_endpoint(
        $config,
        SPOINC_ABANDONED_BUY_CONFIRM_PATH
    );

    $confirmPayload = [
        'projectId' => (string)$config['project_id'],
        'transactionHash' => $transactionHash,
        'status' => 'failed'
    ];

    $confirmResult = spoinc_abandoned_buy_post_json($confirmEndpoint, $apiKey, $confirmPayload);
    $confirmJson = $confirmResult['json'] ?? null;
    $confirmSuccess = !empty($confirmResult['success']) && is_array($confirmJson) && !empty($confirmJson['success']);

    $safeResponseJson = json_encode([
        'abandoned_buy_cleanup' => [
            'reason' => $reason,
            'gensuki_lookup_http_code' => $getTransactionResult['http_code'],
            'gensuki_confirm_http_code' => $confirmResult['http_code'],
            'gensuki_confirm_response' => $confirmJson,
            'solana_status' => $solanaStatus,
            'ledger_movement' => false
        ]
    ]);

    if (!$confirmSuccess) {
        $pdo->prepare("
            UPDATE tbl_spoinc_bridge_intents
            SET status = 'confirm_failed',
                gensuki_status = 'abandoned_buy_fail_confirm_failed',
                narrrfs_status = 'abandoned_buy_fail_confirm_failed_no_ledger_movement',
                error_message = :error_message,
                raw_response_json = :raw_response_json,
                updated_at = CURRENT_TIMESTAMP
            WHERE intent_id = :intent_id
        ")->execute([
            ':error_message' => 'Gensuki abandoned buy failed-confirm request failed. No DSPOINC movement happened.',
            ':raw_response_json' => $safeResponseJson,
            ':intent_id' => $intentId
        ]);

        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki failed-confirm request failed. No DSPOINC movement happened.',
            'gensuki_http_code' => $confirmResult['http_code'],
            'safety' => [
                'gensuki_confirm_called' => true,
                'gensuki_status_sent' => 'failed',
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false
            ]
        ], 502);
    }

    $pdo->prepare("
        UPDATE tbl_spoinc_bridge_intents
        SET status = 'failed',
            gensuki_status = 'abandoned_buy_failed_before_broadcast',
            narrrfs_status = 'abandoned_buy_failed_no_ledger_movement',
            failed_at = CURRENT_TIMESTAMP,
            error_message = :error_message,
            raw_response_json = :raw_response_json,
            updated_at = CURRENT_TIMESTAMP
        WHERE intent_id = :intent_id
    ")->execute([
        ':error_message' => 'Wallet transaction was not broadcast. Gensuki pending buy was safely marked failed.',
        ':raw_response_json' => $safeResponseJson,
        ':intent_id' => $intentId
    ]);

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'action' => 'fail_abandoned_buy_intent',
            'intent_id' => $intentId,
            'route_key' => $routeKey,
            'transaction_hash' => $transactionHash,
            'gensuki_status_before' => $gensukiStatus,
            'confirmed_status' => 'failed',
            'message' => 'Pending Gensuki buy was safely closed as failed.',
            'safety' => [
                'gensuki_confirm_called' => true,
                'gensuki_status_sent' => 'failed',
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false,
                'spoinc_to_dspoinc_settlement_performed' => false
            ]
        ]
    ]);
} catch (Throwable $error) {
    error_log('❌ SPOINC abandoned buy cleanup error: ' . $error->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Failed to clean abandoned SPOINC buy intent.',
        'details' => spoinc_bridge_is_localhost() ? $error->getMessage() : null,
        'safety' => [
            'gensuki_confirm_called' => false,
            'dspoinc_credit_performed' => false,
            'dspoinc_debit_performed' => false,
            'local_bridge_settlement_performed' => false
        ]
    ], 500);
}
