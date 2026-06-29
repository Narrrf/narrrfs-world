<?php
/**
 * SPOINC Bridge shared helpers.
 *
 * DEVS FOR DECADES:
 * This helper supports safe read-only / preview APIs for the SPOINC bridge.
 * It must not:
 * - store API keys
 * - expose API keys
 * - call live Gensuki execution routes
 * - credit DSPOINC
 * - deduct DSPOINC
 * - send Solana transactions
 *
 * Final settlement waits for Zeno's live projectId, fee fields, /claim lifecycle,
 * transactionHash/signature rules, and idempotency confirmation.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

const SPOINC_BRIDGE_PARTNER_NAME = 'gensuki';
const SPOINC_BRIDGE_CONFIG_KEY = 'gensuki_spoinc_mainnet';
const SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC = 10000;
const SPOINC_BRIDGE_LOCAL_TEST_DISCORD_ID = '328601656659017732';

/**
 * Load the shared database connection.
 */
function spoinc_bridge_require_database(): void
{
    $databaseIncludeCandidates = [
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../config/database.php'
    ];

    foreach ($databaseIncludeCandidates as $databaseIncludePath) {
        if (file_exists($databaseIncludePath)) {
            require_once $databaseIncludePath;
            return;
        }
    }

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Database config not found'
    ], 500);
}

/**
 * Return a JSON response and stop execution.
 */
function spoinc_bridge_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Apply JSON headers and preflight handling.
 */
function spoinc_bridge_boot_json_api(array $allowedMethods = ['GET', 'POST', 'OPTIONS']): void
{
    header('Content-Type: application/json');
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
    header('Access-Control-Max-Age: 86400');

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

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowedMethods, true)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }
}

/**
 * Determine whether this request is localhost.
 */
function spoinc_bridge_is_localhost(): bool
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read JSON, POST, and GET request data once.
 */
function spoinc_bridge_get_request_data(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = [];

    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);

    if (is_array($json)) {
        $cached = $json;
    }

    if (!empty($_POST) && is_array($_POST)) {
        $cached = array_merge($cached, $_POST);
    }

    if (!empty($_GET) && is_array($_GET)) {
        $cached = array_merge($cached, $_GET);
    }

    return $cached;
}

/**
 * Validate a Solana wallet address shape.
 *
 * Plain language for DEVS:
 * This is a format check only. It does not prove wallet ownership.
 */
function spoinc_bridge_is_valid_solana_address(string $value): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value);
}

/**
 * Resolve the logged-in Discord user for frontend-safe APIs.
 *
 * Production:
 * - use Discord session only
 *
 * Localhost:
 * - allow user_id override for local browser and curl testing
 * - this mirrors Genesis Freezer local resolver behavior
 */
function spoinc_bridge_resolve_session_user_id(array $requestData): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($requestData['user_id'] ?? ''));

    if (spoinc_bridge_is_localhost() && $requestUserId !== '') {
        return $requestUserId;
    }

    if ($requestUserId !== '' && $requestUserId !== $sessionUserId) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    if (spoinc_bridge_is_localhost()) {
        return SPOINC_BRIDGE_LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Load the SPOINC bridge config row.
 */
function spoinc_bridge_load_config(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_spoinc_bridge_config
        WHERE config_key = ?
        LIMIT 1
    ");
    $stmt->execute([SPOINC_BRIDGE_CONFIG_KEY]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Load all SPOINC bridge route toggle rows.
 */
function spoinc_bridge_load_routes(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT *
        FROM tbl_spoinc_bridge_routes
        ORDER BY
            CASE
                WHEN route_key = 'DSPOINC_TO_SPOINC' THEN 1
                WHEN route_key = 'SPOINC_TO_DSPOINC' THEN 2
                WHEN route_key = 'SOL_TO_SPOINC' THEN 3
                WHEN route_key = 'EMPIRE_TO_SPOINC' THEN 4
                WHEN route_key = 'FOOK_TO_SPOINC' THEN 5
                ELSE 99
            END,
            route_key ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return only public-safe config fields.
 */
function spoinc_bridge_public_config_payload(?array $config): array
{
    if (!$config) {
        return [
            'exists' => false,
            'status' => 'missing_config'
        ];
    }

    return [
        'exists' => true,
        'config_key' => $config['config_key'],
        'partner_name' => $config['partner_name'],
        'api_base_url' => $config['api_base_url'],
        'project_id' => $config['project_id'],
        'project_id_ready' => trim((string)($config['project_id'] ?? '')) !== '',
        'token_symbol' => $config['token_symbol'],
        'token_mint' => $config['token_mint'],
        'token_decimals' => (int)$config['token_decimals'],
        'conversion_rate_dspoinc_per_spoinc' => (int)$config['conversion_rate_dspoinc_per_spoinc'],
        'spoinc_price_usd' => $config['spoinc_price_usd'],
        'pool_wallet' => $config['pool_wallet'],
        'admin_wallet' => $config['admin_wallet'],
        'funding_receiver' => $config['funding_receiver'],
        'public_enabled' => (int)$config['public_enabled'],
        'settlement_enabled' => (int)$config['settlement_enabled'],
        'external_sell_enabled' => (int)$config['external_sell_enabled'],
        'status' => $config['status'],
        'notes' => $config['notes'],
        'updated_at' => $config['updated_at']
    ];
}

/**
 * Return public-safe route fields.
 */
function spoinc_bridge_public_routes_payload(array $routes): array
{
    $payload = [];

    foreach ($routes as $route) {
        $payload[] = [
            'route_key' => $route['route_key'],
            'partner_name' => $route['partner_name'],
            'direction' => $route['direction'],
            'input_token' => $route['input_token'],
            'output_token' => $route['output_token'],
            'api_route' => $route['api_route'],
            'public_enabled' => (int)$route['public_enabled'],
            'backend_enabled' => (int)$route['backend_enabled'],
            'v1_public_allowed' => (int)$route['v1_public_allowed'],
            'requires_wallet_signature' => (int)$route['requires_wallet_signature'],
            'requires_gensuki_confirmation' => (int)$route['requires_gensuki_confirmation'],
            'requires_narrrfs_ledger_settlement' => (int)$route['requires_narrrfs_ledger_settlement'],
            'status' => $route['status'],
            'notes' => $route['notes']
        ];
    }

    return $payload;
}

/**
 * Resolve one wallet to the latest verified Discord ownership row.
 */
function spoinc_bridge_resolve_discord_id_for_wallet(PDO $pdo, string $wallet): ?string
{
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM tbl_holder_verifications
        WHERE wallet = ?
        ORDER BY
            COALESCE(verified_at, '') DESC,
            id DESC
        LIMIT 1
    ");
    $stmt->execute([$wallet]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row['user_id'] ?? null;
}

/**
 * Calculate backend-authoritative DSPOINC balances.
 *
 * Plain language for DEVS:
 * Available DSPOINC = total score ledger - active frozen stake amount.
 */
function spoinc_bridge_calculate_dspoinc_balance(PDO $pdo, string $discordId): array
{
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0) AS total_balance
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $totalStmt->execute([$discordId]);
    $totalBalance = (int)($totalStmt->fetch(PDO::FETCH_ASSOC)['total_balance'] ?? 0);

    $frozenStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS frozen_balance
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $frozenStmt->execute([$discordId]);
    $frozenBalance = (int)($frozenStmt->fetch(PDO::FETCH_ASSOC)['frozen_balance'] ?? 0);

    $availableBalance = max(0, $totalBalance - $frozenBalance);
    $maxSpoincConvertible = $availableBalance / SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC;

    return [
        'total_dspoinc' => $totalBalance,
        'frozen_dspoinc' => $frozenBalance,
        'available_dspoinc' => $availableBalance,
        'conversion' => [
            'dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
            'max_spoinc_convertible' => $maxSpoincConvertible
        ]
    ];
}

/**
 * Open a PDO database connection.
 */
function spoinc_bridge_open_database(): PDO
{
    spoinc_bridge_require_database();

    if (!function_exists('getDatabaseConnection')) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Database connection helper missing'
        ], 500);
    }

    return getDatabaseConnection();
}