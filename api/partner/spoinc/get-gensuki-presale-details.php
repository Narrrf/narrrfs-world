<?php
/**
 * SPOINC Bridge API — Safe Gensuki Presale Details Proxy.
 *
 * Plain language for DEVS:
 * This endpoint lets Narrrfs backend call the Gensuki SPOINC project details API
 * with the backend-only outbound API key.
 *
 * This endpoint must never:
 * - expose the Gensuki API key
 * - generate a transaction
 * - deduct DSPOINC
 * - credit DSPOINC
 * - settle bridge intents
 * - enable public bridge execution
 *
 * It only returns public-safe SPOINC project details for Swap Lab display/testing.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

/**
 * Local-only config support.
 *
 * Plain language for DEVS:
 * XAMPP Apache does not automatically read Git Bash export variables.
 * Render production uses real environment variables.
 * Localhost may load api/config/gensuki-outbound-local.php if it exists.
 */
function is_spoinc_localhost_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

if (is_spoinc_localhost_request()) {
    $localGensukiConfigPath = __DIR__ . '/../../config/gensuki-outbound-local.php';

    if (file_exists($localGensukiConfigPath)) {
        require_once $localGensukiConfigPath;

        if (!empty($GENSUKI_OUTBOUND_API_KEY)) {
            $_SERVER['GENSUKI_OUTBOUND_API_KEY'] = $GENSUKI_OUTBOUND_API_KEY;
        }

        if (!empty($GENSUKI_SPOINC_PROJECT_ID)) {
            $_SERVER['GENSUKI_SPOINC_PROJECT_ID'] = $GENSUKI_SPOINC_PROJECT_ID;
        }

        if (!empty($GENSUKI_API_BASE_URL)) {
            $_SERVER['GENSUKI_API_BASE_URL'] = $GENSUKI_API_BASE_URL;
        }
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

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

/**
 * Return a clean JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Read one environment value from getenv / $_ENV / $_SERVER.
 *
 * Plain language for DEVS:
 * Render and local Apache can expose env variables differently.
 * This helper checks the common PHP locations without logging secrets.
 */
function read_bridge_env_value(string $key): string
{
    $value = getenv($key);

    if (is_string($value) && trim($value) !== '') {
        return trim($value);
    }

    if (isset($_ENV[$key]) && trim((string)$_ENV[$key]) !== '') {
        return trim((string)$_ENV[$key]);
    }

    if (isset($_SERVER[$key]) && trim((string)$_SERVER[$key]) !== '') {
        return trim((string)$_SERVER[$key]);
    }

    return '';
}

/**
 * Validate the project id shape before calling Gensuki.
 *
 * Plain language for DEVS:
 * This only checks UUID format. The Gensuki API still confirms project ownership
 * through the x-api-key + project isolation rules.
 */
function is_valid_bridge_project_id(string $projectId): bool
{
    return (bool)preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        $projectId
    );
}

/**
 * Perform a backend-only GET request to Gensuki.
 *
 * Plain language for DEVS:
 * The x-api-key header is sent only from PHP backend to Gensuki.
 * It is never returned to frontend users.
 */
function fetch_gensuki_presale_details(string $apiBaseUrl, string $projectId, string $apiKey): array
{
    $baseUrl = rtrim($apiBaseUrl, '/');
    $url = $baseUrl . '/api/custom-token-presale/getPresaleDetails?ids=' . rawurlencode($projectId);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Narrrfs-World-SPOINC-Bridge/1.0',
        'x-api-key: ' . $apiKey
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($rawResponse === false || $curlError !== '') {
        throw new RuntimeException('Gensuki request failed.');
    }

    $decoded = json_decode((string)$rawResponse, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Gensuki returned invalid JSON.');
    }

    return [
        'http_code' => $httpCode,
        'raw' => $rawResponse,
        'json' => $decoded
    ];
}

/**
 * Return only public-safe fields needed by Swap Lab.
 *
 * Plain language for DEVS:
 * This intentionally strips private request headers and any unexpected raw data.
 * If more public fields are needed later, add them explicitly.
 */
function build_public_presale_payload(array $gensukiProject): array
{
    $tokens = is_array($gensukiProject['tokens'] ?? null) ? $gensukiProject['tokens'] : [];
    $socials = is_array($gensukiProject['socials'] ?? null) ? $gensukiProject['socials'] : [];

    return [
        'project_id' => (string)($gensukiProject['projectId'] ?? ''),
        'project_name' => (string)($gensukiProject['projectName'] ?? ''),
        'image_url' => (string)($gensukiProject['imageUrl'] ?? ''),
        'status' => (string)($gensukiProject['status'] ?? ''),
        'chain' => (string)($gensukiProject['chain'] ?? ''),
        'description' => (string)($gensukiProject['description'] ?? ''),

        'token_price_usd' => (string)($gensukiProject['tokenPriceUsd'] ?? $gensukiProject['tokenPrice'] ?? ''),
        'token_price_native' => (string)($gensukiProject['tokenPriceNative'] ?? ''),
        'token_sold' => (string)($gensukiProject['tokenSold'] ?? ''),
        'total_raised' => (string)($gensukiProject['totalRaised'] ?? ''),
        'total_usd_raise' => (string)($gensukiProject['totalUsdRaise'] ?? ''),
        'target_raised_amount' => (string)($gensukiProject['targetRaisedAmount'] ?? ''),
        'min_buy_usd_amount' => (string)($gensukiProject['minBuyUsdAmount'] ?? ''),

        'project_fee' => (string)($gensukiProject['projectFee'] ?? ''),
        'platform_fee' => (string)($gensukiProject['platformFee'] ?? ''),
        'disable_sell' => !empty($gensukiProject['disableSell']),

        'allowed_tokens' => is_array($gensukiProject['allowedTokens'] ?? null)
            ? array_values($gensukiProject['allowedTokens'])
            : [],

        'tokenomics' => is_array($gensukiProject['tokenomics'] ?? null)
            ? array_values($gensukiProject['tokenomics'])
            : [],

        'socials' => [
            'twitter' => (string)($socials['twitter'] ?? $gensukiProject['twitter'] ?? ''),
            'discord' => (string)($socials['discord'] ?? $gensukiProject['discord'] ?? ''),
            'website' => (string)($socials['website'] ?? $gensukiProject['website'] ?? '')
        ],

        'tokens' => [
            'token_address' => (string)($tokens['tokenAddress'] ?? ''),
            'token_b_address' => (string)($tokens['tokenBAddress'] ?? ''),
            'pool_address' => (string)($tokens['poolAddress'] ?? ''),
            'admin_wallet' => (string)($tokens['adminWallet'] ?? ''),
            'funding_receiver' => (string)($tokens['fundingReceiver'] ?? ''),
            'token_type_2022' => !empty($tokens['tokenType2022'])
        ]
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_response([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}

$apiKey = read_bridge_env_value('GENSUKI_OUTBOUND_API_KEY');
$projectId = read_bridge_env_value('GENSUKI_SPOINC_PROJECT_ID');
$apiBaseUrl = read_bridge_env_value('GENSUKI_API_BASE_URL');

if ($apiBaseUrl === '') {
    $apiBaseUrl = 'https://app.gensuki.xyz';
}

if ($apiKey === '' || $projectId === '') {
    json_response([
        'success' => false,
        'error' => 'Gensuki outbound bridge environment is not configured.',
        'data' => [
            'api_key_configured' => $apiKey !== '',
            'project_id_configured' => $projectId !== '',
            'api_base_url_configured' => $apiBaseUrl !== ''
        ]
    ], 503);
}

if (!is_valid_bridge_project_id($projectId)) {
    json_response([
        'success' => false,
        'error' => 'Configured Gensuki SPOINC project id is invalid.'
    ], 500);
}

try {
    $gensukiResponse = fetch_gensuki_presale_details($apiBaseUrl, $projectId, $apiKey);
    $decoded = $gensukiResponse['json'];

    if ($gensukiResponse['http_code'] < 200 || $gensukiResponse['http_code'] >= 300) {
        json_response([
            'success' => false,
            'error' => 'Gensuki returned a non-success HTTP response.',
            'data' => [
                'http_code' => $gensukiResponse['http_code']
            ]
        ], 502);
    }

    if (empty($decoded['success']) || !is_array($decoded['data'] ?? null) || empty($decoded['data'][0])) {
        json_response([
            'success' => false,
            'error' => 'Gensuki response did not include SPOINC project data.'
        ], 502);
    }

    $project = $decoded['data'][0];
    $publicProject = build_public_presale_payload($project);

    json_response([
        'success' => true,
        'data' => [
            'project' => $publicProject,
            'safety' => [
                'api_key_exposed' => false,
                'transaction_created' => false,
                'ledger_movement_enabled' => false,
                'message' => 'Public-safe Gensuki SPOINC project details loaded through Narrrfs backend.'
            ],
            'fetched_at' => gmdate('c')
        ]
    ]);
} catch (Throwable $e) {
    error_log('❌ SPOINC Gensuki presale details proxy failed: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load Gensuki SPOINC project details.'
    ], 502);
}