<?php
/**
 * SPOINC Bridge API — Safe Gensuki Token Price Proxy.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint lets Swap Lab ask Gensuki for a public token price preview
 * without exposing the Gensuki outbound API key in browser JavaScript.
 *
 * This endpoint must never:
 * - expose the Gensuki API key
 * - create a transaction
 * - deduct DSPOINC
 * - credit DSPOINC
 * - settle bridge intents
 * - enable public bridge execution
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

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
 * Parse a positive decimal amount without allowing unsafe text.
 */
function normalize_positive_decimal(string $amount): string
{
    $amount = trim($amount);

    if ($amount === '') {
        json_response([
            'success' => false,
            'error' => 'Token amount is required.'
        ], 400);
    }

    if (!preg_match('/^\d+(\.\d{1,9})?$/', $amount)) {
        json_response([
            'success' => false,
            'error' => 'Token amount must be a positive number with max 9 decimals.'
        ], 400);
    }

    if ((float)$amount <= 0) {
        json_response([
            'success' => false,
            'error' => 'Token amount must be greater than zero.'
        ], 400);
    }

    return rtrim(rtrim(number_format((float)$amount, 9, '.', ''), '0'), '.');
}

/**
 * Allow only known Narrrfs payment tokens through this proxy.
 *
 * Plain language for DEVS FOR DECADES:
 * This prevents the endpoint from becoming a generic open price proxy.
 */
function resolve_allowed_payment_token(string $address): array
{
    $normalizedAddress = strtolower(trim($address));

    $tokens = [
        [
            'symbol' => 'EMPIRE',
            'address' => 'EmpirdtfUMfBQXEjnNmTngeimjfizfuSBD3TN9zqzydj'
        ],
        [
            'symbol' => 'FOOK',
            'address' => 'G63a43wp5PKXBPo6VeMJUBfdUVjRRskVwqEZfwWRpump'
        ],
        [
            'symbol' => 'SOL',
            'address' => '11111111111111111111111111111111'
        ]
    ];

    foreach ($tokens as $token) {
        if ($normalizedAddress === strtolower($token['address'])) {
            return $token;
        }
    }

    json_response([
        'success' => false,
        'error' => 'Unsupported payment token for SPOINC price preview.'
    ], 400);
}

/**
 * Read the first positive number from possible Gensuki response fields.
 *
 * Plain language for DEVS FOR DECADES:
 * Zeno docs show getTokenPrice can return different conversion fields depending
 * on whether we ask with amount/token/sol or usd. We do not assume one name.
 */
function read_first_positive_number(array $data, array $fields): ?float
{
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data)) {
            continue;
        }

        if (!is_numeric($data[$field])) {
            continue;
        }

        $value = (float)$data[$field];

        if ($value > 0) {
            return $value;
        }
    }

    return null;
}

function fetch_gensuki_token_price(string $apiBaseUrl, string $apiKey, string $address, string $amount): array
{
    $baseUrl = rtrim($apiBaseUrl, '/');

    $query = http_build_query([
        'address' => $address,
        'amount' => $amount
    ]);

    $url = $baseUrl . '/api/custom-token-presale/getTokenPrice?' . $query;

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
        throw new RuntimeException('Gensuki token price request failed.');
    }

    $decoded = json_decode((string)$rawResponse, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Gensuki returned invalid token price JSON.');
    }

    return [
        'http_code' => $httpCode,
        'json' => $decoded
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_response([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}

$apiKey = read_bridge_env_value('GENSUKI_OUTBOUND_API_KEY');
$apiBaseUrl = read_bridge_env_value('GENSUKI_API_BASE_URL');

if ($apiBaseUrl === '') {
    $apiBaseUrl = 'https://app.gensuki.xyz';
}

if ($apiKey === '') {
    json_response([
        'success' => false,
        'error' => 'Gensuki outbound bridge environment is not configured.',
        'data' => [
            'api_key_configured' => false
        ]
    ], 503);
}

$address = trim((string)($_GET['address'] ?? ''));
$amount = normalize_positive_decimal((string)($_GET['amount'] ?? ''));

if ($address === '') {
    json_response([
        'success' => false,
        'error' => 'Token address is required.'
    ], 400);
}

$token = resolve_allowed_payment_token($address);

try {
    $gensukiResponse = fetch_gensuki_token_price($apiBaseUrl, $apiKey, $token['address'], $amount);
    $decoded = $gensukiResponse['json'];

    if ($gensukiResponse['http_code'] < 200 || $gensukiResponse['http_code'] >= 300 || empty($decoded['success'])) {
        json_response([
            'success' => false,
            'error' => 'Gensuki returned a non-success token price response.',
            'data' => [
                'http_code' => $gensukiResponse['http_code']
            ]
        ], 502);
    }

    $priceUsd = read_first_positive_number($decoded, ['priceUsd', 'price_usd', 'tokenPriceUsd']);
    $inputUsd = read_first_positive_number($decoded, ['inputUsd', 'usdWorth', 'usd_worth', 'amountUsd', 'amount_usd', 'valueUsd', 'value_usd']);

    if ($inputUsd === null && $priceUsd !== null) {
        $inputUsd = ((float)$amount) * $priceUsd;
    }

    json_response([
        'success' => true,
        'data' => [
            'symbol' => $token['symbol'],
            'address' => $token['address'],
            'amount' => $amount,
            'price_usd' => $priceUsd,
            'input_usd' => $inputUsd,
            'quote_available' => $inputUsd !== null,
            'api_key_exposed' => false,
            'transaction_created' => false,
            'ledger_movement_enabled' => false
        ]
    ]);
} catch (Throwable $error) {
    error_log('❌ SPOINC Gensuki token price proxy failed: ' . $error->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load Gensuki token price preview.'
    ], 502);
}