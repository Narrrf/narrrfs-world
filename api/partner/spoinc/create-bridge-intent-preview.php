<?php
/**
 * SPOINC Bridge Intent Preview API.
 *
 * Plain language for DEVS:
 * This private tester endpoint creates a bridge intent preview row only.
 * It is for Narrrf + justme local/private testing before public launch.
 *
 * This endpoint must never:
 * - call Gensuki transaction endpoints
 * - create a Solana transaction
 * - call /claim
 * - call /confirm
 * - deduct DSPOINC
 * - credit DSPOINC
 * - settle an intent
 * - expose any API key
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

require_once __DIR__ . '/bridge-tester-helpers.php';

$databaseIncludeCandidates = [
    __DIR__ . '/../../../config/database.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php'
];

$databaseLoaded = false;

foreach ($databaseIncludeCandidates as $databaseIncludePath) {
    if (file_exists($databaseIncludePath)) {
        require_once $databaseIncludePath;
        $databaseLoaded = true;
        break;
    }
}

if (!$databaseLoaded || !function_exists('getDatabaseConnection')) {
    spoinc_bridge_tester_json_response([
        'success' => false,
        'error' => 'Database connection helper not found.'
    ], 500);
}

const SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC = 10000;
const SPOINC_BRIDGE_ALLOWED_PREVIEW_ROUTES = [
    'DSPOINC_TO_SPOINC',
    'SPOINC_TO_DSPOINC'
];

/**
 * Validate a Solana wallet address shape.
 *
 * Plain language for DEVS:
 * This is a format guard only. It does not prove wallet ownership.
 */
function spoinc_bridge_intent_is_valid_solana_wallet(string $wallet): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet);
}

/**
 * Return a stable decimal string without scientific notation.
 */
function spoinc_bridge_intent_format_decimal(float $value, int $decimals = 9): string
{
    $formatted = number_format($value, $decimals, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');

    return $formatted === '' ? '0' : $formatted;
}

/**
 * Generate an idempotency id for private test intents.
 *
 * Plain language for DEVS:
 * This lets later transaction tests connect back to the same preview intent.
 */
function spoinc_bridge_intent_create_idempotency_id(string $userId, string $routeKey): string
{
    $randomBytes = bin2hex(random_bytes(8));
    $timestamp = gmdate('YmdHis');

    return 'spoinc-test-' . $userId . '-' . strtolower($routeKey) . '-' . $timestamp . '-' . $randomBytes;
}

/**
 * Load one route row and keep route rules backend-authoritative.
 */
function spoinc_bridge_intent_load_route(PDO $pdo, string $routeKey): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_spoinc_bridge_routes
        WHERE route_key = ?
        LIMIT 1
    ");
    $stmt->execute([$routeKey]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Load public bridge config for project id and price context.
 */
function spoinc_bridge_intent_load_config(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_spoinc_bridge_config
        WHERE config_key = 'gensuki_spoinc_mainnet'
        LIMIT 1
    ");
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: [];
}

/**
 * Build safe preview amounts for the two internal Narrrfs bridge routes.
 *
 * Plain language for DEVS:
 * This only calculates display/intent math.
 * It does not move DSPOINC or SPOINC.
 */
function spoinc_bridge_intent_build_amount_preview(string $routeKey, string $amount): array
{
    $amount = trim($amount);

    if ($amount === '' || !is_numeric($amount)) {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'Amount is required and must be numeric.'
        ], 400);
    }

    $numericAmount = (float)$amount;

    if ($numericAmount <= 0) {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'Amount must be greater than zero.'
        ], 400);
    }

    if ($routeKey === 'DSPOINC_TO_SPOINC') {
        $dspoincAmount = (int)floor($numericAmount);

        if ($dspoincAmount < 1) {
            spoinc_bridge_tester_json_response([
                'success' => false,
                'error' => 'DSPOINC amount must be at least 1.'
            ], 400);
        }

        $spoincAmount = $dspoincAmount / SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC;

        return [
            'input_amount' => (string)$dspoincAmount,
            'expected_output_amount' => spoinc_bridge_intent_format_decimal($spoincAmount, 9),
            'minimum_output_amount' => spoinc_bridge_intent_format_decimal($spoincAmount, 9),
            'dspoinc_amount' => $dspoincAmount,
            'spoinc_amount' => spoinc_bridge_intent_format_decimal($spoincAmount, 9)
        ];
    }

    if ($routeKey === 'SPOINC_TO_DSPOINC') {
        $spoincAmount = $numericAmount;
        $dspoincAmount = (int)floor($spoincAmount * SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC);

        if ($dspoincAmount < 1) {
            spoinc_bridge_tester_json_response([
                'success' => false,
                'error' => 'SPOINC amount is too small to convert into at least 1 DSPOINC.'
            ], 400);
        }

        return [
            'input_amount' => spoinc_bridge_intent_format_decimal($spoincAmount, 9),
            'expected_output_amount' => (string)$dspoincAmount,
            'minimum_output_amount' => (string)$dspoincAmount,
            'dspoinc_amount' => $dspoincAmount,
            'spoinc_amount' => spoinc_bridge_intent_format_decimal($spoincAmount, 9)
        ];
    }

    spoinc_bridge_tester_json_response([
        'success' => false,
        'error' => 'This route is not available for private intent preview yet.'
    ], 400);
}

/**
 * Create the private test intent row.
 */
function spoinc_bridge_intent_insert_preview(
    PDO $pdo,
    array $config,
    array $route,
    string $userId,
    string $wallet,
    string $idempotencyId,
    array $amountPreview,
    array $requestData
): int {
    $projectId = (string)($config['project_id'] ?? '');

    $rawQuote = [
        'mode' => 'private_test_preview',
        'conversion_rate_dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        'route_key' => $route['route_key'],
        'input_token' => $route['input_token'],
        'output_token' => $route['output_token'],
        'input_amount' => $amountPreview['input_amount'],
        'expected_output_amount' => $amountPreview['expected_output_amount'],
        'minimum_output_amount' => $amountPreview['minimum_output_amount'],
        'ledger_movement_enabled' => false,
        'transaction_created' => false
    ];

    $stmt = $pdo->prepare("
        INSERT INTO tbl_spoinc_bridge_intents (
            idempotency_id,
            partner_name,
            project_id,
            discord_id,
            wallet,
            route_key,
            direction,
            input_token,
            output_token,
            input_amount,
            expected_output_amount,
            minimum_output_amount,
            dspoinc_amount,
            spoinc_amount,
            conversion_rate_dspoinc_per_spoinc,
            status,
            gensuki_status,
            narrrfs_status,
            raw_quote_json,
            raw_request_json,
            raw_response_json,
            expires_at
        ) VALUES (
            :idempotency_id,
            :partner_name,
            :project_id,
            :discord_id,
            :wallet,
            :route_key,
            :direction,
            :input_token,
            :output_token,
            :input_amount,
            :expected_output_amount,
            :minimum_output_amount,
            :dspoinc_amount,
            :spoinc_amount,
            :conversion_rate,
            :status,
            :gensuki_status,
            :narrrfs_status,
            :raw_quote_json,
            :raw_request_json,
            :raw_response_json,
            datetime('now', '+30 minutes')
        )
    ");

    $stmt->execute([
        ':idempotency_id' => $idempotencyId,
        ':partner_name' => 'gensuki',
        ':project_id' => $projectId !== '' ? $projectId : null,
        ':discord_id' => $userId,
        ':wallet' => $wallet,
        ':route_key' => $route['route_key'],
        ':direction' => $route['direction'],
        ':input_token' => $route['input_token'],
        ':output_token' => $route['output_token'],
        ':input_amount' => $amountPreview['input_amount'],
        ':expected_output_amount' => $amountPreview['expected_output_amount'],
        ':minimum_output_amount' => $amountPreview['minimum_output_amount'],
        ':dspoinc_amount' => $amountPreview['dspoinc_amount'],
        ':spoinc_amount' => $amountPreview['spoinc_amount'],
        ':conversion_rate' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
        ':status' => 'private_test_preview',
        ':gensuki_status' => 'not_submitted',
        ':narrrfs_status' => 'not_settled',
        ':raw_quote_json' => json_encode($rawQuote),
        ':raw_request_json' => json_encode($requestData),
        ':raw_response_json' => json_encode([
            'success' => true,
            'message' => 'Private bridge intent preview created. No transaction or ledger movement happened.'
        ])
    ]);

    return (int)$pdo->lastInsertId();
}

$requestData = spoinc_bridge_tester_get_request_data();
$userId = spoinc_bridge_tester_require_access();

$routeKey = strtoupper(trim((string)($requestData['route_key'] ?? '')));
$wallet = trim((string)($requestData['wallet'] ?? ''));
$amount = trim((string)($requestData['amount'] ?? ''));

if ($routeKey === '') {
    spoinc_bridge_tester_json_response([
        'success' => false,
        'error' => 'route_key is required.'
    ], 400);
}

if (!in_array($routeKey, SPOINC_BRIDGE_ALLOWED_PREVIEW_ROUTES, true)) {
    spoinc_bridge_tester_json_response([
        'success' => false,
        'error' => 'Only DSPOINC_TO_SPOINC and SPOINC_TO_DSPOINC are enabled for private intent preview.'
    ], 400);
}

if ($wallet === '' || !spoinc_bridge_intent_is_valid_solana_wallet($wallet)) {
    spoinc_bridge_tester_json_response([
        'success' => false,
        'error' => 'A valid Solana wallet is required.'
    ], 400);
}

try {
    $pdo = getDatabaseConnection();
    $route = spoinc_bridge_intent_load_route($pdo, $routeKey);

    if (!$route) {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'Bridge route not found.'
        ], 404);
    }

    if ((int)$route['v1_public_allowed'] !== 1) {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'This bridge route is protected and not available for V1 private preview.'
        ], 403);
    }

    $config = spoinc_bridge_intent_load_config($pdo);
    $amountPreview = spoinc_bridge_intent_build_amount_preview($routeKey, $amount);
    $idempotencyId = spoinc_bridge_intent_create_idempotency_id($userId, $routeKey);

    $intentId = spoinc_bridge_intent_insert_preview(
        $pdo,
        $config,
        $route,
        $userId,
        $wallet,
        $idempotencyId,
        $amountPreview,
        $requestData
    );

    spoinc_bridge_tester_json_response([
        'success' => true,
        'data' => [
            'intent_id' => $intentId,
            'idempotency_id' => $idempotencyId,
            'user_id' => $userId,
            'wallet' => $wallet,
            'route' => [
                'route_key' => $route['route_key'],
                'direction' => $route['direction'],
                'input_token' => $route['input_token'],
                'output_token' => $route['output_token'],
                'status' => 'Private test preview created'
            ],
            'preview' => [
                'input_amount' => $amountPreview['input_amount'],
                'expected_output_amount' => $amountPreview['expected_output_amount'],
                'minimum_output_amount' => $amountPreview['minimum_output_amount'],
                'dspoinc_amount' => $amountPreview['dspoinc_amount'],
                'spoinc_amount' => $amountPreview['spoinc_amount'],
                'conversion_rate_dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC
            ],
            'safety' => [
                'transaction_created' => false,
                'gensuki_called' => false,
                'ledger_movement_enabled' => false,
                'dspoinc_deducted' => false,
                'dspoinc_credited' => false,
                'api_key_exposed' => false,
                'message' => 'Private intent preview created. No bridge execution or ledger movement happened.'
            ]
        ]
    ]);
} catch (Throwable $e) {
    error_log('❌ SPOINC bridge intent preview failed: ' . $e->getMessage());

    spoinc_bridge_tester_json_response([
        'success' => false,
        'error' => 'Failed to create private bridge intent preview.'
    ], 500);
}