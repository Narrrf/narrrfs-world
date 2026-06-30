<?php
/**
 * SPOINC Bridge API — Create Gensuki SOL_TO_SPOINC buy intent.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint asks Gensuki for a prepared /buy transaction so a logged-in
 * user can buy SPOINC with SOL through the Narrrfs Swap Lab.
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

const SPOINC_GENSUKI_BUY_PATH = '/api/custom-token-presale/buy';
const SPOINC_BUY_ROUTE_KEY_SOL = 'SOL_TO_SPOINC';
const SPOINC_BUY_DIRECTION_SOL = 'sol_to_spoinc';
const SPOINC_BUY_INPUT_TOKEN_SOL = 'SOL';
const SPOINC_BUY_OUTPUT_TOKEN = 'SPOINC';
const SPOINC_BUY_NATIVE_SOL_ADDRESS = '11111111111111111111111111111111';
const SPOINC_BUY_MIN_SOL_LAUNCH = 0.025;
const SPOINC_BUY_MAX_SOL_LAUNCH = 10.0;
const SPOINC_BUY_EXPIRES_HOURS = 2;

/**
 * Return the first configured outbound Gensuki API key.
 *
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
 * Build the Gensuki endpoint URL from bridge config.
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
 * Fetch a recent Solana blockhash server-side so the browser does not call RPC.
 */
function spoinc_bridge_fetch_latest_blockhash(): array
{
    $rpcUrl = trim((string)getenv('SOLANA_RPC_URL'));

    if ($rpcUrl === '') {
        $heliusApiKey = trim((string)getenv('HELIUS_API_KEY'));
        if ($heliusApiKey !== '') {
            $rpcUrl = 'https://mainnet.helius-rpc.com/?api-key=' . rawurlencode($heliusApiKey);
        }
    }

    if ($rpcUrl === '') {
        $rpcUrl = 'https://api.mainnet-beta.solana.com';
    }

    $rpcPayload = [
        'jsonrpc' => '2.0',
        'id' => 'spoinc-buy-blockhash',
        'method' => 'getLatestBlockhash',
        'params' => [['commitment' => 'confirmed']]
    ];

    $ch = curl_init($rpcUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($rpcPayload)
    ]);

    $rawBody = (string)curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($rawBody, true);
    $value = $json['result']['value'] ?? [];
    $blockhash = trim((string)($value['blockhash'] ?? ''));

    if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300 || $blockhash === '') {
        return [
            'success' => false,
            'error' => 'Could not fetch latest Solana blockhash.',
            'http_code' => $httpCode,
            'rpc_error' => $curlError
        ];
    }

    return [
        'success' => true,
        'blockhash' => $blockhash,
        'last_valid_block_height' => $value['lastValidBlockHeight'] ?? null,
        'rpc_source' => preg_replace('/api-key=[^&]+/', 'api-key=hidden', $rpcUrl)
    ];
}

/**
 * Normalize a SOL amount for private buy tests.
 */
function normalize_launch_sol_payment_amount(string $amount): string{
    $amount = trim($amount);

    if ($amount === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SOL payment amount is required.'
        ], 400);
    }

    if (!preg_match('/^\d+(\.\d{1,9})?$/', $amount)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SOL payment amount must be a positive number with max 9 decimals.'
        ], 400);
    }

    $floatAmount = (float)$amount;
    if ($floatAmount < SPOINC_BUY_MIN_SOL_LAUNCH || $floatAmount > SPOINC_BUY_MAX_SOL_LAUNCH) {
        spoinc_bridge_json_response([
    'success' => false,
    'error' => 'SOL_TO_SPOINC buy amount must be between ' . SPOINC_BUY_MIN_SOL_LAUNCH . ' and ' . SPOINC_BUY_MAX_SOL_LAUNCH . ' SOL.',
    'min_sol' => SPOINC_BUY_MIN_SOL_LAUNCH,
    'max_sol' => SPOINC_BUY_MAX_SOL_LAUNCH
], 400);
    }

    return rtrim(rtrim(number_format($floatAmount, 9, '.', ''), '0'), '.');
}

/**
 * Create a replay-safe local idempotency id for the Gensuki buy route.
 */
function create_spoinc_buy_idempotency_id(string $userId, string $wallet, string $paymentAmount): string
{
    return 'spoinc-buy-sol-' . $userId . '-' . substr(hash('sha256', $wallet), 0, 10) . '-' . str_replace('.', '_', $paymentAmount) . '-' . bin2hex(random_bytes(8));
}

/**
 * Read Gensuki response value defensively across snake/camel cases.
 */
function read_gensuki_buy_value(array $payload, string $field): string
{
    $candidates = [
        $field,
        lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $field)))),
        strtolower($field),
        strtoupper($field)
    ];

    foreach ($candidates as $candidate) {
        if (isset($payload[$candidate]) && is_scalar($payload[$candidate])) {
            return trim((string)$payload[$candidate]);
        }
    }

    return '';
}

try {
        $requestData = spoinc_bridge_get_request_data();
    $pdo = spoinc_bridge_open_database();
    $userId = spoinc_bridge_require_public_route_access(
        $pdo,
        $requestData,
        SPOINC_BUY_ROUTE_KEY_SOL,
        false
    );

    $wallet = trim((string)($requestData['wallet'] ?? ''));
    $paymentAmount = normalize_launch_sol_payment_amount(trim((string)($requestData['payment_amount'] ?? ($requestData['amount'] ?? ''))));
    $paymentTokenAddress = trim((string)($requestData['payment_token_address'] ?? SPOINC_BUY_NATIVE_SOL_ADDRESS));

    if ($paymentTokenAddress === '') {
        $paymentTokenAddress = SPOINC_BUY_NATIVE_SOL_ADDRESS;
    }

    if ($paymentTokenAddress !== SPOINC_BUY_NATIVE_SOL_ADDRESS) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Step 2 only supports SOL_TO_SPOINC private testing. EMPIRE / FOOK come later.'
        ], 400);
    }

    if (!spoinc_bridge_is_valid_solana_address($wallet)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'A valid buyer Solana wallet is required.'
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

    $apiKey = spoinc_bridge_get_gensuki_outbound_api_key();
    if ($apiKey === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki outbound API key is not configured on the server.'
        ], 500);
    }

    $blockhashResult = spoinc_bridge_fetch_latest_blockhash();
    if (empty($blockhashResult['success'])) {
        spoinc_bridge_json_response($blockhashResult, 502);
    }

    $idempotencyId = create_spoinc_buy_idempotency_id($userId, $wallet, $paymentAmount);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + (SPOINC_BUY_EXPIRES_HOURS * 3600));
    $gensukiEndpoint = spoinc_bridge_build_gensuki_endpoint($config, SPOINC_GENSUKI_BUY_PATH);

    $gensukiBuyRequest = [
        'buyerAddress' => $wallet,
        'projectId' => $projectId,
        'paymentAmount' => (float)$paymentAmount,
        'paymentTokenAddress' => $paymentTokenAddress,
        'latestBlockhash' => $blockhashResult['blockhash'],
        'idempotencyId' => $idempotencyId
    ];

    $rawRequestJson = json_encode([
        'request_type' => 'public_sol_to_spoinc_gensuki_buy_intent',
        'user_id' => $userId,
        'wallet' => $wallet,
        'payment_amount' => $paymentAmount,
        'payment_token_address' => $paymentTokenAddress,
        'gensuki_endpoint' => $gensukiEndpoint,
        'gensuki_buy_request' => $gensukiBuyRequest,
        'created_by_endpoint' => basename(__FILE__)
    ]);

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("\n        INSERT INTO tbl_spoinc_bridge_intents (\n            idempotency_id,\n            partner_name,\n            project_id,\n            discord_id,\n            wallet,\n            route_key,\n            direction,\n            input_token,\n            output_token,\n            input_amount,\n            expected_output_amount,\n            minimum_output_amount,\n            dspoinc_amount,\n            spoinc_amount,\n            conversion_rate_dspoinc_per_spoinc,\n            latest_blockhash,\n            last_valid_block_height,\n            status,\n            gensuki_status,\n            narrrfs_status,\n            raw_request_json,\n            expires_at\n        ) VALUES (\n            :idempotency_id,\n            :partner_name,\n            :project_id,\n            :discord_id,\n            :wallet,\n            :route_key,\n            :direction,\n            :input_token,\n            :output_token,\n            :input_amount,\n            :expected_output_amount,\n            :minimum_output_amount,\n            :dspoinc_amount,\n            :spoinc_amount,\n            :conversion_rate,\n            :latest_blockhash,\n            :last_valid_block_height,\n            :status,\n            :gensuki_status,\n            :narrrfs_status,\n            :raw_request_json,\n            :expires_at\n        )\n    ");

    $insertStmt->execute([
        ':idempotency_id' => $idempotencyId,
        ':partner_name' => SPOINC_BRIDGE_PARTNER_NAME,
        ':project_id' => $projectId,
        ':discord_id' => $userId,
        ':wallet' => $wallet,
        ':route_key' => SPOINC_BUY_ROUTE_KEY_SOL,
        ':direction' => SPOINC_BUY_DIRECTION_SOL,
        ':input_token' => SPOINC_BUY_INPUT_TOKEN_SOL,
        ':output_token' => SPOINC_BUY_OUTPUT_TOKEN,
        ':input_amount' => $paymentAmount,
        ':expected_output_amount' => '0',
        ':minimum_output_amount' => '0',
        ':dspoinc_amount' => 0,
        ':spoinc_amount' => '0',
        ':conversion_rate' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        ':latest_blockhash' => $blockhashResult['blockhash'],
        ':last_valid_block_height' => $blockhashResult['last_valid_block_height'],
        ':status' => 'buy_pending',
        ':gensuki_status' => 'buy_request_created',
        ':narrrfs_status' => 'awaiting_gensuki_buy_payload',
        ':raw_request_json' => $rawRequestJson,
        ':expires_at' => $expiresAt
    ]);

    $intentId = (int)$pdo->lastInsertId();
    $pdo->commit();

    $gensukiResult = spoinc_bridge_post_gensuki_json($gensukiEndpoint, $apiKey, $gensukiBuyRequest);
    $gensukiJson = $gensukiResult['json'] ?? [];
    $gensukiSuccess = !empty($gensukiResult['success']) && !empty($gensukiJson['success']);
    $transactionPayload = is_array($gensukiJson) ? read_gensuki_buy_value($gensukiJson, 'transaction') : '';
    $returnedIdempotencyId = is_array($gensukiJson) ? read_gensuki_buy_value($gensukiJson, 'idempotencyId') : '';
    $lookupTableAddress = is_array($gensukiJson) ? (read_gensuki_buy_value($gensukiJson, 'lookupTableAddress') ?: read_gensuki_buy_value($gensukiJson, 'lutAddress')) : '';

    $status = $gensukiSuccess && $transactionPayload !== '' ? 'buy_payload_ready' : 'buy_failed';
    $gensukiStatus = $gensukiSuccess && $transactionPayload !== '' ? 'buy_payload_ready' : 'buy_request_failed';
    $narrrfsStatus = $gensukiSuccess && $transactionPayload !== '' ? 'awaiting_user_signature_no_ledger_movement' : 'buy_failed_no_ledger_movement';

    $responseData = [
        'intent_id' => $intentId,
        'idempotency_id' => $idempotencyId,
        'gensuki_idempotency_id' => $returnedIdempotencyId,
        'discord_id' => $userId,
        'route_key' => SPOINC_BUY_ROUTE_KEY_SOL,
        'wallet' => $wallet,
        'payment_token' => SPOINC_BUY_INPUT_TOKEN_SOL,
        'payment_token_address' => $paymentTokenAddress,
        'payment_amount' => $paymentAmount,
        'output_token' => SPOINC_BUY_OUTPUT_TOKEN,
        'status' => $status,
        'gensuki_status' => $gensukiStatus,
        'narrrfs_status' => $narrrfsStatus,
        'transaction' => $transactionPayload,
        'lookup_table_address' => $lookupTableAddress,
        'requires_phantom_signature' => $transactionPayload !== '',
        'expires_at' => $expiresAt,
                'safety' => [
            'private_tester_only' => false,
            'public_route_gate_checked' => true,
            'route_key' => SPOINC_BUY_ROUTE_KEY_SOL,
            'dspoinc_credited' => false,
            'dspoinc_debited' => false,
            'ledger_movement' => false,
            'gensuki_key_exposed' => false,
            'min_sol' => SPOINC_BUY_MIN_SOL_LAUNCH,
            'max_sol' => SPOINC_BUY_MAX_SOL_LAUNCH
        ]
    ];

    $pdo->beginTransaction();
    $updateStmt = $pdo->prepare("\n        UPDATE tbl_spoinc_bridge_intents\n        SET status = :status,\n            gensuki_status = :gensuki_status,\n            narrrfs_status = :narrrfs_status,\n            transaction_payload_hash = :transaction_payload_hash,\n            lookup_table_account = :lookup_table_account,\n            raw_response_json = :raw_response_json,\n            error_message = :error_message,\n            updated_at = CURRENT_TIMESTAMP\n        WHERE intent_id = :intent_id\n    ");

    $updateStmt->execute([
        ':status' => $status,
        ':gensuki_status' => $gensukiStatus,
        ':narrrfs_status' => $narrrfsStatus,
        ':transaction_payload_hash' => $transactionPayload !== '' ? hash('sha256', $transactionPayload) : null,
        ':lookup_table_account' => $lookupTableAddress !== '' ? $lookupTableAddress : null,
        ':raw_response_json' => json_encode([
            'safe_response' => $responseData,
            'gensuki_http_code' => $gensukiResult['http_code'] ?? null,
            'gensuki_response' => $gensukiJson,
            'gensuki_raw_body' => $gensukiResult['raw_body'] ?? '',
            'gensuki_error' => $gensukiResult['error'] ?? ''
        ]),
        ':error_message' => $gensukiSuccess ? null : ('Gensuki buy request failed. HTTP ' . (int)($gensukiResult['http_code'] ?? 0)),
        ':intent_id' => $intentId
    ]);
    $pdo->commit();

    if (!$gensukiSuccess || $transactionPayload === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki buy request failed or returned no transaction. No DSPOINC movement happened.',
            'data' => $responseData,
            'gensuki_http_code' => $gensukiResult['http_code'] ?? null,
            'gensuki_response' => $gensukiJson
        ], 502);
    }

    spoinc_bridge_json_response([
        'success' => true,
        'data' => $responseData
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('❌ SOL_TO_SPOINC Gensuki buy intent error: ' . $error->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Failed to create SOL_TO_SPOINC Gensuki buy intent.',
        'details' => $error->getMessage()
    ], 500);
}
