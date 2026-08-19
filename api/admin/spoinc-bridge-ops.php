<?php
/**
 * SPOINC Bridge Admin Operations API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint is the protected admin investigation cockpit for the
 * SPOINC / DSPOINC / Gensuki bridge.
 *
 * Phase 1C keeps bridge investigation read-only except for one narrow,
 * protected fail-only Gensuki reconciliation action:
 * - It can report endpoint health and pending reconciliation data.
 * - It can check Solana transaction signature status.
 * - It can run authoritative dry-run checks before the fail-only action.
 * - It may call Gensuki /confirm only with status failed after all guards pass.
 * - It must not call Gensuki complete.
 * - It must not credit or debit DSPOINC.
 * - It must not settle or mutate Narrrfs bridge/economy rows.
 * - Protected apply attempts write only the dedicated admin-operation audit table.
 * - It must not expose the Gensuki API key.
 */

if (function_exists('ob_end_clean')) {
    @ob_end_clean();
}

if (function_exists('ob_clean')) {
    @ob_clean();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

const SPOINC_BRIDGE_OPS_ENDPOINT_NAME = 'spoinc-bridge-ops';
const SPOINC_BRIDGE_OPS_PHASE = 'phase_1c_fail_only_apply';
const SPOINC_BRIDGE_OPS_LOCAL_ADMIN_ID = '328601656659017732';
const SPOINC_BRIDGE_OPS_SOLANA_RPC_FALLBACK = 'https://api.mainnet-beta.solana.com';
const SPOINC_BRIDGE_OPS_PROJECT_ID = '7e04b38a-7bd4-4fab-acc4-dfa53a99b639';
const SPOINC_BRIDGE_OPS_GENSUKI_BASE_URL = 'https://app.gensuki.xyz';
const SPOINC_BRIDGE_OPS_GET_ALL_TRANSACTIONS_PATH = '/api/custom-token-presale/getAllTransactions';
const SPOINC_BRIDGE_OPS_CONFIRM_PATH = '/api/custom-token-presale/confirm';
const SPOINC_BRIDGE_OPS_MAX_GENSUKI_PAGES = 50;
const SPOINC_BRIDGE_OPS_CONFIRM_FAILED_PHRASE = 'CONFIRM GENSUKI FAILED';
const SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE = 'tbl_spoinc_bridge_admin_operations';
const SPOINC_BRIDGE_OPS_FAIL_OPERATION_TYPE = 'fail_local_not_found';

/**
 * Return JSON and stop execution.
 */
function spoinc_bridge_ops_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);

    if (function_exists('ob_end_clean')) {
        @ob_end_clean();
    }

    if (function_exists('ob_clean')) {
        @ob_clean();
    }

    echo json_encode($payload);
    exit;
}

/**
 * Return true when this request is running on local development.
 */
function spoinc_bridge_ops_is_localhost(): bool
{
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));

    return $host === 'localhost'
        || $host === '127.0.0.1'
        || str_starts_with($host, 'localhost:')
        || str_starts_with($host, '127.0.0.1:');
}

/**
 * Return one request header value without assuming server-specific casing.
 */
function spoinc_bridge_ops_header(string $name): string
{
    $targetName = strtolower($name);

    if (function_exists('getallheaders')) {
        $headers = getallheaders();

        foreach ($headers as $headerName => $headerValue) {
            if (strtolower((string)$headerName) === $targetName) {
                return trim((string)$headerValue);
            }
        }
    }

    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

    return trim((string)($_SERVER[$serverKey] ?? ''));
}

/**
 * Protect this admin endpoint.
 *
 * Plain language for DEVS FOR DECADES:
 * Localhost stays open for safe XAMPP testing. Production requires either the
 * existing admin session flag or Narrrf's Discord session id. This is Phase 1A
 * only; before write operations are added, role checks should be aligned with
 * the live admin auth helper used by the rest of the admin interface.
 */
function spoinc_bridge_ops_require_admin(): array
{
    if (spoinc_bridge_ops_is_localhost()) {
        return [
            'admin_id' => SPOINC_BRIDGE_OPS_LOCAL_ADMIN_ID,
            'auth_mode' => 'localhost_bypass'
        ];
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sessionAdminLoggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;
    $sessionDiscordId = trim((string)($_SESSION['discord_id'] ?? ''));
    $cookieDiscordId = trim((string)($_COOKIE['discord_user_id'] ?? ''));
    $headerAdminId = spoinc_bridge_ops_header('X-Admin-Id');

    if ($sessionAdminLoggedIn) {
        return [
            'admin_id' => $sessionDiscordId ?: $headerAdminId ?: 'session_admin',
            'auth_mode' => 'admin_session'
        ];
    }

    if ($sessionDiscordId === SPOINC_BRIDGE_OPS_LOCAL_ADMIN_ID) {
        return [
            'admin_id' => $sessionDiscordId,
            'auth_mode' => 'narrrf_session'
        ];
    }

    if ($cookieDiscordId === SPOINC_BRIDGE_OPS_LOCAL_ADMIN_ID) {
        return [
            'admin_id' => $cookieDiscordId,
            'auth_mode' => 'narrrf_cookie'
        ];
    }

    spoinc_bridge_ops_json([
        'success' => false,
        'error' => 'Unauthorized - admin access required'
    ], 401);
}

/**
 * Read JSON request input once.
 */
function spoinc_bridge_ops_read_json_input(): array
{
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode((string)$rawInput, true);

    if (is_array($jsonInput)) {
        return $jsonInput;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return [];
}

/**
 * Return a clean string from request data or query parameters.
 */
function spoinc_bridge_ops_request_string(array $data, string $key, int $maximumLength = 500): string
{
    $value = '';

    if (array_key_exists($key, $data)) {
        $value = (string)$data[$key];
    } elseif (array_key_exists($key, $_GET)) {
        $value = (string)$_GET[$key];
    }

    $value = trim($value);

    if (strlen($value) > $maximumLength) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => "Request value is too long: {$key}"
        ], 400);
    }

    return $value;
}

/**
 * Return the current action name.
 */
function spoinc_bridge_ops_get_action(array $data): string
{
    $action = spoinc_bridge_ops_request_string($data, 'action', 100);

    if ($action === '') {
        $action = 'get_health';
    }

    return strtolower($action);
}

/**
 * Validate a Solana transaction signature shape.
 *
 * Plain language for DEVS FOR DECADES:
 * Wallet addresses and transaction signatures are both base58, but transaction
 * signatures are normally longer. We accept 64-128 base58 characters here so
 * admins can check signatures without accidentally sending arbitrary text.
 */
function spoinc_bridge_ops_is_valid_solana_signature(string $value): bool
{
    $trimmedValue = trim($value);

    if ($trimmedValue === '') {
        return false;
    }

    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,128}$/', $trimmedValue);
}

/**
 * Return the Solana RPC URL used for admin investigation checks.
 */
function spoinc_bridge_ops_get_solana_rpc_url(): string
{
    $candidateNames = [
        'SOLANA_RPC_URL',
        'HELIUS_RPC_URL',
        'QUICKNODE_SOLANA_RPC_URL'
    ];

    foreach ($candidateNames as $candidateName) {
        $value = trim((string)getenv($candidateName));

        if ($value !== '') {
            return $value;
        }
    }

    return SPOINC_BRIDGE_OPS_SOLANA_RPC_FALLBACK;
}

/**
 * POST JSON to Solana RPC.
 */
function spoinc_bridge_ops_post_json_rpc(string $url, array $payload): array
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
        CURLOPT_TIMEOUT => 30,
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

    return [
        'success' => $curlError === '' && $httpCode >= 200 && $httpCode < 300 && is_array($json),
        'http_code' => $httpCode,
        'json' => is_array($json) ? $json : null,
        'raw_body' => $rawBody,
        'error' => $curlError
    ];
}

/**
 * Convert a Solana getSignatureStatuses response into one admin-safe state.
 */
function spoinc_bridge_ops_classify_solana_status(?array $status): array
{
    if ($status === null) {
        return [
            'state' => 'not_found',
            'safe_label' => '❌ value:null — failed candidate only after clean RPC response',
            'can_complete' => false,
            'can_fail' => true,
            'needs_manual_review' => false
        ];
    }

    $confirmationStatus = strtolower(trim((string)($status['confirmationStatus'] ?? '')));
    $err = $status['err'] ?? null;

    if ($err !== null) {
        return [
            'state' => 'failed_on_chain',
            'safe_label' => '❌ Solana transaction failed — failed candidate',
            'can_complete' => false,
            'can_fail' => true,
            'needs_manual_review' => false
        ];
    }

    if ($confirmationStatus === 'confirmed' || $confirmationStatus === 'finalized') {
        return [
            'state' => 'complete_on_chain',
            'safe_label' => '✅ confirmed/finalized and err:null — complete candidate',
            'can_complete' => true,
            'can_fail' => false,
            'needs_manual_review' => false
        ];
    }

    return [
        'state' => 'pending_on_chain',
        'safe_label' => '⏳ pending — retry later',
        'can_complete' => false,
        'can_fail' => false,
        'needs_manual_review' => true
    ];
}

/**
 * Return endpoint health for the admin interface.
 */
function spoinc_bridge_ops_get_health(array $admin): void
{
    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'endpoint' => SPOINC_BRIDGE_OPS_ENDPOINT_NAME,
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'mode' => 'read_only_plus_protected_fail_only_apply',
            'admin_id' => $admin['admin_id'],
            'auth_mode' => $admin['auth_mode'],
            'server_time_utc' => gmdate('Y-m-d H:i:s'),
            'safety' => [
                'gensuki_confirm_enabled' => true,
                'gensuki_confirm_scope' => 'failed_only',
                'gensuki_complete_confirm_enabled' => false,
                'dspoinc_credit_enabled' => false,
                'dspoinc_debit_enabled' => false,
                'db_write_enabled' => true,
                'db_write_scope' => SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE . '_only',
                'admin_operation_log_write_enabled' => true,
                'bridge_economy_db_write_enabled' => false,
                'local_bridge_settlement_enabled' => false,
                'route_mutation_enabled' => false,
                'rpc_error_auto_fail_enabled' => false
            ],
            'available_actions' => [
                'get_health',
                'get_gensuki_pending_audit',
                'check_solana_signature',
                'verify_failed_cleanup',
                'verify_complete_settlement',
                'dry_run_fail_local_not_found',
                'dry_run_fail_gensuki_only',
                'dry_run_complete_gensuki_only',
                'dry_run_settle_spoinc_claim',
                'apply_fail_local_not_found',
                'get_operation_log'
            ]
        ]
    ]);
}

/**
 * Check one Solana signature using getSignatureStatuses.
 */
function spoinc_bridge_ops_check_solana_signature(array $data): void
{
    $signature = spoinc_bridge_ops_request_string($data, 'signature', 140);

    if (!spoinc_bridge_ops_is_valid_solana_signature($signature)) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Invalid Solana signature format. Expected base58 transaction signature, 64-128 characters.'
        ], 400);
    }

    $rpcUrl = spoinc_bridge_ops_get_solana_rpc_url();

    $rpcPayload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getSignatureStatuses',
        'params' => [
            [$signature],
            ['searchTransactionHistory' => true]
        ]
    ];

    $rpcResponse = spoinc_bridge_ops_post_json_rpc($rpcUrl, $rpcPayload);

    if (!$rpcResponse['success']) {
        spoinc_bridge_ops_json([
            'success' => true,
            'data' => [
                'signature' => $signature,
                'state' => 'rpc_error',
                'safe_label' => '⚠️ rpc_error — do not touch',
                'can_complete' => false,
                'can_fail' => false,
                'needs_manual_review' => true,
                'http_code' => $rpcResponse['http_code'],
                'error' => $rpcResponse['error'] ?: 'Solana RPC request failed.',
                'raw_preview' => substr((string)$rpcResponse['raw_body'], 0, 500)
            ]
        ]);
    }

    $status = $rpcResponse['json']['result']['value'][0] ?? null;
    $classification = spoinc_bridge_ops_classify_solana_status(is_array($status) ? $status : null);

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'signature' => $signature,
            'state' => $classification['state'],
            'safe_label' => $classification['safe_label'],
            'can_complete' => $classification['can_complete'],
            'can_fail' => $classification['can_fail'],
            'needs_manual_review' => $classification['needs_manual_review'],
            'confirmationStatus' => is_array($status) ? ($status['confirmationStatus'] ?? null) : null,
            'err' => is_array($status) ? ($status['err'] ?? null) : null,
            'slot' => is_array($status) ? ($status['slot'] ?? null) : null,
            'confirmations' => is_array($status) ? ($status['confirmations'] ?? null) : null,
            'raw_status_preview' => is_array($status) ? $status : null
        ]
    ]);
}

/**
 * Return a safe scaffold for future failed-cleanup verification.
 *
 * TODO: Wire this to verified DB schema after we inspect the live/local bridge
 * tables. This must remain read-only and must never credit DSPOINC.
 */
function spoinc_bridge_ops_verify_failed_cleanup(array $data): void
{
    $intentId = spoinc_bridge_ops_request_string($data, 'intent_id', 80);
    $signature = spoinc_bridge_ops_request_string($data, 'signature', 140);

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'action' => 'verify_failed_cleanup',
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'implemented' => false,
            'intent_id' => $intentId,
            'signature' => $signature,
            'result_status' => 'schema_verification_required',
            'checks_planned' => [
                'intent is marked failed',
                'no tbl_user_scores credit exists',
                'no tbl_score_adjustments credit exists',
                'no bridge ledger credit exists',
                'transaction hash is not settled elsewhere'
            ],
            'safety' => 'Read-only scaffold. No database writes are performed.'
        ]
    ]);
}

/**
 * Return a safe scaffold for future complete-settlement verification.
 *
 * TODO: Wire this to verified DB schema after we inspect the live/local bridge
 * tables. This must remain read-only and must never create settlement rows.
 */
function spoinc_bridge_ops_verify_complete_settlement(array $data): void
{
    $intentId = spoinc_bridge_ops_request_string($data, 'intent_id', 80);
    $signature = spoinc_bridge_ops_request_string($data, 'signature', 140);

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'action' => 'verify_complete_settlement',
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'implemented' => false,
            'intent_id' => $intentId,
            'signature' => $signature,
            'result_status' => 'schema_verification_required',
            'checks_planned' => [
                'intent is settled',
                'bridge transaction row exists',
                'score row exists',
                'score adjustment row exists',
                'bridge ledger audit exists',
                'amount matches 1 SPOINC = 10,000 DSPOINC',
                'transaction hash is unique'
            ],
            'safety' => 'Read-only scaffold. No database writes are performed.'
        ]
    ]);
}

/**
 * Return the first non-empty environment value from a list.
 */
function spoinc_bridge_ops_env_value(array $names): string
{
    foreach ($names as $name) {
        $value = trim((string)getenv($name));

        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Return the server-side Gensuki API key.
 *
 * Plain language for DEVS FOR DECADES:
 * The browser must never receive this key. The admin interface only calls this
 * Narrrfs endpoint, and this endpoint calls Gensuki from the server.
 */
function spoinc_bridge_ops_get_gensuki_api_key(): string
{
    $apiKey = spoinc_bridge_ops_env_value([
        'GENSUKI_SPOINC_API_KEY',
        'GENSUKI_OUTBOUND_API_KEY',
        'SPOINC_GENSUKI_API_KEY',
        'GENSUKI_API_KEY'
    ]);

    if ($apiKey !== '') {
        return $apiKey;
    }

    $localConfigPath = __DIR__ . '/../config/gensuki-outbound-local.php';

    if (spoinc_bridge_ops_is_localhost() && file_exists($localConfigPath)) {
        $localConfig = require $localConfigPath;

        if (is_array($localConfig)) {
            return trim((string)($localConfig['api_key'] ?? $localConfig['GENSUKI_API_KEY'] ?? ''));
        }
    }

    return '';
}

/**
 * Return the Gensuki API base URL.
 */
function spoinc_bridge_ops_get_gensuki_base_url(): string
{
    $configuredBaseUrl = spoinc_bridge_ops_env_value([
        'GENSUKI_BASE_URL',
        'GENSUKI_API_BASE_URL'
    ]);

    if ($configuredBaseUrl !== '') {
        return rtrim($configuredBaseUrl, '/');
    }

    return SPOINC_BRIDGE_OPS_GENSUKI_BASE_URL;
}

/**
 * Call one Gensuki JSON GET endpoint.
 */
function spoinc_bridge_ops_gensuki_get_json(string $path, array $query): array
{
    $apiKey = spoinc_bridge_ops_get_gensuki_api_key();

    if ($apiKey === '') {
        return [
            'success' => false,
            'http_code' => 0,
            'json' => null,
            'raw_body' => '',
            'error' => 'Gensuki API key is not configured on the server.'
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'http_code' => 0,
            'json' => null,
            'raw_body' => '',
            'error' => 'PHP cURL extension is not available.'
        ];
    }

    $url = spoinc_bridge_ops_get_gensuki_base_url()
        . $path
        . '?'
        . http_build_query($query);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'x-api-key: ' . $apiKey
        ]
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
 * Call one Gensuki JSON POST endpoint.
 *
 * Plain language for DEVS FOR DECADES:
 * This keeps the Gensuki API key server-side. It is used only after all safety
 * checks pass. Browser/admin HTML must never receive the key.
 */
function spoinc_bridge_ops_gensuki_post_json(string $path, array $payload): array
{
    $apiKey = spoinc_bridge_ops_get_gensuki_api_key();

    if ($apiKey === '') {
        return [
            'success' => false,
            'http_code' => 0,
            'json' => null,
            'raw_body' => '',
            'error' => 'Gensuki API key is not configured on the server.'
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'http_code' => 0,
            'json' => null,
            'raw_body' => '',
            'error' => 'PHP cURL extension is not available.'
        ];
    }

    $url = spoinc_bridge_ops_get_gensuki_base_url() . $path;
    $encodedPayload = json_encode($payload);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => $encodedPayload
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
        'error' => $curlError,
        'sent_payload' => $payload
    ];
}

/**
 * Return true when an array looks like a list of rows.
 */
function spoinc_bridge_ops_is_row_list($value): bool
{
    if (!is_array($value)) {
        return false;
    }

    if ($value === []) {
        return true;
    }

    $firstValue = reset($value);

    return is_array($firstValue);
}

/**
 * Extract a transaction list from flexible Gensuki response shapes.
 */
function spoinc_bridge_ops_extract_gensuki_rows(array $json): array
{
    $candidates = [
        $json['data']['transactions'] ?? null,
        $json['data']['items'] ?? null,
        $json['data']['rows'] ?? null,
        $json['transactions'] ?? null,
        $json['items'] ?? null,
        $json['rows'] ?? null,
        $json['data'] ?? null
    ];

    foreach ($candidates as $candidate) {
        if (spoinc_bridge_ops_is_row_list($candidate)) {
            return $candidate;
        }
    }

    return [];
}

/**
 * Extract total pages from flexible Gensuki response shapes.
 */
function spoinc_bridge_ops_extract_total_pages(array $json): int
{
    $candidates = [
        $json['data']['totalPages'] ?? null,
        $json['data']['total_pages'] ?? null,
        $json['pagination']['totalPages'] ?? null,
        $json['pagination']['total_pages'] ?? null,
        $json['totalPages'] ?? null,
        $json['total_pages'] ?? null
    ];

    foreach ($candidates as $candidate) {
        if (is_numeric($candidate)) {
            return max(1, (int)$candidate);
        }
    }

    return 1;
}

/**
 * Return the first non-empty value from a transaction row.
 */
function spoinc_bridge_ops_first_row_value(array $row, array $keys): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $row)) {
            continue;
        }

        $value = trim((string)$row[$key]);

        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Extract a Solana signature from flexible Gensuki row field names.
 */
function spoinc_bridge_ops_extract_signature_from_row(array $row): string
{
    return spoinc_bridge_ops_first_row_value($row, [
        'transactionHash',
        'transaction_hash',
        'txHash',
        'tx_hash',
        'signature',
        'solanaSignature',
        'solana_signature',
        'hash'
    ]);
}

/**
 * Return a normalized status from a Gensuki transaction row.
 */
function spoinc_bridge_ops_extract_status_from_row(array $row): string
{
    return strtolower(spoinc_bridge_ops_first_row_value($row, [
        'status',
        'transactionStatus',
        'transaction_status',
        'state'
    ]));
}

/**
 * Return true when a Gensuki status should be treated as pending / unresolved.
 */
function spoinc_bridge_ops_is_pending_status(string $status): bool
{
    if ($status === '') {
        return true;
    }

    return in_array($status, [
        'pending',
        'created',
        'processing',
        'submitted',
        'signature_pending',
        'confirm_pending',
        'awaiting_confirm',
        'awaiting_confirmation'
    ], true);
}

/**
 * Return the local SQLite DB path when available.
 */
function spoinc_bridge_ops_get_local_db_path(): string
{
    $candidatePaths = [
        __DIR__ . '/../../db/narrrf_world.sqlite',
        '/var/www/html/db/narrrf_world.sqlite',
        '/data/narrrf_world.sqlite'
    ];

    foreach ($candidatePaths as $candidatePath) {
        if (file_exists($candidatePath)) {
            return $candidatePath;
        }
    }

    return '';
}

/**
 * Return true when a SQLite table exists.
 */
function spoinc_bridge_ops_sqlite_table_exists(SQLite3 $db, string $tableName): bool
{
    $statement = $db->prepare(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table_name LIMIT 1"
    );

    if (!$statement) {
        return false;
    }

    $statement->bindValue(':table_name', $tableName, SQLITE3_TEXT);
    $result = $statement->execute();

    if (!$result) {
        return false;
    }

    $row = $result->fetchArray(SQLITE3_ASSOC);

    return is_array($row) && ($row['name'] ?? '') === $tableName;
}

/**
 * Return column names for one SQLite table.
 */
function spoinc_bridge_ops_sqlite_columns(SQLite3 $db, string $tableName): array
{
    $safeTableName = str_replace('"', '""', $tableName);
    $result = $db->query('PRAGMA table_info("' . $safeTableName . '")');

    if (!$result) {
        return [];
    }

    $columns = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $name = trim((string)($row['name'] ?? ''));

        if ($name !== '') {
            $columns[] = $name;
        }
    }

    return $columns;
}

/**
 * Find a local Narrrfs bridge row by transaction signature or idempotency id.
 *
 * Plain language for DEVS FOR DECADES:
 * This is read-only and schema-flexible. It searches only verified existing
 * columns from the local DB schema, so it does not assume undocumented fields.
 */
function spoinc_bridge_ops_find_local_bridge_match(string $signature, string $idempotencyId): array
{
    $dbPath = spoinc_bridge_ops_get_local_db_path();

    if ($dbPath === '' || !class_exists('SQLite3')) {
        return [
            'found' => false,
            'reason' => 'local_db_not_available'
        ];
    }

    $searchValues = [];

    if ($signature !== '') {
        $searchValues[] = $signature;
    }

    if ($idempotencyId !== '') {
        $searchValues[] = $idempotencyId;
    }

    if (empty($searchValues)) {
        return [
            'found' => false,
            'reason' => 'no_signature_or_idempotency'
        ];
    }

    $tables = [
        'tbl_spoinc_bridge_intents',
        'tbl_spoinc_bridge_transactions',
        'tbl_spoinc_bridge_ledger_audit'
    ];

    $candidateColumns = [
        'transaction_hash',
        'transactionHash',
        'tx_hash',
        'txHash',
        'signature',
        'solana_signature',
        'hash',
        'idempotency_id',
        'idempotencyId'
    ];

    try {
        $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);

        foreach ($tables as $tableName) {
            if (!spoinc_bridge_ops_sqlite_table_exists($db, $tableName)) {
                continue;
            }

            $columns = spoinc_bridge_ops_sqlite_columns($db, $tableName);
            $searchColumns = array_values(array_intersect($candidateColumns, $columns));

            if (empty($searchColumns)) {
                continue;
            }

            $whereParts = [];
            $bindIndex = 0;

            foreach ($searchColumns as $columnName) {
                foreach ($searchValues as $searchValue) {
                    $safeColumnName = str_replace('"', '""', $columnName);
                    $parameterName = ':value_' . $bindIndex;
                    $whereParts[] = 'CAST("' . $safeColumnName . '" AS TEXT) = ' . $parameterName;
                    $bindIndex++;
                }
            }

            $sql = 'SELECT * FROM "' . str_replace('"', '""', $tableName) . '" WHERE '
                . implode(' OR ', $whereParts)
                . ' ORDER BY rowid DESC LIMIT 1';

            $statement = $db->prepare($sql);

            if (!$statement) {
                continue;
            }

            $bindIndex = 0;
            foreach ($searchColumns as $columnName) {
                foreach ($searchValues as $searchValue) {
                    $statement->bindValue(':value_' . $bindIndex, $searchValue, SQLITE3_TEXT);
                    $bindIndex++;
                }
            }

            $result = $statement->execute();

            if (!$result) {
                continue;
            }

            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (is_array($row)) {
                return [
                    'found' => true,
                    'table' => $tableName,
                    'row' => array_slice($row, 0, 20, true)
                ];
            }
        }
    } catch (Throwable $error) {
        return [
            'found' => false,
            'reason' => 'local_db_error',
            'error' => spoinc_bridge_ops_is_localhost() ? $error->getMessage() : null
        ];
    }

    return [
        'found' => false,
        'reason' => 'no_local_match'
    ];
}

/**
 * Return one Solana signature state without ending the whole request.
 */
function spoinc_bridge_ops_get_solana_signature_state(string $signature): array
{
    if (!spoinc_bridge_ops_is_valid_solana_signature($signature)) {
        return [
            'state' => 'missing_or_invalid_signature',
            'safe_label' => '⚠️ missing or invalid signature — manual review',
            'can_complete' => false,
            'can_fail' => false,
            'needs_manual_review' => true
        ];
    }

    $rpcPayload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getSignatureStatuses',
        'params' => [
            [$signature],
            ['searchTransactionHistory' => true]
        ]
    ];

    $rpcResponse = spoinc_bridge_ops_post_json_rpc(
        spoinc_bridge_ops_get_solana_rpc_url(),
        $rpcPayload
    );

    if (!$rpcResponse['success']) {
        return [
            'state' => 'rpc_error',
            'safe_label' => '⚠️ rpc_error — do not touch',
            'can_complete' => false,
            'can_fail' => false,
            'needs_manual_review' => true,
            'http_code' => $rpcResponse['http_code'],
            'error' => $rpcResponse['error'] ?: 'Solana RPC request failed.'
        ];
    }

    $status = $rpcResponse['json']['result']['value'][0] ?? null;
    $classification = spoinc_bridge_ops_classify_solana_status(is_array($status) ? $status : null);

    return [
        'state' => $classification['state'],
        'safe_label' => $classification['safe_label'],
        'can_complete' => $classification['can_complete'],
        'can_fail' => $classification['can_fail'],
        'needs_manual_review' => $classification['needs_manual_review'],
        'confirmationStatus' => is_array($status) ? ($status['confirmationStatus'] ?? null) : null,
        'err' => is_array($status) ? ($status['err'] ?? null) : null,
        'slot' => is_array($status) ? ($status['slot'] ?? null) : null
    ];
}

/**
 * Classify one Gensuki pending row for the employee-friendly admin queue.
 */
function spoinc_bridge_ops_classify_gensuki_pending_row(array $row, bool $includeSolana): array
{
    $signature = spoinc_bridge_ops_extract_signature_from_row($row);
    $status = spoinc_bridge_ops_extract_status_from_row($row);
    $idempotencyId = spoinc_bridge_ops_first_row_value($row, [
        'idempotencyId',
        'idempotency_id'
    ]);

    $localMatch = spoinc_bridge_ops_find_local_bridge_match($signature, $idempotencyId);

    $solana = [
        'state' => $includeSolana ? 'not_checked' : 'not_checked',
        'safe_label' => $includeSolana ? 'Not checked.' : 'Click Recheck With Solana.',
        'can_complete' => false,
        'can_fail' => false,
        'needs_manual_review' => false
    ];

    if ($includeSolana) {
        $solana = spoinc_bridge_ops_get_solana_signature_state($signature);
    }

    $bucket = 'manual_review';

    if (!$includeSolana) {
        $bucket = $localMatch['found'] ? 'local_intent_matched' : 'gensuki_only_no_local_intent';
    } elseif (($solana['state'] ?? '') === 'complete_on_chain') {
        $bucket = $localMatch['found'] ? 'complete_candidate_local' : 'complete_candidate_gensuki_only';
    } elseif (in_array(($solana['state'] ?? ''), ['not_found', 'failed_on_chain'], true)) {
        $bucket = $localMatch['found'] ? 'failed_candidate_local' : 'failed_candidate_gensuki_only';
    } elseif (($solana['state'] ?? '') === 'rpc_error') {
        $bucket = 'rpc_error_manual_review';
    } elseif (($solana['state'] ?? '') === 'pending_on_chain') {
        $bucket = 'pending_on_chain';
    }

    return [
        'bucket' => $bucket,
        'gensuki_id' => spoinc_bridge_ops_first_row_value($row, ['id', '_id', 'transactionId', 'transaction_id']),
        'status' => $status,
        'signature' => $signature,
        'idempotency_id' => $idempotencyId,
        'amount' => spoinc_bridge_ops_first_row_value($row, [
            'amount',
            'paymentAmount',
            'payment_amount',
            'tokenAmount',
            'token_amount',
            'inputAmount',
            'outputAmount'
        ]),
        'route_hint' => spoinc_bridge_ops_first_row_value($row, [
            'route',
            'routeKey',
            'route_key',
            'type',
            'transactionType',
            'transaction_type'
        ]),
        'wallet' => spoinc_bridge_ops_first_row_value($row, [
            'buyerAddress',
            'buyer_address',
            'sellerAddress',
            'seller_address',
            'wallet',
            'userWallet',
            'user_wallet'
        ]),
        'local_match' => $localMatch,
        'solana' => $solana
    ];
}

/**
 * Read-only Gensuki pending audit.
 *
 * Plain language for DEVS FOR DECADES:
 * This replaces the manual emergency pending-audit scripts. It reads Gensuki
 * rows, classifies unresolved rows, optionally checks Solana, and returns a
 * queue for admins. It does not call Gensuki /confirm and does not write DB.
 */
function spoinc_bridge_ops_get_gensuki_pending_audit(array $data): void
{
    $includeSolanaRaw = strtolower(spoinc_bridge_ops_request_string($data, 'include_solana', 20));
    $includeSolana = in_array($includeSolanaRaw, ['1', 'true', 'yes', 'on'], true);

    $allPendingRows = [];
    $totalPages = 1;
    $gensukiErrors = [];

    for ($page = 1; $page <= min($totalPages, SPOINC_BRIDGE_OPS_MAX_GENSUKI_PAGES); $page++) {
        $response = spoinc_bridge_ops_gensuki_get_json(
            SPOINC_BRIDGE_OPS_GET_ALL_TRANSACTIONS_PATH,
            [
                'projectId' => SPOINC_BRIDGE_OPS_PROJECT_ID,
                'page' => $page
            ]
        );

        if (!$response['success']) {
            $gensukiErrors[] = [
                'page' => $page,
                'http_code' => $response['http_code'],
                'error' => $response['error'] ?: 'Gensuki request failed.',
                'raw_preview' => substr((string)$response['raw_body'], 0, 500)
            ];
            break;
        }

        $json = $response['json'] ?: [];
        $totalPages = max(1, spoinc_bridge_ops_extract_total_pages($json));
        $rows = spoinc_bridge_ops_extract_gensuki_rows($json);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $status = spoinc_bridge_ops_extract_status_from_row($row);

            if (!spoinc_bridge_ops_is_pending_status($status)) {
                continue;
            }

            $allPendingRows[] = spoinc_bridge_ops_classify_gensuki_pending_row($row, $includeSolana);
        }
    }

    $summary = [
        'total_pages' => $totalPages,
        'pending_count' => count($allPendingRows),
        'local_matched_count' => 0,
        'gensuki_only_count' => 0,
        'complete_candidate_count' => 0,
        'failed_candidate_count' => 0,
        'manual_review_count' => 0,
        'rpc_error_count' => 0,
        'include_solana' => $includeSolana
    ];

    foreach ($allPendingRows as $row) {
        $bucket = (string)($row['bucket'] ?? '');

        if (!empty($row['local_match']['found'])) {
            $summary['local_matched_count']++;
        } else {
            $summary['gensuki_only_count']++;
        }

        if (str_starts_with($bucket, 'complete_candidate')) {
            $summary['complete_candidate_count']++;
        } elseif (str_starts_with($bucket, 'failed_candidate')) {
            $summary['failed_candidate_count']++;
        } elseif ($bucket === 'rpc_error_manual_review') {
            $summary['rpc_error_count']++;
            $summary['manual_review_count']++;
        } elseif (str_contains($bucket, 'manual_review')) {
            $summary['manual_review_count']++;
        }
    }

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'action' => 'get_gensuki_pending_audit',
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'mode' => 'read_only',
            'summary' => $summary,
            'rows' => $allPendingRows,
            'gensuki_errors' => $gensukiErrors,
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'rpc_error_auto_failed' => false
            ]
        ]
    ]);
}

/**
 * Find one already-classified pending audit row by signature and optional intent id.
 *
 * Plain language for DEVS FOR DECADES:
 * This reuses the same pending audit path that powers the admin screen. The
 * apply action must never trust only the browser form. It must re-read Gensuki,
 * recheck Solana, and confirm the local Narrrfs intent match on the server.
 */
function spoinc_bridge_ops_find_pending_audit_row_for_apply(
    string $signature,
    string $intentId
): array {
    $pendingRows = [];
    $totalPages = 1;
    $gensukiErrors = [];

    for ($page = 1; $page <= min($totalPages, SPOINC_BRIDGE_OPS_MAX_GENSUKI_PAGES); $page++) {
        $response = spoinc_bridge_ops_gensuki_get_json(
            SPOINC_BRIDGE_OPS_GET_ALL_TRANSACTIONS_PATH,
            [
                'projectId' => SPOINC_BRIDGE_OPS_PROJECT_ID,
                'page' => $page
            ]
        );

        if (!$response['success']) {
            $gensukiErrors[] = [
                'page' => $page,
                'http_code' => $response['http_code'],
                'error' => $response['error'] ?: 'Gensuki request failed.',
                'raw_preview' => substr((string)$response['raw_body'], 0, 500)
            ];
            break;
        }

        $json = $response['json'] ?: [];
        $totalPages = max(1, spoinc_bridge_ops_extract_total_pages($json));
        $rows = spoinc_bridge_ops_extract_gensuki_rows($json);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $status = spoinc_bridge_ops_extract_status_from_row($row);

            if (!spoinc_bridge_ops_is_pending_status($status)) {
                continue;
            }

            $classifiedRow = spoinc_bridge_ops_classify_gensuki_pending_row($row, true);
            $rowSignature = (string)($classifiedRow['signature'] ?? '');
            $localIntentId = (string)($classifiedRow['local_match']['row']['intent_id'] ?? '');

            if ($rowSignature !== $signature) {
                continue;
            }

            if ($intentId !== '' && $localIntentId !== $intentId) {
                continue;
            }

            return [
                'found' => true,
                'row' => $classifiedRow,
                'gensuki_errors' => $gensukiErrors
            ];
        }
    }

    return [
        'found' => false,
        'row' => null,
        'gensuki_errors' => $gensukiErrors
    ];
}

/**
 * Open the shared Narrrfs PDO connection for the dedicated admin-operation log.
 *
 * Plain language for DEVS FOR DECADES:
 * The existing reconciliation lookup remains SQLite READONLY. This writer uses
 * the verified central api/config/database.php connection and may write only
 * tbl_spoinc_bridge_admin_operations. It never creates schema automatically.
 */
function spoinc_bridge_ops_open_admin_log_database(): PDO
{
    $databaseConfigPath = __DIR__ . '/../config/database.php';

    if (!is_file($databaseConfigPath)) {
        throw new RuntimeException('Central database config is missing.');
    }

    require_once $databaseConfigPath;

    if (!function_exists('getDatabaseConnection')) {
        throw new RuntimeException('Central PDO database helper is unavailable.');
    }

    $pdo = getDatabaseConnection();
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    $tableCheck = $pdo->prepare(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table_name LIMIT 1"
    );
    $tableCheck->execute([
        ':table_name' => SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE
    ]);

    if ((string)$tableCheck->fetchColumn() !== SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE) {
        throw new RuntimeException('SPOINC bridge admin operation log table is missing.');
    }

    return $pdo;
}

/**
 * Encode one admin-audit JSON value and fail closed if encoding fails.
 */
function spoinc_bridge_ops_encode_admin_log_json(array $value): string
{
    $encoded = json_encode(
        $value,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($encoded === false) {
        throw new RuntimeException('Failed to encode SPOINC bridge admin audit JSON.');
    }

    return $encoded;
}

/**
 * Build the deterministic attempt key for one fail-only external mutation.
 *
 * The admin reason is intentionally excluded so editing a note cannot create a
 * second logical attempt. The attempt number preserves every failed retry as a
 * separate audit row instead of overwriting earlier evidence.
 */
function spoinc_bridge_ops_build_fail_operation_key(
    array $validation,
    int $attemptCount
): string {
    return hash('sha256', implode('|', [
        SPOINC_BRIDGE_OPS_FAIL_OPERATION_TYPE,
        SPOINC_BRIDGE_OPS_PROJECT_ID,
        (string)($validation['intent_id'] ?? ''),
        (string)($validation['signature'] ?? ''),
        'attempt:' . $attemptCount
    ]));
}

/**
 * Prepare one new dedicated admin audit attempt before Gensuki.
 *
 * A completed operation is never reissued. Any prepared attempt is treated as
 * unresolved and blocks another call. If all earlier attempts failed, a new
 * attempt row may be created only after the caller has already repeated the
 * authoritative Gensuki, Solana, and local-intent validation.
 */
function spoinc_bridge_ops_prepare_fail_admin_operation(
    array $validation,
    array $admin,
    string $dryRunToken,
    array $confirmPayload
): array {
    $pdo = spoinc_bridge_ops_open_admin_log_database();
    $transactionOpen = false;
    $now = gmdate('Y-m-d H:i:s');
    $requestJson = spoinc_bridge_ops_encode_admin_log_json($confirmPayload);
    $dryRunFingerprint = hash('sha256', $dryRunToken);

    try {
        // Serialize competing protected apply attempts before inspecting prior
        // audit state. The remote Gensuki call occurs only after COMMIT.
        $pdo->exec('BEGIN IMMEDIATE');
        $transactionOpen = true;

        $existingStmt = $pdo->prepare("
            SELECT
                operation_id,
                operation_status,
                attempt_count
            FROM tbl_spoinc_bridge_admin_operations
            WHERE operation_type = :operation_type
              AND intent_id = :intent_id
              AND transaction_hash = :transaction_hash
            ORDER BY attempt_count DESC, operation_id DESC
        ");
        $existingStmt->execute([
            ':operation_type' => SPOINC_BRIDGE_OPS_FAIL_OPERATION_TYPE,
            ':intent_id' => (int)$validation['intent_id'],
            ':transaction_hash' => (string)$validation['signature']
        ]);
        $existingRows = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

        $maxAttemptCount = 0;

        foreach ($existingRows as $existing) {
            $existingStatus = strtolower(trim((string)($existing['operation_status'] ?? '')));
            $existingAttemptCount = (int)($existing['attempt_count'] ?? 0);
            $maxAttemptCount = max($maxAttemptCount, $existingAttemptCount);

            if ($existingStatus === 'completed') {
                $pdo->exec('ROLLBACK');
                $transactionOpen = false;

                return [
                    'allowed' => false,
                    'block_reason' => 'completed',
                    'operation_id' => (int)$existing['operation_id'],
                    'operation_status' => $existingStatus,
                    'attempt_count' => $existingAttemptCount
                ];
            }

            if ($existingStatus === 'prepared') {
                $pdo->exec('ROLLBACK');
                $transactionOpen = false;

                return [
                    'allowed' => false,
                    'block_reason' => 'prepared',
                    'operation_id' => (int)$existing['operation_id'],
                    'operation_status' => $existingStatus,
                    'attempt_count' => $existingAttemptCount
                ];
            }

            if ($existingStatus !== 'failed') {
                $pdo->exec('ROLLBACK');
                $transactionOpen = false;

                return [
                    'allowed' => false,
                    'block_reason' => 'unexpected_status',
                    'operation_id' => (int)$existing['operation_id'],
                    'operation_status' => $existingStatus,
                    'attempt_count' => $existingAttemptCount
                ];
            }
        }

        $attemptCount = $maxAttemptCount + 1;
        $operationKey = spoinc_bridge_ops_build_fail_operation_key(
            $validation,
            $attemptCount
        );

        $insert = $pdo->prepare("
            INSERT INTO tbl_spoinc_bridge_admin_operations (
                operation_key,
                operation_type,
                admin_id,
                intent_id,
                transaction_id,
                partner_name,
                partner_transaction_id,
                transaction_hash,
                reason,
                dry_run_fingerprint,
                classification_before,
                gensuki_status_before,
                gensuki_status_after,
                solana_state,
                operation_status,
                attempt_count,
                http_status,
                request_json,
                response_json,
                error_message,
                created_at,
                updated_at,
                completed_at
            ) VALUES (
                :operation_key,
                :operation_type,
                :admin_id,
                :intent_id,
                NULL,
                'gensuki',
                :partner_transaction_id,
                :transaction_hash,
                :reason,
                :dry_run_fingerprint,
                'failed_candidate_local',
                :gensuki_status_before,
                NULL,
                :solana_state,
                'prepared',
                :attempt_count,
                NULL,
                :request_json,
                NULL,
                NULL,
                :created_at,
                :updated_at,
                NULL
            )
        ");
        $insert->execute([
            ':operation_key' => $operationKey,
            ':operation_type' => SPOINC_BRIDGE_OPS_FAIL_OPERATION_TYPE,
            ':admin_id' => (string)($admin['admin_id'] ?? 'unknown'),
            ':intent_id' => (int)$validation['intent_id'],
            ':partner_transaction_id' => (string)$validation['gensuki_id'],
            ':transaction_hash' => (string)$validation['signature'],
            ':reason' => (string)$validation['reason'],
            ':dry_run_fingerprint' => $dryRunFingerprint,
            ':gensuki_status_before' => (string)$validation['gensuki_status'],
            ':solana_state' => (string)$validation['solana_state'],
            ':attempt_count' => $attemptCount,
            ':request_json' => $requestJson,
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        if ($insert->rowCount() !== 1) {
            throw new RuntimeException('Prepared admin operation row was not inserted.');
        }

        $operationId = (int)$pdo->lastInsertId();
        $pdo->exec('COMMIT');
        $transactionOpen = false;

        return [
            'allowed' => true,
            'operation_id' => $operationId,
            'operation_key' => $operationKey,
            'operation_status' => 'prepared',
            'attempt_count' => $attemptCount
        ];
    } catch (Throwable $error) {
        if ($transactionOpen) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable $rollbackError) {
                error_log('[SPOINC BRIDGE OPS] Admin operation rollback failed: ' . $rollbackError->getMessage());
            }
        }

        throw $error;
    }
}

/**
 * Finalize one prepared admin operation after the external Gensuki result.
 */
function spoinc_bridge_ops_finalize_fail_admin_operation(
    int $operationId,
    bool $success,
    array $confirmResponse
): void {
    $pdo = spoinc_bridge_ops_open_admin_log_database();
    $now = gmdate('Y-m-d H:i:s');
    $responseJson = spoinc_bridge_ops_encode_admin_log_json([
        'http_code' => $confirmResponse['http_code'] ?? null,
        'json' => $confirmResponse['json'] ?? null,
        'raw_body' => (string)($confirmResponse['raw_body'] ?? ''),
        'error' => (string)($confirmResponse['error'] ?? '')
    ]);

    $httpStatus = (int)($confirmResponse['http_code'] ?? 0);
    $errorMessage = $success
        ? null
        : trim((string)($confirmResponse['error'] ?? ''));

    if (!$success && $errorMessage === '') {
        $errorMessage = 'Gensuki failed-confirm request did not succeed.';
    }

    try {
        $pdo->beginTransaction();

        $update = $pdo->prepare("
            UPDATE tbl_spoinc_bridge_admin_operations
            SET operation_status = :operation_status,
                gensuki_status_after = :gensuki_status_after,
                http_status = :http_status,
                response_json = :response_json,
                error_message = :error_message,
                updated_at = :updated_at,
                completed_at = :completed_at
            WHERE operation_id = :operation_id
              AND operation_status = 'prepared'
        ");
        $update->execute([
            ':operation_status' => $success ? 'completed' : 'failed',
            ':gensuki_status_after' => $success ? 'failed' : null,
            ':http_status' => $httpStatus > 0 ? $httpStatus : null,
            ':response_json' => $responseJson,
            ':error_message' => $errorMessage,
            ':updated_at' => $now,
            ':completed_at' => $success ? $now : null,
            ':operation_id' => $operationId
        ]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Prepared admin operation could not be finalized.');
        }

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $error;
    }
}

/**
 * Validate the one currently-supported fail-only reconciliation candidate.
 *
 * Plain language for DEVS FOR DECADES:
 * Dry-run and apply must use the exact same authoritative server checks.
 * This helper re-reads Gensuki, rechecks Solana, verifies the matching local
 * bridge intent, and returns facts only. It never calls Gensuki /confirm and
 * never writes Narrrfs, DSPOINC, SPOINC, score, settlement, or ledger state.
 */
function spoinc_bridge_ops_validate_fail_local_not_found_candidate(array $data): array
{
    $signature = spoinc_bridge_ops_request_string($data, 'signature', 140);
    $intentId = spoinc_bridge_ops_request_string($data, 'intent_id', 80);
    $reason = spoinc_bridge_ops_request_string($data, 'reason', 500);
    $failedChecks = [];

    if (!spoinc_bridge_ops_is_valid_solana_signature($signature)) {
        $failedChecks[] = 'A valid Solana transaction signature is required.';
    }

    if ($intentId === '') {
        $failedChecks[] = 'Intent ID is required for protected fail reconciliation.';
    }

    if ($reason === '') {
        $failedChecks[] = 'Admin note is required for protected fail reconciliation.';
    }

    if (!empty($failedChecks)) {
        return [
            'can_apply' => false,
            'signature' => $signature,
            'intent_id' => $intentId,
            'reason' => $reason,
            'gensuki_id' => '',
            'gensuki_status' => '',
            'local_table' => '',
            'local_intent_id' => '',
            'solana_state' => '',
            'failed_checks' => $failedChecks,
            'gensuki_errors' => [],
            'row' => null
        ];
    }

    $auditMatch = spoinc_bridge_ops_find_pending_audit_row_for_apply(
        $signature,
        $intentId
    );

    if (!$auditMatch['found']) {
        return [
            'can_apply' => false,
            'signature' => $signature,
            'intent_id' => $intentId,
            'reason' => $reason,
            'gensuki_id' => '',
            'gensuki_status' => '',
            'local_table' => '',
            'local_intent_id' => '',
            'solana_state' => '',
            'failed_checks' => [
                'No matching pending Gensuki/local intent row was found during the fresh server recheck.'
            ],
            'gensuki_errors' => $auditMatch['gensuki_errors'] ?? [],
            'row' => null
        ];
    }

    $row = $auditMatch['row'];
    $bucket = (string)($row['bucket'] ?? '');
    $gensukiStatus = (string)($row['status'] ?? '');
    $gensukiId = (string)($row['gensuki_id'] ?? '');
    $localTable = (string)($row['local_match']['table'] ?? '');
    $localIntentId = (string)($row['local_match']['row']['intent_id'] ?? '');
    $solanaState = (string)($row['solana']['state'] ?? '');

    if ($bucket !== 'failed_candidate_local') {
        $failedChecks[] = 'Expected bucket failed_candidate_local, got ' . $bucket;
    }

    if (!spoinc_bridge_ops_is_pending_status($gensukiStatus)) {
        $failedChecks[] = 'Expected Gensuki pending status, got ' . $gensukiStatus;
    }

    if ($localTable !== 'tbl_spoinc_bridge_intents') {
        $failedChecks[] = 'Expected local table tbl_spoinc_bridge_intents, got ' . $localTable;
    }

    if ($localIntentId !== $intentId) {
        $failedChecks[] = 'Expected intent id ' . $intentId . ', got ' . $localIntentId;
    }

    if ($solanaState !== 'not_found') {
        $failedChecks[] = 'Expected Solana state not_found, got ' . $solanaState;
    }

    if ($gensukiId === '') {
        $failedChecks[] = 'Missing Gensuki transaction id.';
    }

    return [
        'can_apply' => empty($failedChecks),
        'signature' => $signature,
        'intent_id' => $intentId,
        'reason' => $reason,
        'gensuki_id' => $gensukiId,
        'gensuki_status' => $gensukiStatus,
        'local_table' => $localTable,
        'local_intent_id' => $localIntentId,
        'solana_state' => $solanaState,
        'failed_checks' => $failedChecks,
        'gensuki_errors' => $auditMatch['gensuki_errors'] ?? [],
        'row' => $row
    ];
}

/**
 * Build deterministic dry-run workflow evidence for the current fail candidate.
 *
 * Plain language for DEVS FOR DECADES:
 * This is workflow evidence, not authorization. Apply always repeats the
 * authoritative Gensuki, Solana, and local-intent checks before mutation.
 */
function spoinc_bridge_ops_build_fail_dry_run_token(array $validation): string
{
    return hash('sha256', implode('|', [
        SPOINC_BRIDGE_OPS_PROJECT_ID,
        (string)($validation['intent_id'] ?? ''),
        (string)($validation['signature'] ?? ''),
        (string)($validation['reason'] ?? ''),
        (string)($validation['gensuki_id'] ?? ''),
        (string)($validation['gensuki_status'] ?? ''),
        (string)($validation['local_table'] ?? ''),
        (string)($validation['local_intent_id'] ?? ''),
        (string)($validation['solana_state'] ?? '')
    ]));
}

/**
 * Execute the real fail-only dry-run without mutating external or local state.
 */
function spoinc_bridge_ops_dry_run_fail_local_not_found(array $data): void
{
    $validation = spoinc_bridge_ops_validate_fail_local_not_found_candidate($data);
    $canApply = (bool)($validation['can_apply'] ?? false);

    $dryRunToken = $canApply
        ? spoinc_bridge_ops_build_fail_dry_run_token($validation)
        : null;

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'action' => 'dry_run_fail_local_not_found',
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'mode' => 'authoritative_dry_run',
            'can_apply' => $canApply,
            'intent_id' => $validation['intent_id'],
            'signature' => $validation['signature'],
            'reason' => $validation['reason'],
            'required_confirmation_phrase_for_apply' => SPOINC_BRIDGE_OPS_CONFIRM_FAILED_PHRASE,
            'dry_run_token' => $dryRunToken,
            'blocked_reason' => $canApply
                ? null
                : 'One or more protected fail-only checks did not pass.',
            'failed_checks' => $validation['failed_checks'],
            'gensuki_errors' => $validation['gensuki_errors'],
            'server_recheck' => [
                'gensuki_id' => $validation['gensuki_id'],
                'gensuki_status_before' => $validation['gensuki_status'],
                'local_table' => $validation['local_table'],
                'local_intent_id' => $validation['local_intent_id'],
                'solana_state' => $validation['solana_state']
            ],
            'row' => $validation['row'],
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false,
                'rpc_error_auto_failed' => false
            ]
        ]
    ]);
}

/**
 * Apply fail-only cleanup for a local pending intent whose Solana transaction
 * is not found.
 *
 * Plain language for DEVS FOR DECADES:
 * This is the first real protected apply action. It is intentionally narrow:
 * - Only failed_candidate_local is allowed.
 * - Only local table tbl_spoinc_bridge_intents is allowed.
 * - Only Gensuki pending statuses are allowed.
 * - Only Solana not_found is allowed.
 * - The admin must type CONFIRM GENSUKI FAILED exactly.
 *
 * This action writes only the dedicated admin-operation audit row and then calls
 * Gensuki /confirm with status failed. It does not credit/debit DSPOINC, settle
 * or mutate bridge/economy rows, or create score rows. rpc_error must never be
 * treated as failed.
 */
function spoinc_bridge_ops_apply_fail_local_not_found(array $data, array $admin): void
{
    $confirmationPhrase = spoinc_bridge_ops_request_string(
        $data,
        'confirmation_phrase',
        80
    );

    $submittedDryRunToken = spoinc_bridge_ops_request_string(
        $data,
        'dry_run_token',
        128
    );

    if ($confirmationPhrase !== SPOINC_BRIDGE_OPS_CONFIRM_FAILED_PHRASE) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Confirmation phrase mismatch. Type exactly: ' . SPOINC_BRIDGE_OPS_CONFIRM_FAILED_PHRASE
        ], 400);
    }

    $validation = spoinc_bridge_ops_validate_fail_local_not_found_candidate($data);

    if (!($validation['can_apply'] ?? false)) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Protected fail apply checks did not pass.',
            'failed_checks' => $validation['failed_checks'],
            'gensuki_errors' => $validation['gensuki_errors'],
            'row' => $validation['row'],
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false
            ]
        ], 409);
    }

    $expectedDryRunToken = spoinc_bridge_ops_build_fail_dry_run_token($validation);

    if (
        $submittedDryRunToken === ''
        || !hash_equals($expectedDryRunToken, $submittedDryRunToken)
    ) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Dry-run evidence is missing or stale. Run dry_run_fail_local_not_found again before apply.',
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false
            ]
        ], 409);
    }

    $result = spoinc_bridge_ops_apply_validated_fail_local_not_found(
        $validation,
        $admin,
        $submittedDryRunToken
    );

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => $result
    ]);
}

/**
 * Apply one already-validated local failed candidate.
 *
 * Plain language for DEVS FOR DECADES:
 * Phase 1D reuses this helper for assisted cleanup. The caller must already
 * have run the same authoritative validation used by the manual Phase 1C path.
 * This helper still writes only the admin operation log and sends Gensuki
 * status failed. It never credits DSPOINC, debits DSPOINC, settles Narrrfs
 * bridge rows, or marks complete candidates.
 */
function spoinc_bridge_ops_apply_validated_fail_local_not_found(
    array $validation,
    array $admin,
    string $submittedDryRunToken
): array {
    $signature = (string)$validation['signature'];
    $intentId = (string)$validation['intent_id'];
    $reason = (string)$validation['reason'];
    $gensukiId = (string)$validation['gensuki_id'];
    $gensukiStatus = (string)$validation['gensuki_status'];
    $localTable = (string)$validation['local_table'];
    $localIntentId = (string)$validation['local_intent_id'];
    $solanaState = (string)$validation['solana_state'];

    $confirmPayload = [
        'projectId' => SPOINC_BRIDGE_OPS_PROJECT_ID,
        'transactionHash' => $signature,
        'status' => 'failed'
    ];

    try {
        $adminOperation = spoinc_bridge_ops_prepare_fail_admin_operation(
            $validation,
            $admin,
            $submittedDryRunToken,
            $confirmPayload
        );
    } catch (Throwable $error) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Admin operation log preparation failed. Gensuki was not called.',
            'details' => spoinc_bridge_ops_is_localhost() ? $error->getMessage() : null,
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'admin_operation_log_write_performed' => false,
                'bridge_economy_db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false
            ]
        ], 500);
    }

    if (!($adminOperation['allowed'] ?? false)) {
        $blockReason = (string)($adminOperation['block_reason'] ?? 'unknown');
        $errorMessage = 'Protected fail apply is blocked by the admin operation log.';

        if ($blockReason === 'completed') {
            $errorMessage = 'This reconciliation operation was already completed. Gensuki was not called again.';
        } elseif ($blockReason === 'prepared') {
            $errorMessage = 'A prepared reconciliation operation already exists. Manual review is required before retry.';
        }

        spoinc_bridge_ops_json([
            'success' => false,
            'error' => $errorMessage,
            'admin_operation' => $adminOperation,
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'admin_operation_log_write_performed' => false,
                'bridge_economy_db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false
            ]
        ], 409);
    }

    $operationId = (int)$adminOperation['operation_id'];

    $confirmResponse = spoinc_bridge_ops_gensuki_post_json(
        SPOINC_BRIDGE_OPS_CONFIRM_PATH,
        $confirmPayload
    );

    if (!$confirmResponse['success']) {
        $adminLogFinalizeError = null;

        try {
            spoinc_bridge_ops_finalize_fail_admin_operation(
                $operationId,
                false,
                $confirmResponse
            );
        } catch (Throwable $error) {
            $adminLogFinalizeError = $error->getMessage();
            error_log('[SPOINC BRIDGE OPS] Admin log failure finalization error: ' . $adminLogFinalizeError);
        }

        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Gensuki failed-confirm request failed.',
            'http_code' => $confirmResponse['http_code'],
            'response_error' => $confirmResponse['error'],
            'raw_preview' => substr((string)$confirmResponse['raw_body'], 0, 700),
            'sent_payload' => $confirmPayload,
            'admin_operation' => [
                'operation_id' => $operationId,
                'operation_key' => $adminOperation['operation_key'],
                'attempt_count' => $adminOperation['attempt_count'],
                'expected_status' => $adminLogFinalizeError === null ? 'failed' : 'prepared',
                'finalize_error' => $adminLogFinalizeError
            ],
            'safety' => [
                'gensuki_confirm_called' => true,
                'gensuki_status_sent' => 'failed',
                'db_write_performed' => true,
                'db_write_scope' => SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE . '_only',
                'admin_operation_log_write_performed' => true,
                'bridge_economy_db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'manual_review_required' => $adminLogFinalizeError !== null
            ]
        ], 502);
    }

    try {
        spoinc_bridge_ops_finalize_fail_admin_operation(
            $operationId,
            true,
            $confirmResponse
        );
    } catch (Throwable $error) {
        error_log('[SPOINC BRIDGE OPS] Gensuki succeeded but admin log finalization failed: ' . $error->getMessage());

        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Gensuki failed-confirm succeeded, but the admin operation log could not be finalized. Manual review required; do not retry automatically.',
            'gensuki_response' => $confirmResponse['json'],
            'admin_operation' => [
                'operation_id' => $operationId,
                'operation_key' => $adminOperation['operation_key'],
                'attempt_count' => $adminOperation['attempt_count'],
                'expected_status' => 'prepared'
            ],
            'safety' => [
                'gensuki_confirm_called' => true,
                'gensuki_status_sent' => 'failed',
                'db_write_performed' => true,
                'db_write_scope' => SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE . '_only',
                'admin_operation_log_write_performed' => true,
                'bridge_economy_db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'manual_review_required' => true,
                'automatic_retry_safe' => false
            ]
        ], 500);
    }

    return [
        'action' => 'apply_fail_local_not_found',
        'phase' => SPOINC_BRIDGE_OPS_PHASE,
        'mode' => 'protected_apply',
        'admin_id' => $admin['admin_id'] ?? 'unknown',
        'intent_id' => $intentId,
        'gensuki_id' => $gensukiId,
        'signature' => $signature,
        'reason' => $reason,
        'confirmed_status' => 'failed',
        'admin_operation' => [
            'operation_id' => $operationId,
            'operation_key' => $adminOperation['operation_key'],
            'operation_status' => 'completed',
            'attempt_count' => $adminOperation['attempt_count']
        ],
        'gensuki_response' => $confirmResponse['json'],
        'server_recheck' => [
            'bucket' => 'failed_candidate_local',
            'gensuki_status_before' => $gensukiStatus,
            'local_table' => $localTable,
            'local_intent_id' => $localIntentId,
            'solana_state' => $solanaState
        ],
        'safety' => [
            'gensuki_confirm_called' => true,
            'gensuki_status_sent' => 'failed',
            'db_write_performed' => true,
            'db_write_scope' => SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE . '_only',
            'admin_operation_log_write_performed' => true,
            'bridge_economy_db_write_performed' => false,
            'dspoinc_credit_performed' => false,
            'dspoinc_debit_performed' => false,
            'local_bridge_settlement_performed' => false,
            'rpc_error_auto_failed' => false
        ]
    ];
}

/**
 * Apply all selected safe failed local rows from the current pending queue.
 *
 * Plain language for DEVS FOR DECADES:
 * This is Phase 1D assisted cleanup. The browser may propose selected rows, but
 * every row is re-read from Gensuki, rechecked on Solana, and revalidated
 * against the local Narrrfs intent before Gensuki is called.
 *
 * Only failed_candidate_local + SOL_TO_SPOINC + Solana not_found rows are
 * eligible. This action never moves DSPOINC, never settles a claim, and never
 * confirms complete rows.
 */
function spoinc_bridge_ops_apply_safe_failed_local_batch(array $data, array $admin): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Method not allowed. apply_safe_failed_local_batch requires POST.'
        ], 405);
    }

    $confirmationPhrase = spoinc_bridge_ops_request_string(
        $data,
        'confirmation_phrase',
        80
    );

    $reason = spoinc_bridge_ops_request_string($data, 'reason', 500);

    if ($confirmationPhrase !== SPOINC_BRIDGE_OPS_CONFIRM_FAILED_PHRASE) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Confirmation phrase mismatch. Type exactly: ' . SPOINC_BRIDGE_OPS_CONFIRM_FAILED_PHRASE,
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false
            ]
        ], 400);
    }

    if ($reason === '') {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Admin note is required for assisted failed cleanup.'
        ], 400);
    }

    $selectedRows = $data['rows'] ?? [];

    if (!is_array($selectedRows) || empty($selectedRows)) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Select at least one safe failed local row.'
        ], 400);
    }

    if (count($selectedRows) > 20) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Too many rows selected. Maximum assisted cleanup batch is 20 rows.'
        ], 400);
    }

    $applied = [];
    $blocked = [];
    $skipped = [];

    foreach ($selectedRows as $index => $selectedRow) {
        if (!is_array($selectedRow)) {
            $blocked[] = [
                'index' => $index,
                'error' => 'Selected row is malformed.'
            ];
            continue;
        }

        $candidateData = [
            'intent_id' => trim((string)($selectedRow['intent_id'] ?? '')),
            'signature' => trim((string)($selectedRow['signature'] ?? '')),
            'reason' => $reason
        ];

        $validation = spoinc_bridge_ops_validate_fail_local_not_found_candidate(
            $candidateData
        );

        $rowRouteKey = (string)($validation['row']['local_match']['row']['route_key'] ?? '');
        $solanaCanFail = (bool)($validation['row']['solana']['can_fail'] ?? false);
        $solanaCanComplete = (bool)($validation['row']['solana']['can_complete'] ?? true);
        $needsManualReview = (bool)($validation['row']['solana']['needs_manual_review'] ?? true);

        if ($rowRouteKey !== 'SOL_TO_SPOINC') {
            $validation['failed_checks'][] = 'Expected local route SOL_TO_SPOINC, got ' . $rowRouteKey;
            $validation['can_apply'] = false;
        }

        if (!$solanaCanFail) {
            $validation['failed_checks'][] = 'Solana recheck did not allow fail cleanup.';
            $validation['can_apply'] = false;
        }

        if ($solanaCanComplete) {
            $validation['failed_checks'][] = 'Solana recheck still allows complete; fail cleanup blocked.';
            $validation['can_apply'] = false;
        }

        if ($needsManualReview) {
            $validation['failed_checks'][] = 'Solana recheck requires manual review.';
            $validation['can_apply'] = false;
        }

        if (!($validation['can_apply'] ?? false)) {
            $blocked[] = [
                'index' => $index,
                'intent_id' => $candidateData['intent_id'],
                'signature' => $candidateData['signature'],
                'failed_checks' => $validation['failed_checks'],
                'gensuki_errors' => $validation['gensuki_errors']
            ];
            continue;
        }

        $dryRunToken = spoinc_bridge_ops_build_fail_dry_run_token($validation);

        try {
            $applied[] = spoinc_bridge_ops_apply_validated_fail_local_not_found(
                $validation,
                $admin,
                $dryRunToken
            );
        } catch (Throwable $error) {
            $skipped[] = [
                'index' => $index,
                'intent_id' => $candidateData['intent_id'],
                'signature' => $candidateData['signature'],
                'error' => $error->getMessage()
            ];
        }
    }

    spoinc_bridge_ops_json([
        'success' => empty($blocked) && empty($skipped),
        'data' => [
            'action' => 'apply_safe_failed_local_batch',
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'mode' => 'protected_assisted_batch_apply',
            'admin_id' => $admin['admin_id'] ?? 'unknown',
            'selected_count' => count($selectedRows),
            'applied_count' => count($applied),
            'blocked_count' => count($blocked),
            'skipped_count' => count($skipped),
            'applied' => $applied,
            'blocked' => $blocked,
            'skipped' => $skipped,
            'safety' => [
                'eligible_bucket_required' => 'failed_candidate_local',
                'eligible_route_required' => 'SOL_TO_SPOINC',
                'eligible_solana_state_required' => 'not_found',
                'gensuki_confirm_status_allowed' => 'failed',
                'gensuki_complete_called' => false,
                'bridge_economy_db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'local_bridge_settlement_performed' => false,
                'rpc_error_auto_failed' => false
            ]
        ]
    ], empty($blocked) && empty($skipped) ? 200 : 207);
}

/**
 * Return a dry-run-only protected operation scaffold.
 *
 * Plain language for DEVS FOR DECADES:
 * This makes the future employee workflow visible without allowing real
 * settlement, failure, confirmation, or credit actions yet.
 */
function spoinc_bridge_ops_dry_run_scaffold(array $data, string $operationType): void
{
    $signature = spoinc_bridge_ops_request_string($data, 'signature', 140);
    $intentId = spoinc_bridge_ops_request_string($data, 'intent_id', 80);
    $reason = spoinc_bridge_ops_request_string($data, 'reason', 500);

    $requiredPhrase = 'CONFIRM REVIEW ONLY';

    if (str_contains($operationType, 'failed') || str_contains($operationType, 'fail')) {
        $requiredPhrase = 'CONFIRM GENSUKI FAILED';
    } elseif (str_contains($operationType, 'complete')) {
        $requiredPhrase = 'CONFIRM GENSUKI COMPLETE';
    } elseif (str_contains($operationType, 'settle')) {
        $requiredPhrase = 'CONFIRM DSPOINC SETTLEMENT';
    }

    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'action' => $operationType,
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'mode' => 'dry_run_only',
            'can_apply' => false,
            'intent_id' => $intentId,
            'signature' => $signature,
            'reason' => $reason,
            'required_confirmation_phrase_for_future_apply' => $requiredPhrase,
            'blocked_reason' => 'This operation remains intentionally disabled in Phase 1C.',
            'checks_planned' => [
                'Solana state must be rechecked',
                'Gensuki row must be matched',
                'local duplicate check must pass',
                'amount and route must be verified',
                'admin operation log must be written before any future apply'
            ],
            'safety' => [
                'gensuki_confirm_called' => false,
                'db_write_performed' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false
            ]
        ]
    ]);
}

/**
 * Return the newest dedicated bridge admin-operation audit rows.
 *
 * This endpoint stays SQLite READONLY and does not expose the reusable dry-run
 * token. The stored one-way dry-run fingerprint is intentionally not selected.
 */
function spoinc_bridge_ops_get_operation_log(): void
{
    $dbPath = spoinc_bridge_ops_get_local_db_path();

    if ($dbPath === '' || !class_exists('SQLite3')) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'SPOINC bridge admin operation log database is unavailable.'
        ], 503);
    }

    try {
        $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(5000);

        if (!spoinc_bridge_ops_sqlite_table_exists($db, SPOINC_BRIDGE_OPS_ADMIN_LOG_TABLE)) {
            spoinc_bridge_ops_json([
                'success' => false,
                'error' => 'SPOINC bridge admin operation log table is missing.'
            ], 503);
        }

        $result = $db->query("
            SELECT
                operation_id,
                operation_type,
                admin_id,
                intent_id,
                transaction_id,
                partner_name,
                partner_transaction_id,
                transaction_hash,
                reason,
                classification_before,
                gensuki_status_before,
                gensuki_status_after,
                solana_state,
                operation_status,
                attempt_count,
                http_status,
                error_message,
                created_at,
                updated_at,
                completed_at
            FROM tbl_spoinc_bridge_admin_operations
            ORDER BY operation_id DESC
            LIMIT 50
        ");

        if (!$result) {
            throw new RuntimeException('Admin operation log query failed.');
        }

        $rows = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        $db->close();

        spoinc_bridge_ops_json([
            'success' => true,
            'data' => [
                'action' => 'get_operation_log',
                'phase' => SPOINC_BRIDGE_OPS_PHASE,
                'implemented' => true,
                'result_status' => 'ok',
                'row_count' => count($rows),
                'rows' => $rows,
                'safety' => [
                    'read_only' => true,
                    'db_open_mode' => 'SQLITE3_OPEN_READONLY',
                    'dry_run_token_exposed' => false,
                    'dry_run_fingerprint_exposed' => false,
                    'bridge_economy_db_write_performed' => false
                ]
            ]
        ]);
    } catch (Throwable $error) {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'SPOINC bridge admin operation log is unavailable.',
            'details' => spoinc_bridge_ops_is_localhost() ? $error->getMessage() : null
        ], 503);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'message' => 'OK'
        ]
    ]);
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    spoinc_bridge_ops_json([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}

$admin = spoinc_bridge_ops_require_admin();
$data = spoinc_bridge_ops_read_json_input();
$action = spoinc_bridge_ops_get_action($data);

try {
    if ($action === 'get_health') {
        spoinc_bridge_ops_get_health($admin);
    }

    if ($action === 'check_solana_signature') {
        spoinc_bridge_ops_check_solana_signature($data);
    }

    if ($action === 'get_gensuki_pending_audit') {
    spoinc_bridge_ops_get_gensuki_pending_audit($data);
}

if ($action === 'dry_run_fail_local_not_found') {
    spoinc_bridge_ops_dry_run_fail_local_not_found($data);
}

if ($action === 'apply_fail_local_not_found') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        spoinc_bridge_ops_json([
            'success' => false,
            'error' => 'Method not allowed. apply_fail_local_not_found requires POST.'
        ], 405);
    }

    spoinc_bridge_ops_apply_fail_local_not_found($data, $admin);
}

if ($action === 'apply_safe_failed_local_batch') {
    spoinc_bridge_ops_apply_safe_failed_local_batch($data, $admin);
}

if ($action === 'dry_run_fail_gensuki_only') {
    spoinc_bridge_ops_dry_run_scaffold($data, 'dry_run_fail_gensuki_only');
}

if ($action === 'dry_run_complete_gensuki_only') {
    spoinc_bridge_ops_dry_run_scaffold($data, 'dry_run_complete_gensuki_only');
}

if ($action === 'dry_run_settle_spoinc_claim') {
    spoinc_bridge_ops_dry_run_scaffold($data, 'dry_run_settle_spoinc_claim');
}

    if ($action === 'verify_failed_cleanup') {
        spoinc_bridge_ops_verify_failed_cleanup($data);
    }

    if ($action === 'verify_complete_settlement') {
        spoinc_bridge_ops_verify_complete_settlement($data);
    }

    if ($action === 'get_operation_log') {
        spoinc_bridge_ops_get_operation_log();
    }

    spoinc_bridge_ops_json([
        'success' => false,
        'error' => "Unsupported SPOINC bridge ops action: {$action}"
    ], 400);
} catch (Throwable $error) {
    error_log('[SPOINC BRIDGE OPS] ' . $error->getMessage());

    spoinc_bridge_ops_json([
        'success' => false,
        'error' => 'SPOINC bridge ops request failed.',
        'details' => spoinc_bridge_ops_is_localhost() ? $error->getMessage() : null
    ], 500);
}