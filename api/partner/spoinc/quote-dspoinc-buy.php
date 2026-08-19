<?php
/**
 * SPOINC Bridge API — Quote dSPOINC buy with SOL.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint is quote-only for the reusable frontend dSPOINC Buy Widget.
 *
 * It helps the user answer:
 * "If I want X dSPOINC, how much SPOINC is needed and about how much SOL
 * will I pay through the bridge?"
 *
 * It must never:
 * - create a Gensuki buy transaction
 * - create a Gensuki claim transaction
 * - confirm Gensuki complete/failed
 * - credit DSPOINC
 * - debit DSPOINC
 * - settle SPOINC
 * - write database rows
 * - mutate staking, freezer, Lab, Reward Chamber, or profile state
 */

require_once __DIR__ . '/bridge-helpers.php';

const DSPOINC_BUY_QUOTE_ROUTE_KEY_SOL = 'SOL_TO_SPOINC';
const DSPOINC_BUY_QUOTE_ROUTE_KEY_CLAIM = 'SPOINC_TO_DSPOINC';
const DSPOINC_BUY_QUOTE_MIN_DSPOINC = 10000;
const DSPOINC_BUY_QUOTE_MAX_DSPOINC = 100000000;
const DSPOINC_BUY_QUOTE_NATIVE_SOL_ADDRESS = '11111111111111111111111111111111';
const DSPOINC_BUY_QUOTE_PRESALE_DETAILS_PATH = '/api/custom-token-presale/getPresaleDetails';
const DSPOINC_BUY_QUOTE_SOL_PRICE_PATH = '/api/custom-token-presale/getSolanaPrice';

spoinc_bridge_boot_json_api(['POST', 'OPTIONS']);

/**
 * Return a Gensuki outbound API key without exposing it.
 */
function dspoinc_buy_quote_get_gensuki_api_key(): string
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

    /**
     * Match the existing create/confirm bridge endpoints.
     *
     * DEVS FOR DECADES:
     * Local XAMPP/Apache does not automatically inherit Git Bash env vars.
     * The ignored local file lets local quote tests authenticate to Gensuki
     * without exposing the key to frontend, GitHub, Discord, screenshots, or
     * QUICK_STATUS.
     */
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

    if (isset($GLOBALS['GENSUKI_OUTBOUND_API_KEY'])) {
        return trim((string)$GLOBALS['GENSUKI_OUTBOUND_API_KEY']);
    }

    return '';
}

/**
 * Normalize a positive dSPOINC integer from request data.
 */
function dspoinc_buy_quote_normalize_dspoinc_amount($value): int
{
    $raw = preg_replace('/[^\d]/', '', (string)$value);

    if ($raw === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'dSPOINC amount is required.'
        ], 400);
    }

    $amount = (int)$raw;

    if ($amount < DSPOINC_BUY_QUOTE_MIN_DSPOINC) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'dSPOINC amount is below the minimum quote amount.',
            'min_dspoinc' => DSPOINC_BUY_QUOTE_MIN_DSPOINC
        ], 400);
    }

    if ($amount > DSPOINC_BUY_QUOTE_MAX_DSPOINC) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'dSPOINC amount is above the maximum quote amount.',
            'max_dspoinc' => DSPOINC_BUY_QUOTE_MAX_DSPOINC
        ], 400);
    }

    return $amount;
}

/**
 * Convert exact integer dSPOINC into a SPOINC amount string with max 4 decimals.
 *
 * 10,000 dSPOINC = 1 SPOINC.
 * 1 dSPOINC = 0.0001 SPOINC.
 */
function dspoinc_buy_quote_spoinc_amount_from_dspoinc(int $dspoincAmount): string
{
    $whole = intdiv($dspoincAmount, SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC);
    $fraction = $dspoincAmount % SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC;

    if ($fraction === 0) {
        return (string)$whole;
    }

    return $whole . '.' . rtrim(str_pad((string)$fraction, 4, '0', STR_PAD_LEFT), '0');
}

/**
 * Build a safe GET URL for a Gensuki custom-token-presale route.
 */
function dspoinc_buy_quote_build_gensuki_get_url(array $config, string $path, array $query): string
{
    $baseUrl = rtrim((string)($config['api_base_url'] ?? ''), '/');

    if ($baseUrl === '') {
        return '';
    }

    return $baseUrl . $path . '?' . http_build_query($query);
}

/**
 * Perform one quote-only GET request to Gensuki.
 *
 * This does not call buy, claim, confirm, status mutation, or any write route.
 */
function dspoinc_buy_quote_append_api_key_to_url(string $url, string $apiKey): string
{
    if ($url === '' || $apiKey === '') {
        return $url;
    }

    $separator = str_contains($url, '?') ? '&' : '?';

    return $url . $separator . 'apiKey=' . rawurlencode($apiKey);
}

function dspoinc_buy_quote_fetch_gensuki_json_once(string $url, array $headers): array
{
    if ($url === '') {
        return [
            'success' => false,
            'http_code' => 0,
            'json' => null,
            'error' => 'Empty Gensuki quote URL.'
        ];
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => $headers
    ]);

    $rawBody = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($rawBody === false || $rawBody === null || $rawBody === '') {
        return [
            'success' => false,
            'http_code' => $httpCode,
            'json' => null,
            'error' => $error ?: 'Empty response from Gensuki quote route.'
        ];
    }

    $json = json_decode((string)$rawBody, true);

    if (!is_array($json)) {
        return [
            'success' => false,
            'http_code' => $httpCode,
            'json' => null,
            'error' => 'Non-JSON response from Gensuki quote route.'
        ];
    }

    return [
        'success' => $httpCode >= 200 && $httpCode < 300 && !empty($json['success']),
        'http_code' => $httpCode,
        'json' => $json,
        'error' => $error
    ];
}

/**
 * Perform one quote-only GET request to Gensuki.
 *
 * This does not call buy, claim, confirm, status mutation, or any write route.
 */
function dspoinc_buy_quote_fetch_gensuki_json(string $url, string $apiKey): array
{
    $headers = [
        'Accept: application/json'
    ];

    if ($apiKey !== '') {
        $headers[] = 'x-api-key: ' . $apiKey;
    }

    $result = dspoinc_buy_quote_fetch_gensuki_json_once($url, $headers);

    /**
     * Gensuki docs allow x-api-key header or apiKey query param.
     * If the read-only GET route rejects the header locally with 401,
     * retry once with apiKey in the server-side outbound URL.
     *
     * This API key is never returned to the frontend.
     */
    if ((int)($result['http_code'] ?? 0) === 401 && $apiKey !== '') {
        $fallbackUrl = dspoinc_buy_quote_append_api_key_to_url($url, $apiKey);
        $fallbackResult = dspoinc_buy_quote_fetch_gensuki_json_once($fallbackUrl, ['Accept: application/json']);
        $fallbackResult['auth_fallback_used'] = true;
        return $fallbackResult;
    }

    $result['auth_fallback_used'] = false;

    return $result;
}

/**
 * Return the first presale detail row from Gensuki response shape.
 */
function dspoinc_buy_quote_extract_presale_detail(array $json): array
{
    $data = $json['data'] ?? null;

    if (is_array($data) && array_is_list($data) && isset($data[0]) && is_array($data[0])) {
        return $data[0];
    }

    if (is_array($data) && isset($data['projectId'])) {
        return $data;
    }

    return [];
}

/**
 * Return a positive decimal string or null.
 */
function dspoinc_buy_quote_positive_decimal_or_null($value): ?string
{
    $normalized = trim((string)$value);

    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }

    if ((float)$normalized <= 0) {
        return null;
    }

    return $normalized;
}

/**
 * Multiply two decimal-ish values and return a display-safe decimal string.
 */
function dspoinc_buy_quote_multiply_decimal(string $amount, string $price, int $scale = 9): string
{
    $result = (float)$amount * (float)$price;

    if (!is_finite($result) || $result <= 0) {
        return '';
    }

    return rtrim(rtrim(number_format($result, $scale, '.', ''), '0'), '.');
}

try {
    $requestData = spoinc_bridge_get_request_data();
    $pdo = spoinc_bridge_open_database();

    /**
     * Route gates are read-only checks here.
     * They ensure this quote is not shown as available when the underlying
     * SOL buy or SPOINC->DSPOINC settlement route is not allowed for the user.
     */
    $userId = spoinc_bridge_require_public_route_access(
        $pdo,
        $requestData,
        DSPOINC_BUY_QUOTE_ROUTE_KEY_SOL,
        false
    );

    spoinc_bridge_require_public_route_access(
        $pdo,
        $requestData,
        DSPOINC_BUY_QUOTE_ROUTE_KEY_CLAIM,
        true
    );

    $wallet = trim((string)($requestData['wallet'] ?? ''));

    if ($wallet !== '' && !spoinc_bridge_is_valid_solana_address($wallet)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'A valid Solana wallet is required for this quote.'
        ], 400);
    }

    $dspoincAmount = dspoinc_buy_quote_normalize_dspoinc_amount(
        $requestData['dspoinc_amount'] ?? ($requestData['amount'] ?? '')
    );

    $spoincAmount = dspoinc_buy_quote_spoinc_amount_from_dspoinc($dspoincAmount);
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

    $apiKey = dspoinc_buy_quote_get_gensuki_api_key();

    $presaleUrl = dspoinc_buy_quote_build_gensuki_get_url(
        $config,
        DSPOINC_BUY_QUOTE_PRESALE_DETAILS_PATH,
        ['ids' => $projectId]
    );

    $presaleResult = dspoinc_buy_quote_fetch_gensuki_json($presaleUrl, $apiKey);
    $presaleDetail = !empty($presaleResult['success'])
        ? dspoinc_buy_quote_extract_presale_detail($presaleResult['json'])
        : [];

    $tokenPriceNative = dspoinc_buy_quote_positive_decimal_or_null(
        $presaleDetail['tokenPriceNative'] ?? null
    );

    $tokenPriceUsd = dspoinc_buy_quote_positive_decimal_or_null(
        $presaleDetail['tokenPriceUsd']
            ?? $presaleDetail['tokenPrice']
            ?? $config['spoinc_price_usd']
            ?? null
    );

    $estimatedSolAmount = null;
    $estimatedUsdAmount = null;
    $quoteSource = 'unavailable';

    if ($tokenPriceNative !== null) {
        $estimatedSolAmount = dspoinc_buy_quote_multiply_decimal($spoincAmount, $tokenPriceNative, 9);
        $quoteSource = 'gensuki_presale_tokenPriceNative';
    } elseif ($tokenPriceUsd !== null) {
        $estimatedUsdAmount = dspoinc_buy_quote_multiply_decimal($spoincAmount, $tokenPriceUsd, 6);

        $solPriceUrl = dspoinc_buy_quote_build_gensuki_get_url(
            $config,
            DSPOINC_BUY_QUOTE_SOL_PRICE_PATH,
            ['usd' => $estimatedUsdAmount]
        );

        $solPriceResult = dspoinc_buy_quote_fetch_gensuki_json($solPriceUrl, $apiKey);
        $solWorth = is_array($solPriceResult['json'] ?? null)
            ? dspoinc_buy_quote_positive_decimal_or_null($solPriceResult['json']['solWorth'] ?? null)
            : null;

        if ($solWorth !== null) {
            $estimatedSolAmount = $solWorth;
            $quoteSource = 'gensuki_sol_price_from_usd';
        } else {
            $quoteSource = 'usd_estimate_only';
        }
    }

    $quoteTokenPayload = [
        'dspoinc_amount' => $dspoincAmount,
        'spoinc_amount' => $spoincAmount,
        'project_id' => $projectId,
        'issued_at' => gmdate('Y-m-d H:i:s'),
        'quote_source' => $quoteSource
    ];

    $quoteToken = 'quote_' . hash('sha256', json_encode($quoteTokenPayload));

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'request_type' => 'quote_dspoinc_buy_with_sol',
            'discord_id' => $userId,
            'wallet' => $wallet,
            'dspoinc_amount' => $dspoincAmount,
            'spoinc_amount' => $spoincAmount,
            'conversion_rate_dspoinc_per_spoinc' => SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC,
            'estimated_sol_amount' => $estimatedSolAmount,
            'estimated_usd_amount' => $estimatedUsdAmount,
            'payment_token' => 'SOL',
            'payment_token_address' => DSPOINC_BUY_QUOTE_NATIVE_SOL_ADDRESS,
            'project_id' => $projectId,
            'quote_token' => $quoteToken,
            'quote_source' => $quoteSource,
            'price' => [
                'token_price_native_sol' => $tokenPriceNative,
                'token_price_usd' => $tokenPriceUsd,
                'presale_http_code' => $presaleResult['http_code'] ?? null
            ],
            'safety' => [
                'quote_only' => true,
                'db_write_performed' => false,
                'gensuki_buy_called' => false,
                'gensuki_claim_called' => false,
                'gensuki_confirm_called' => false,
                'dspoinc_credit_performed' => false,
                'dspoinc_debit_performed' => false,
                'spoinc_settlement_performed' => false,
                'bridge_economy_mutation_performed' => false,
                'route_gate_checked' => true,
                'requires_future_wallet_signature' => true
            ]
        ]
    ]);
} catch (Throwable $error) {
    error_log('❌ dSPOINC buy quote error: ' . $error->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Failed to quote dSPOINC buy with SOL.',
        'details' => spoinc_bridge_is_localhost() ? $error->getMessage() : null,
        'safety' => [
            'quote_only' => true,
            'db_write_performed' => false,
            'gensuki_buy_called' => false,
            'gensuki_claim_called' => false,
            'gensuki_confirm_called' => false,
            'dspoinc_credit_performed' => false,
            'spoinc_settlement_performed' => false
        ]
    ], 500);
}
