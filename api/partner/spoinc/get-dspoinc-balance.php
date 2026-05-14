<?php
// 🌉 SPOINC Bridge API — Gensuki DSPOINC Balance Lookup
// Secure read-only partner endpoint for querying backend-authoritative DSPOINC balances.
//
// DEVS FOR DECADES:
// - This endpoint never sends SPOINC and never holds Solana private keys.
// - This endpoint never deducts or credits DSPOINC.
// - DSPOINC balance is read from the append-only ledger only:
//   available DSPOINC = SUM(tbl_user_scores.score) - active tbl_dspoinc_stakes.amount.
// - Every successful partner balance query is written to tbl_spoinc_bridge_balance_queries.
// - Partner API keys are stored only as SHA-256 hashes in tbl_spoinc_bridge_api_keys.
// - Partner-provided timestamps are stored only as metadata; server time is authoritative.
// - Partner requests may be wallet-only; the Discord user is resolved from verified wallets.

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

/**
 * Allow trusted browser clients to call the partner balance API.
 *
 * DEVS FOR DECADES:
 * CORS is only browser access control. Real API security still comes from:
 * - Bearer key
 * - hashed partner API key table
 * - wallet verification
 * - read-only balance logic
 */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowedOrigins = [
    'https://narrrfs.world',
    'https://www.narrrfs.world',
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:8080'
];

if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$databaseIncludeCandidates = [
    __DIR__ . '/../../../config/database.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php'
];

foreach ($databaseIncludeCandidates as $databaseIncludePath) {
    if (file_exists($databaseIncludePath)) {
        require_once $databaseIncludePath;
        break;
    }
}

const SPOINC_BRIDGE_PARTNER_NAME = 'gensuki';
const SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC = 10000;
const SPOINC_BRIDGE_LOCAL_TEST_TOKEN = 'local-spoinc-bridge-test';

/**
 * Return a clean JSON payload and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running on a localhost-style development host.
 */
function is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data from JSON first, then POST fallback.
 */
function get_request_data(): array {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = [];
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (is_array($json)) {
        $cached = $json;
    } elseif (!empty($_POST) && is_array($_POST)) {
        $cached = $_POST;
    }

    return is_array($cached) ? $cached : [];
}

/**
 * Resolve Authorization header across Apache, Nginx, and local server setups.
 */
function get_authorization_header(): string {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim((string)$_SERVER['HTTP_AUTHORIZATION']);
    }

    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim((string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    if (!empty($_SERVER['Authorization'])) {
        return trim((string)$_SERVER['Authorization']);
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strtolower((string)$key) === 'authorization') {
                    return trim((string)$value);
                }
            }
        }
    }

    return '';
}

/**
 * Resolve the Bearer token from Authorization or explicit local test fields.
 */
function get_provided_partner_token(array $request): array {
    $authHeader = get_authorization_header();

    if (stripos($authHeader, 'Bearer ') === 0) {
        return [trim(substr($authHeader, 7)), 'authorization_bearer'];
    }

    $headerCandidates = [
        $_SERVER['HTTP_X_GENSUKI_API_KEY'] ?? '',
        $_SERVER['HTTP_X_SPOINC_BRIDGE_API_KEY'] ?? '',
        $_SERVER['HTTP_X_PARTNER_API_KEY'] ?? ''
    ];

    foreach ($headerCandidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '') {
            return [$candidate, 'custom_header'];
        }
    }

    $bodyCandidates = [
        $request['api_key'] ?? '',
        $request['partner_api_key'] ?? ''
    ];

    foreach ($bodyCandidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '') {
            return [$candidate, 'request_body'];
        }
    }

    return ['', 'none'];
}

/**
 * Open the Narrrfs World database locally or in production.
 */
function get_spoinc_bridge_database_connection(): PDO {
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }
    }

    if (function_exists('getDB')) {
        $pdo = getDB();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }
    }

    $dbPathCandidates = [];

    if (is_localhost_env()) {
        $dbPathCandidates[] = __DIR__ . '/../../../db/narrrf_world.sqlite';
        $dbPathCandidates[] = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
    }

    $dbPathCandidates[] = __DIR__ . '/../../../db/narrrf_world.sqlite';
    $dbPathCandidates[] = '/var/www/html/db/narrrf_world.sqlite';

    foreach ($dbPathCandidates as $dbPath) {
        if (!$dbPath || !file_exists($dbPath)) {
            continue;
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    throw new Exception('Database connection helper not available and SQLite file not found');
}

/**
 * Create the partner API key table if it does not exist yet.
 * Plain language: store only hashes so leaked database rows do not reveal live partner keys.
 */
function ensure_spoinc_bridge_api_key_table(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbl_spoinc_bridge_api_keys (
            api_key_id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_name TEXT NOT NULL DEFAULT 'gensuki',
            key_label TEXT,
            key_hash TEXT NOT NULL UNIQUE,
            status TEXT NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_used_at DATETIME,
            revoked_at DATETIME,
            metadata_json TEXT
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_spoinc_bridge_api_keys_partner_status ON tbl_spoinc_bridge_api_keys (partner_name, status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_spoinc_bridge_api_keys_key_hash ON tbl_spoinc_bridge_api_keys (key_hash)");
}

/**
 * Create the bridge balance query audit table if it does not exist yet.
 */
function ensure_spoinc_bridge_balance_query_table(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbl_spoinc_bridge_balance_queries (
            query_id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_request_id TEXT,
            partner_name TEXT DEFAULT 'gensuki',
            discord_id TEXT,
            wallet TEXT,
            available_dspoinc INTEGER NOT NULL DEFAULT 0,
            total_dspoinc INTEGER NOT NULL DEFAULT 0,
            frozen_dspoinc INTEGER NOT NULL DEFAULT 0,
            conversion_rate_dspoinc_per_spoinc INTEGER NOT NULL DEFAULT 10000,
            max_spoinc_convertible REAL NOT NULL DEFAULT 0,
            unix_timestamp INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            metadata_json TEXT
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_spoinc_bridge_balance_queries_discord_id ON tbl_spoinc_bridge_balance_queries (discord_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_spoinc_bridge_balance_queries_wallet ON tbl_spoinc_bridge_balance_queries (wallet)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_spoinc_bridge_balance_queries_partner_request_id ON tbl_spoinc_bridge_balance_queries (partner_request_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_spoinc_bridge_balance_queries_created_at ON tbl_spoinc_bridge_balance_queries (created_at)");
}

/**
 * Require Gensuki bridge authentication before revealing any balance data.
 * Plain language: compare the provided key to active database hashes, never raw stored keys.
 */
function require_partner_auth(PDO $pdo, array $request): array {
    [$providedToken, $authSource] = get_provided_partner_token($request);

    if (is_localhost_env() && $providedToken !== '' && hash_equals(SPOINC_BRIDGE_LOCAL_TEST_TOKEN, $providedToken)) {
        return [
            'auth_source' => 'localhost_test_token',
            'api_key_id' => null,
            'key_label' => 'local-test'
        ];
    }

    if ($providedToken === '') {
        json_response([
            'success' => false,
            'error' => 'Unauthorized partner request'
        ], 401);
    }

    $providedHash = hash('sha256', $providedToken);

    $stmt = $pdo->prepare("
        SELECT api_key_id, key_label
        FROM tbl_spoinc_bridge_api_keys
        WHERE partner_name = ?
          AND key_hash = ?
          AND status = 'active'
          AND revoked_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([SPOINC_BRIDGE_PARTNER_NAME, $providedHash]);
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (is_array($keyRow) && !empty($keyRow['api_key_id'])) {
        $updateStmt = $pdo->prepare("
            UPDATE tbl_spoinc_bridge_api_keys
            SET last_used_at = CURRENT_TIMESTAMP
            WHERE api_key_id = ?
        ");
        $updateStmt->execute([(int)$keyRow['api_key_id']]);

        return [
            'auth_source' => $authSource,
            'api_key_id' => (int)$keyRow['api_key_id'],
            'key_label' => (string)($keyRow['key_label'] ?? '')
        ];
    }

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tbl_spoinc_bridge_api_keys
        WHERE partner_name = ?
          AND status = 'active'
          AND revoked_at IS NULL
    ");
    $countStmt->execute([SPOINC_BRIDGE_PARTNER_NAME]);

    if ((int)$countStmt->fetchColumn() === 0 && !is_localhost_env()) {
        json_response([
            'success' => false,
            'error' => 'SPOINC bridge API key table has no active key configured'
        ], 500);
    }

    json_response([
        'success' => false,
        'error' => 'Unauthorized partner request'
    ], 401);
}

/**
 * Return total DSPOINC from the append-only score ledger.
 */
function get_user_total_dspoinc(PDO $pdo, string $discordId): int {
    $stmt = $pdo->prepare("\n        SELECT COALESCE(SUM(score), 0)\n        FROM tbl_user_scores\n        WHERE user_id = ?\n    ");
    $stmt->execute([$discordId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return frozen DSPOINC from currently active stakes.
 */
function get_user_frozen_dspoinc(PDO $pdo, string $discordId): int {
    $stmt = $pdo->prepare("\n        SELECT COALESCE(SUM(amount), 0)\n        FROM tbl_dspoinc_stakes\n        WHERE user_id = ?\n          AND status = 'active'\n    ");
    $stmt->execute([$discordId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Build the canonical available DSPOINC balance for bridge reads.
 */
function get_user_dspoinc_balance_snapshot(PDO $pdo, string $discordId): array {
    $totalDspoinc = get_user_total_dspoinc($pdo, $discordId);
    $frozenDspoinc = get_user_frozen_dspoinc($pdo, $discordId);
    $availableDspoinc = max(0, $totalDspoinc - $frozenDspoinc);

    return [
        'total_dspoinc' => $totalDspoinc,
        'frozen_dspoinc' => $frozenDspoinc,
        'available_dspoinc' => $availableDspoinc
    ];
}

/**
 * Validate a Discord ID without accepting arbitrary user-controlled strings.
 */
function validate_discord_id(string $discordId): void {
    if ($discordId === '' || !preg_match('/^\d{15,25}$/', $discordId)) {
        json_response([
            'success' => false,
            'error' => 'Invalid discord_id'
        ], 400);
    }
}

/**
 * Validate a Solana wallet-shaped base58 address without performing chain calls.
 */
function validate_solana_wallet(string $wallet): void {
    if ($wallet === '' || !preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet)) {
        json_response([
            'success' => false,
            'error' => 'Invalid or missing wallet'
        ], 400);
    }
}

/**
 * Resolve a Discord user ID from a registered Solana wallet.
 * Plain language: Gensuki knows the wallet, Narrrfs resolves the linked Discord account.
 */
function resolve_discord_id_from_registered_wallet(PDO $pdo, string $wallet): string {
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM tbl_holder_verifications
        WHERE wallet = ?
        ORDER BY verified_at DESC
        LIMIT 1
    ");
    $stmt->execute([$wallet]);

    $discordId = trim((string)$stmt->fetchColumn());

    if ($discordId === '') {
        json_response([
            'success' => false,
            'error' => 'User not available for this wallet',
            'code' => 'USER_NOT_AVAILABLE'
        ], 404);
    }

    validate_discord_id($discordId);
    return $discordId;
}

/**
 * Confirm that a provided Discord ID owns the provided registered wallet.
 * Plain language: this prevents mixed wallet/user requests from returning another account balance.
 */
function require_wallet_matches_discord_id(PDO $pdo, string $wallet, string $discordId): void {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM tbl_holder_verifications
        WHERE wallet = ?
          AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$wallet, $discordId]);

    if (!$stmt->fetchColumn()) {
        json_response([
            'success' => false,
            'error' => 'Wallet does not match the provided Discord ID',
            'code' => 'WALLET_DISCORD_MISMATCH'
        ], 403);
    }
}

/**
 * Resolve the balance owner for the partner request.
 * Plain language: wallet is required; Discord ID is optional and only used as an extra safety check.
 */
function resolve_bridge_balance_owner_discord_id(PDO $pdo, string $wallet, string $providedDiscordId): string {
    if ($providedDiscordId !== '') {
        validate_discord_id($providedDiscordId);
        require_wallet_matches_discord_id($pdo, $wallet, $providedDiscordId);
        return $providedDiscordId;
    }

    return resolve_discord_id_from_registered_wallet($pdo, $wallet);
}

/**
 * Resolve a partner request ID so every balance record stays traceable.
 * Plain language: Gensuki may send only wallet + API key; when no ID is sent, we create one.
 */
function resolve_partner_request_id(string $partnerRequestId): string {
    $partnerRequestId = trim($partnerRequestId);

    if ($partnerRequestId === '') {
        return 'gensuki-balance-' . time() . '-' . bin2hex(random_bytes(4));
    }

    if (strlen($partnerRequestId) > 120) {
        json_response([
            'success' => false,
            'error' => 'Invalid partner_request_id'
        ], 400);
    }

    return $partnerRequestId;
}

/**
 * Record one partner balance lookup with the server-authoritative timestamp.
 */
function insert_balance_query_record(PDO $pdo, array $record): int {
    $stmt = $pdo->prepare("\n        INSERT INTO tbl_spoinc_bridge_balance_queries (\n            partner_request_id,\n            partner_name,\n            discord_id,\n            wallet,\n            available_dspoinc,\n            total_dspoinc,\n            frozen_dspoinc,\n            conversion_rate_dspoinc_per_spoinc,\n            max_spoinc_convertible,\n            unix_timestamp,\n            metadata_json\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");

    $stmt->execute([
        $record['partner_request_id'],
        $record['partner_name'],
        $record['discord_id'],
        $record['wallet'],
        $record['available_dspoinc'],
        $record['total_dspoinc'],
        $record['frozen_dspoinc'],
        $record['conversion_rate_dspoinc_per_spoinc'],
        $record['max_spoinc_convertible'],
        $record['unix_timestamp'],
        $record['metadata_json']
    ]);

    return (int)$pdo->lastInsertId();
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed. Use POST.'
        ], 405);
    }

    $request = get_request_data();
    $pdo = get_spoinc_bridge_database_connection();
    ensure_spoinc_bridge_api_key_table($pdo);
    ensure_spoinc_bridge_balance_query_table($pdo);

    $authContext = require_partner_auth($pdo, $request);

    $partnerRequestId = trim((string)($request['partner_request_id'] ?? ''));
    $discordId = trim((string)($request['discord_id'] ?? ''));
    $wallet = trim((string)($request['wallet'] ?? ''));
    $partnerRequestedAtUnix = isset($request['requested_at_unix']) ? (int)$request['requested_at_unix'] : null;

    $partnerRequestId = resolve_partner_request_id($partnerRequestId);
    validate_solana_wallet($wallet);

    $resolvedDiscordId = resolve_bridge_balance_owner_discord_id($pdo, $wallet, $discordId);

    $serverUnixTimestamp = time();
    $balance = get_user_dspoinc_balance_snapshot($pdo, $resolvedDiscordId);
    $maxSpoincConvertible = $balance['available_dspoinc'] / SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC;

    $metadata = [
        'endpoint_version' => 'spoinc_bridge_api_agent_1_1_wallet_lookup_read_only_balance_db_key_hash',
        'auth_source' => $authContext['auth_source'],
        'api_key_id' => $authContext['api_key_id'],
        'key_label' => $authContext['key_label'],
        'provided_discord_id' => $discordId !== '' ? $discordId : null,
        'wallet_lookup_mode' => $discordId !== '' ? 'wallet_and_discord_id_verified' : 'wallet_only_resolved',
        'partner_requested_at_unix' => $partnerRequestedAtUnix,
        'server_time_utc' => gmdate('Y-m-d H:i:s', $serverUnixTimestamp),
        'dry_run_local_test' => is_localhost_env() && $authContext['auth_source'] === 'localhost_test_token'
    ];

    $queryId = insert_balance_query_record($pdo, [
        'partner_request_id' => $partnerRequestId,
        'partner_name' => SPOINC_BRIDGE_PARTNER_NAME,
        'discord_id' => $resolvedDiscordId,
        'wallet' => $wallet,
        'available_dspoinc' => $balance['available_dspoinc'],
        'total_dspoinc' => $balance['total_dspoinc'],
        'frozen_dspoinc' => $balance['frozen_dspoinc'],
        'conversion_rate_dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        'max_spoinc_convertible' => $maxSpoincConvertible,
        'unix_timestamp' => $serverUnixTimestamp,
        'metadata_json' => json_encode($metadata)
    ]);

    json_response([
        'success' => true,
        'partner' => SPOINC_BRIDGE_PARTNER_NAME,
        'partner_request_id' => $partnerRequestId,
        'query_id' => $queryId,
        'discord_id' => $resolvedDiscordId,
        'wallet' => $wallet,
        'available_dspoinc' => $balance['available_dspoinc'],
        'total_dspoinc' => $balance['total_dspoinc'],
        'frozen_dspoinc' => $balance['frozen_dspoinc'],
        'conversion' => [
            'dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
            'max_spoinc_convertible' => $maxSpoincConvertible
        ],
        'unix_timestamp' => $serverUnixTimestamp,
        'created_at' => gmdate('Y-m-d H:i:s', $serverUnixTimestamp)
    ]);
} catch (PDOException $e) {
    error_log('SPOINC Bridge Balance PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Database error while reading DSPOINC balance'
    ], 500);
} catch (Throwable $e) {
    error_log('SPOINC Bridge Balance ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Server error while reading DSPOINC balance'
    ], 500);
}
