<?php
/**
 * SPOINC Bridge API — Create SPOINC → DSPOINC Gensuki Claim Intent.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint creates the Gensuki /claim transaction payload for users who
 * want to convert SPOINC into DSPOINC through the Narrrfs Swap Lab.
 *
 * Public users are allowed only when the global bridge config, settlement flag,
 * and the exact SPOINC_TO_DSPOINC route are opened in the database. Internal
 * testers can still use this endpoint before public activation.
 *
 * SPOINC → DSPOINC now means:
 * - Narrrfs creates a local pending intent.
 * - Narrrfs backend calls Gensuki /api/custom-token-presale/claim with spoincAmount.
 * - The frontend signs/sends the Gensuki-prepared transaction when returned.
 * - DSPOINC is credited only later by confirm-spoinc-to-dspoinc-deposit.php after
 *   the Gensuki /confirm route accepts status = complete.
 *
 * Safety:
 * - Public route gate required for normal users.
 * - Narrrf + justme internal testers still allowed before activation.
 * - SPOINC_TO_DSPOINC only.
 * - Max 500 SPOINC per launch conversion intent.
 * - No Gensuki key is exposed to the frontend.
 * - No DSPOINC credit here.
 * - No DSPOINC debit here.
 */

require_once __DIR__ . '/bridge-helpers.php';
require_once __DIR__ . '/bridge-tester-helpers.php';

spoinc_bridge_boot_json_api(['POST', 'OPTIONS']);

const SPOINC_TO_DSPOINC_DEPOSIT_ROUTE_KEY = 'SPOINC_TO_DSPOINC';
const SPOINC_TO_DSPOINC_DEPOSIT_DIRECTION = 'spoinc_to_dspoinc';
const SPOINC_TO_DSPOINC_DEPOSIT_INPUT_TOKEN = 'SPOINC';
const SPOINC_TO_DSPOINC_DEPOSIT_OUTPUT_TOKEN = 'DSPOINC';
const SPOINC_TO_DSPOINC_DEPOSIT_MAX_SPOINC = 500;
const SPOINC_TO_DSPOINC_DEPOSIT_EXPIRES_HOURS = 2;
const SPOINC_GENSUKI_CLAIM_PATH = '/api/custom-token-presale/claim';



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
 * Fetch a recent Solana blockhash server-side.
 *
 * Plain language for DEVS:
 * Browsers can hit CORS on public RPC. PHP calls it server-side so Swap Lab does
 * not fetch mainnet RPC directly.
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
        'id' => 'spoinc-claim-blockhash',
        'method' => 'getLatestBlockhash',
        'params' => [
            ['commitment' => 'confirmed']
        ]
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
 * Return a normalized SPOINC amount string with up to 4 decimals.
 *
 * Plain language for DEVS:
 * 10,000 DSPOINC = 1 SPOINC, so 0.0001 SPOINC = 1 DSPOINC.
 */
function normalize_spoinc_deposit_amount(string $amount): string
{
    $amount = trim($amount);

    if ($amount === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC amount is required.'
        ], 400);
    }

    if (!preg_match('/^\d+(\.\d{1,4})?$/', $amount)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC amount must be a positive number with max 4 decimals.'
        ], 400);
    }

    [$wholePart, $fractionPart] = array_pad(explode('.', $amount, 2), 2, '');

    $wholePart = ltrim($wholePart, '0');
    if ($wholePart === '') {
        $wholePart = '0';
    }

    $fractionPart = rtrim($fractionPart, '0');

    return $fractionPart === '' ? $wholePart : $wholePart . '.' . $fractionPart;
}

/**
 * Convert a normalized SPOINC amount into an exact DSPOINC integer.
 */
function calculate_dspoinc_from_spoinc_amount(string $normalizedAmount): int
{
    [$wholePart, $fractionPart] = array_pad(explode('.', $normalizedAmount, 2), 2, '');

    $wholeDspoinc = ((int)$wholePart) * SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC;
    $fractionDspoinc = (int)str_pad(substr($fractionPart, 0, 4), 4, '0');

    return $wholeDspoinc + $fractionDspoinc;
}

/**
 * Return true when the normalized amount is inside the private tester cap.
 */
function is_spoinc_deposit_amount_within_private_cap(string $normalizedAmount): bool
{
    $dspoincAmount = calculate_dspoinc_from_spoinc_amount($normalizedAmount);
    $maxDspoincAmount = SPOINC_TO_DSPOINC_DEPOSIT_MAX_SPOINC * SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC;

    return $dspoincAmount > 0 && $dspoincAmount <= $maxDspoincAmount;
}

/**
 * Load the SPOINC_TO_DSPOINC route row.
 */
function load_spoinc_to_dspoinc_route(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("\n        SELECT *\n        FROM tbl_spoinc_bridge_routes\n        WHERE route_key = ?\n        LIMIT 1\n    ");
    $stmt->execute([SPOINC_TO_DSPOINC_DEPOSIT_ROUTE_KEY]);

    $route = $stmt->fetch(PDO::FETCH_ASSOC);

    return $route ?: null;
}

/**
 * Create a unique idempotency id for one Gensuki claim intent.
 */
function create_spoinc_claim_idempotency_id(string $userId, string $wallet, string $amount): string
{
    $entropy = bin2hex(random_bytes(8));

    return sprintf(
        'spoinc-claim-%s-%s-%s-%s',
        $userId,
        substr(hash('sha256', $wallet), 0, 10),
        str_replace('.', '_', $amount),
        $entropy
    );
}

/**
 * Detect how Gensuki answered /claim so the frontend knows the next step.
 */
function detect_gensuki_claim_response_mode(array $gensukiJson): string
{
    if (isset($gensukiJson['transaction']) && trim((string)$gensukiJson['transaction']) !== '') {
        return 'transaction';
    }

    if (isset($gensukiJson['data']['transaction']) && trim((string)$gensukiJson['data']['transaction']) !== '') {
        return 'transaction';
    }

    if (isset($gensukiJson['signature']) && trim((string)$gensukiJson['signature']) !== '') {
        return 'signature';
    }

    if (isset($gensukiJson['data']['signature']) && trim((string)$gensukiJson['data']['signature']) !== '') {
        return 'signature';
    }

    return 'unknown';
}

/**
 * Return a safe value from a nested Gensuki response.
 */
function read_gensuki_claim_value(array $gensukiJson, string $key): string
{
    $direct = trim((string)($gensukiJson[$key] ?? ''));
    if ($direct !== '') {
        return $direct;
    }

    return trim((string)($gensukiJson['data'][$key] ?? ''));
}

try {
       $requestData = spoinc_bridge_get_request_data();
    $pdo = spoinc_bridge_open_database();
    $userId = spoinc_bridge_require_public_route_access(
        $pdo,
        $requestData,
        SPOINC_TO_DSPOINC_DEPOSIT_ROUTE_KEY,
        true
    );

    $wallet = trim((string)($requestData['wallet'] ?? ''));
    $rawAmount = trim((string)($requestData['amount'] ?? ($requestData['spoinc_amount'] ?? '')));

    if (!spoinc_bridge_is_valid_solana_address($wallet)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'A valid Solana wallet is required.'
        ], 400);
    }

    $spoincAmount = normalize_spoinc_deposit_amount($rawAmount);

    if (!is_spoinc_deposit_amount_within_private_cap($spoincAmount)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC_TO_DSPOINC amount must be greater than 0 and max 500 SPOINC.',
            'max_spoinc' => SPOINC_TO_DSPOINC_DEPOSIT_MAX_SPOINC
        ], 400);
    }

        $dspoincAmount = calculate_dspoinc_from_spoinc_amount($spoincAmount);
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

    $tokenMint = trim((string)($config['token_mint'] ?? ''));
    if (!spoinc_bridge_is_valid_solana_address($tokenMint)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC token mint is not configured correctly.'
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

    $route = load_spoinc_to_dspoinc_route($pdo);
    if (!$route) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'SPOINC_TO_DSPOINC route is missing.'
        ], 500);
    }

    $blockhashResult = spoinc_bridge_fetch_latest_blockhash();
    if (empty($blockhashResult['success'])) {
        spoinc_bridge_json_response($blockhashResult, 502);
    }

    $idempotencyId = create_spoinc_claim_idempotency_id($userId, $wallet, $spoincAmount);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + (SPOINC_TO_DSPOINC_DEPOSIT_EXPIRES_HOURS * 3600));
    $gensukiEndpoint = spoinc_bridge_build_gensuki_endpoint($config, SPOINC_GENSUKI_CLAIM_PATH);

    $gensukiClaimRequest = [
        'userAddress' => $wallet,
        'projectId' => $projectId,
        'latestBlockhash' => $blockhashResult['blockhash'],
        'spoincAmount' => (float)$spoincAmount,
        'idempotencyId' => $idempotencyId
    ];

    $rawRequestJson = json_encode([
        'request_type' => 'public_spoinc_to_dspoinc_gensuki_claim_intent',
        'user_id' => $userId,
        'wallet' => $wallet,
        'spoinc_amount' => $spoincAmount,
        'dspoinc_amount' => $dspoincAmount,
        'token_mint' => $tokenMint,
        'gensuki_endpoint' => $gensukiEndpoint,
        'gensuki_claim_request' => $gensukiClaimRequest,
        'cap_spoinc' => SPOINC_TO_DSPOINC_DEPOSIT_MAX_SPOINC,
        'created_by_endpoint' => basename(__FILE__)
    ]);

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("\n        INSERT INTO tbl_spoinc_bridge_intents (\n            idempotency_id,\n            partner_name,\n            project_id,\n            discord_id,\n            wallet,\n            route_key,\n            direction,\n            input_token,\n            output_token,\n            input_amount,\n            expected_output_amount,\n            minimum_output_amount,\n            dspoinc_amount,\n            spoinc_amount,\n            conversion_rate_dspoinc_per_spoinc,\n            status,\n            gensuki_status,\n            narrrfs_status,\n            raw_request_json,\n            expires_at\n        ) VALUES (\n            :idempotency_id,\n            :partner_name,\n            :project_id,\n            :discord_id,\n            :wallet,\n            :route_key,\n            :direction,\n            :input_token,\n            :output_token,\n            :input_amount,\n            :expected_output_amount,\n            :minimum_output_amount,\n            :dspoinc_amount,\n            :spoinc_amount,\n            :conversion_rate,\n            :status,\n            :gensuki_status,\n            :narrrfs_status,\n            :raw_request_json,\n            :expires_at\n        )\n    ");

    $insertStmt->execute([
        ':idempotency_id' => $idempotencyId,
        ':partner_name' => SPOINC_BRIDGE_PARTNER_NAME,
        ':project_id' => $projectId,
        ':discord_id' => $userId,
        ':wallet' => $wallet,
        ':route_key' => SPOINC_TO_DSPOINC_DEPOSIT_ROUTE_KEY,
        ':direction' => SPOINC_TO_DSPOINC_DEPOSIT_DIRECTION,
        ':input_token' => SPOINC_TO_DSPOINC_DEPOSIT_INPUT_TOKEN,
        ':output_token' => SPOINC_TO_DSPOINC_DEPOSIT_OUTPUT_TOKEN,
        ':input_amount' => $spoincAmount,
        ':expected_output_amount' => (string)$dspoincAmount,
        ':minimum_output_amount' => (string)$dspoincAmount,
        ':dspoinc_amount' => $dspoincAmount,
        ':spoinc_amount' => $spoincAmount,
        ':conversion_rate' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        ':status' => 'claim_pending',
        ':gensuki_status' => 'claim_request_created',
        ':narrrfs_status' => 'awaiting_gensuki_claim_payload',
        ':raw_request_json' => $rawRequestJson,
        ':expires_at' => $expiresAt
    ]);

    $intentId = (int)$pdo->lastInsertId();
    $pdo->commit();

    $gensukiResult = spoinc_bridge_post_gensuki_json($gensukiEndpoint, $apiKey, $gensukiClaimRequest);
    $gensukiJson = $gensukiResult['json'] ?? [];
    $gensukiSuccess = !empty($gensukiResult['success']) && !empty($gensukiJson['success']);
    $responseMode = is_array($gensukiJson) ? detect_gensuki_claim_response_mode($gensukiJson) : 'unknown';
    $transactionPayload = is_array($gensukiJson) ? read_gensuki_claim_value($gensukiJson, 'transaction') : '';
    $signature = is_array($gensukiJson) ? read_gensuki_claim_value($gensukiJson, 'signature') : '';
    $returnedIdempotencyId = is_array($gensukiJson) ? read_gensuki_claim_value($gensukiJson, 'idempotencyId') : '';
    $lookupTableAddress = is_array($gensukiJson) ? (read_gensuki_claim_value($gensukiJson, 'lookupTableAddress') ?: read_gensuki_claim_value($gensukiJson, 'lutAddress')) : '';

    $status = $gensukiSuccess ? 'claim_payload_ready' : 'claim_failed';
    $gensukiStatus = $gensukiSuccess ? 'claim_payload_ready' : 'claim_request_failed';
    $narrrfsStatus = $gensukiSuccess ? ($responseMode === 'signature' ? 'awaiting_gensuki_confirm' : 'awaiting_user_signature') : 'claim_failed_no_ledger_movement';

    $responseData = [
        'intent_id' => $intentId,
        'idempotency_id' => $idempotencyId,
        'gensuki_idempotency_id' => $returnedIdempotencyId,
        'discord_id' => $userId,
        'route_key' => SPOINC_TO_DSPOINC_DEPOSIT_ROUTE_KEY,
        'wallet' => $wallet,
        'token_mint' => $tokenMint,
        'spoinc_amount' => $spoincAmount,
        'expected_dspoinc_amount' => $dspoincAmount,
        'conversion_rate_dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        'status' => $status,
        'gensuki_status' => $gensukiStatus,
        'narrrfs_status' => $narrrfsStatus,
        'gensuki_response_mode' => $responseMode,
        'transaction' => $transactionPayload,
        'signature' => $signature,
        'lookup_table_address' => $lookupTableAddress,
        'requires_phantom_signature' => $responseMode === 'transaction',
        'expires_at' => $expiresAt,
        'instructions' => [
            'step_1' => 'Use the returned Gensuki transaction payload if present.',
            'step_2' => 'User signs/broadcasts with Phantom.',
            'step_3' => 'Send the returned transaction hash/signature to the confirm endpoint.',
            'step_4' => 'DSPOINC is credited only after Gensuki /confirm accepts status complete.'
        ],
                'safety' => [
            'private_tester_only' => false,
            'public_route_gate_checked' => true,
            'route_key' => SPOINC_TO_DSPOINC_DEPOSIT_ROUTE_KEY,
            'requires_settlement_enabled' => true,
            'dspoinc_credited' => false,
            'dspoinc_debited' => false,
            'settled' => false,
            'max_spoinc' => SPOINC_TO_DSPOINC_DEPOSIT_MAX_SPOINC,
            'gensuki_key_exposed' => false
        ]
    ];

    $pdo->beginTransaction();

    $updateResponseStmt = $pdo->prepare("\n        UPDATE tbl_spoinc_bridge_intents\n        SET status = :status,\n            gensuki_status = :gensuki_status,\n            narrrfs_status = :narrrfs_status,\n            raw_response_json = :raw_response_json,\n            error_message = :error_message,\n            updated_at = CURRENT_TIMESTAMP\n        WHERE intent_id = :intent_id\n    ");

    $updateResponseStmt->execute([
        ':status' => $status,
        ':gensuki_status' => $gensukiStatus,
        ':narrrfs_status' => $narrrfsStatus,
        ':raw_response_json' => json_encode([
            'safe_response' => $responseData,
            'gensuki_http_code' => $gensukiResult['http_code'] ?? null,
            'gensuki_response' => $gensukiJson,
            'gensuki_raw_body' => $gensukiResult['raw_body'] ?? '',
            'gensuki_error' => $gensukiResult['error'] ?? ''
        ]),
        ':error_message' => $gensukiSuccess ? null : ('Gensuki claim request failed. HTTP ' . (int)($gensukiResult['http_code'] ?? 0)),
        ':intent_id' => $intentId
    ]);

    $pdo->commit();

    if (!$gensukiSuccess) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Gensuki claim request failed. No DSPOINC was credited.',
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

    error_log('❌ SPOINC_TO_DSPOINC Gensuki claim intent error: ' . $error->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Failed to create SPOINC_TO_DSPOINC Gensuki claim intent.',
        'details' => $error->getMessage()
    ], 500);
}
