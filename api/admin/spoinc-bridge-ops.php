<?php
/**
 * SPOINC Bridge Admin Operations API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint is the protected admin investigation cockpit for the
 * SPOINC / DSPOINC / Gensuki bridge.
 *
 * Phase 1A is intentionally read-only:
 * - It can report endpoint health.
 * - It can check Solana transaction signature status.
 * - It can return safe scaffolds for future cleanup/settlement verification.
 * - It must not call Gensuki /confirm.
 * - It must not credit or debit DSPOINC.
 * - It must not mutate bridge rows.
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
const SPOINC_BRIDGE_OPS_PHASE = 'phase_1b_pending_audit_read_only';
const SPOINC_BRIDGE_OPS_LOCAL_ADMIN_ID = '328601656659017732';
const SPOINC_BRIDGE_OPS_SOLANA_RPC_FALLBACK = 'https://api.mainnet-beta.solana.com';
const SPOINC_BRIDGE_OPS_PROJECT_ID = '7e04b38a-7bd4-4fab-acc4-dfa53a99b639';
const SPOINC_BRIDGE_OPS_GENSUKI_BASE_URL = 'https://app.gensuki.xyz';
const SPOINC_BRIDGE_OPS_GET_ALL_TRANSACTIONS_PATH = '/api/custom-token-presale/getAllTransactions';
const SPOINC_BRIDGE_OPS_MAX_GENSUKI_PAGES = 50;

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
            'mode' => 'read_only',
            'admin_id' => $admin['admin_id'],
            'auth_mode' => $admin['auth_mode'],
            'server_time_utc' => gmdate('Y-m-d H:i:s'),
            'safety' => [
                'gensuki_confirm_enabled' => false,
                'dspoinc_credit_enabled' => false,
                'dspoinc_debit_enabled' => false,
                'db_write_enabled' => false,
                'route_mutation_enabled' => false
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
            'blocked_reason' => 'Apply actions are intentionally disabled in Phase 1B.',
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
 * Return a safe operation-log scaffold.
 *
 * TODO: After checking whether a suitable admin/audit table already exists,
 * either read that table or add tbl_spoinc_bridge_admin_operations in a later
 * migration. Phase 1A must not create tables automatically.
 */
function spoinc_bridge_ops_get_operation_log(): void
{
    spoinc_bridge_ops_json([
        'success' => true,
        'data' => [
            'action' => 'get_operation_log',
            'phase' => SPOINC_BRIDGE_OPS_PHASE,
            'implemented' => false,
            'rows' => [],
            'result_status' => 'operation_log_table_not_connected_yet',
            'safety' => 'Read-only scaffold. No database writes are performed.'
        ]
    ]);
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
    spoinc_bridge_ops_dry_run_scaffold($data, 'dry_run_fail_local_not_found');
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