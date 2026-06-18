<?php
/**
 * Solana Memo verification helper.
 *
 * Plain language for DEVS:
 * This helper verifies that a user sent a real Solana Memo transaction from
 * the expected wallet and that the Memo text matches the expected backend
 * challenge message.
 *
 * This helper is shared by:
 * - verify-nft-holder.php Ledger fallback verification
 * - Genesis Mouse Freezer freeze activation
 * - Genesis Mouse Freezer unfreeze activation
 *
 * This helper must not:
 * - write database rows
 * - grant roles
 * - freeze NFTs
 * - unfreeze NFTs
 * - pay DSPOINC
 * - touch tbl_dspoinc_stakes
 */

if (!defined('NARRRFS_SOLANA_MEMO_PROGRAM_ID')) {
    define('NARRRFS_SOLANA_MEMO_PROGRAM_ID', 'MemoSq4gqABAXKb96qnH8TysNcWxMyWCqXgDLGmfcHr');
}

/**
 * Validate a Solana wallet address shape.
 *
 * Plain language for DEVS:
 * This is a format guard only. It does not prove ownership.
 */
function narrrfs_is_valid_solana_address(string $value): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value);
}

/**
 * Validate a Solana transaction signature shape.
 *
 * Plain language for DEVS:
 * This only checks that the string looks like a base58 Solana transaction id.
 * The real proof comes from fetching and verifying the transaction by RPC.
 */
function narrrfs_is_valid_solana_transaction_signature(string $value): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,100}$/', $value);
}

/**
 * Return Solana RPC URLs with environment-key support and safe fallbacks.
 *
 * Plain language for DEVS:
 * We try paid/keyed RPCs first when available, then public fallbacks.
 * Public fallbacks can rate-limit, so production should use HELIUS_API_KEY
 * or ALCHEMY_API_KEY when possible.
 */
function narrrfs_get_solana_rpc_urls(): array
{
    $rpcUrls = [];

    $heliusApiKey = getenv('HELIUS_API_KEY');
    if ($heliusApiKey) {
        $rpcUrls[] = 'https://mainnet.helius-rpc.com/?api-key=' . $heliusApiKey;
    }

    $alchemyApiKey = getenv('ALCHEMY_API_KEY');
    if ($alchemyApiKey) {
        $rpcUrls[] = 'https://solana-mainnet.g.alchemy.com/v2/' . $alchemyApiKey;
    }

    $rpcUrls[] = 'https://rpc.ankr.com/solana';
    $rpcUrls[] = 'https://api.mainnet-beta.solana.com';

    return array_values(array_unique(array_filter($rpcUrls)));
}

/**
 * Perform a JSON HTTP request.
 *
 * Plain language for DEVS:
 * This is intentionally small and local to the Solana helper so API endpoints
 * do not need to duplicate curl handling for RPC calls.
 */
function narrrfs_perform_json_http_request(
    string $url,
    ?array $payload = null,
    array $headers = [],
    int $timeoutSeconds = 20
): array {
    $ch = curl_init($url);

    $finalHeaders = $headers;
    $finalHeaders[] = 'User-Agent: Narrrfs-World-Solana-Memo-Verification/1.0';

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $finalHeaders);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('CURL error: ' . $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException("HTTP {$httpCode}: " . substr((string)$responseBody, 0, 500));
    }

    $decoded = json_decode((string)$responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Invalid JSON response: ' . json_last_error_msg());
    }

    return [
        'status' => $httpCode,
        'body' => $responseBody,
        'json' => $decoded
    ];
}

/**
 * Fetch a Solana transaction from RPC with fallback across multiple endpoints.
 *
 * Plain language for DEVS:
 * Memo transactions may need a few seconds before RPC nodes can see them.
 * We retry across the configured RPC list before failing.
 */
function narrrfs_fetch_solana_transaction(string $txSignature, int $maxAttempts = 12): ?array
{
    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getTransaction',
        'params' => [
            $txSignature,
            [
                'encoding' => 'jsonParsed',
                'maxSupportedTransactionVersion' => 0
            ]
        ]
    ];

    $rpcUrls = narrrfs_get_solana_rpc_urls();
    $sleepMicroseconds = 1500000;
    $lastError = null;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        foreach ($rpcUrls as $rpcUrl) {
            try {
                error_log("🔗 [SOLANA MEMO HELPER] Attempt {$attempt}/{$maxAttempts}: getTransaction via {$rpcUrl}");

                $response = narrrfs_perform_json_http_request(
                    $rpcUrl,
                    $payload,
                    ['Content-Type: application/json'],
                    20
                );

                if (!array_key_exists('result', $response['json'])) {
                    error_log("⚠️ [SOLANA MEMO HELPER] No result field from {$rpcUrl}");
                    continue;
                }

                if (!$response['json']['result']) {
                    error_log("⚠️ [SOLANA MEMO HELPER] Empty transaction result from {$rpcUrl}");
                    continue;
                }

                error_log("✅ [SOLANA MEMO HELPER] Transaction fetched from {$rpcUrl}");
                return $response['json']['result'];
            } catch (Throwable $error) {
                $lastError = $error;
                error_log("❌ [SOLANA MEMO HELPER] RPC failed via {$rpcUrl}: " . $error->getMessage());
            }
        }

        if ($attempt < $maxAttempts) {
            usleep($sleepMicroseconds);
        }
    }

    if ($lastError) {
        error_log('❌ [SOLANA MEMO HELPER] Last RPC error: ' . $lastError->getMessage());
    }

    return null;
}

/**
 * Normalize one Solana account key entry into pubkey + signer information.
 */
function narrrfs_normalize_solana_account_key($keyInfo): array
{
    if (is_array($keyInfo)) {
        return [
            'pubkey' => (string)($keyInfo['pubkey'] ?? ''),
            'signer' => array_key_exists('signer', $keyInfo) ? (bool)$keyInfo['signer'] : false
        ];
    }

    return [
        'pubkey' => (string)$keyInfo,
        'signer' => false
    ];
}

/**
 * Return true when the expected wallet signed the transaction.
 *
 * Plain language for DEVS:
 * Parsed RPC responses normally expose signer=true on accountKeys.
 * Some RPC fallbacks may return simple strings, so this helper is defensive.
 */
function narrrfs_solana_transaction_has_signer(array $transactionResult, string $walletAddress): bool
{
    $accountKeys = $transactionResult['transaction']['message']['accountKeys'] ?? null;

    if (!is_array($accountKeys)) {
        error_log('❌ [SOLANA MEMO HELPER] Missing account keys');
        return false;
    }

    foreach ($accountKeys as $keyInfo) {
        $normalized = narrrfs_normalize_solana_account_key($keyInfo);

        if ($normalized['pubkey'] !== $walletAddress) {
            continue;
        }

        if ($normalized['signer']) {
            return true;
        }

        error_log('❌ [SOLANA MEMO HELPER] Wallet found but not marked signer');
        return false;
    }

    error_log('❌ [SOLANA MEMO HELPER] Expected wallet signer not found');
    return false;
}

/**
 * Convert parsed Memo instruction data into readable text when possible.
 */
function narrrfs_decode_solana_memo_instruction_data(string $data): string
{
    if ($data === '') {
        return '';
    }

    if (function_exists('sodium_base642bin')) {
        try {
            $decoded = sodium_base642bin($data, SODIUM_BASE64_VARIANT_ORIGINAL);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        } catch (Throwable $error) {
            // Continue to base64_decode fallback.
        }
    }

    $base64Decoded = base64_decode($data, true);
    if (is_string($base64Decoded) && $base64Decoded !== '') {
        return $base64Decoded;
    }

    return $data;
}

/**
 * Read the Memo text values from top-level and inner Solana instructions.
 *
 * Plain language for DEVS:
 * Some RPC responses expose the memo in parsed.info.memo.
 * Some expose it as parsed string.
 * Some expose raw base64 data. We support all three shapes.
 */
function narrrfs_extract_solana_memo_texts(array $transactionResult): array
{
    $memoTexts = [];

    $instructionGroups = [
        $transactionResult['transaction']['message']['instructions'] ?? []
    ];

    $innerInstructions = $transactionResult['meta']['innerInstructions'] ?? [];
    if (is_array($innerInstructions)) {
        foreach ($innerInstructions as $innerGroup) {
            if (isset($innerGroup['instructions']) && is_array($innerGroup['instructions'])) {
                $instructionGroups[] = $innerGroup['instructions'];
            }
        }
    }

    foreach ($instructionGroups as $instructions) {
        if (!is_array($instructions)) {
            continue;
        }

        foreach ($instructions as $instruction) {
            if (!is_array($instruction)) {
                continue;
            }

            $programId = (string)($instruction['programId'] ?? '');
            $program = (string)($instruction['program'] ?? '');

            if ($programId !== NARRRFS_SOLANA_MEMO_PROGRAM_ID && strtolower($program) !== 'spl-memo') {
                continue;
            }

            $parsed = $instruction['parsed'] ?? null;

            if (is_string($parsed) && $parsed !== '') {
                $memoTexts[] = $parsed;
                continue;
            }

            if (is_array($parsed)) {
                $memoFromInfo = $parsed['info']['memo'] ?? ($parsed['memo'] ?? null);
                if (is_string($memoFromInfo) && $memoFromInfo !== '') {
                    $memoTexts[] = $memoFromInfo;
                    continue;
                }
            }

            $data = (string)($instruction['data'] ?? '');
            if ($data !== '') {
                $memoTexts[] = narrrfs_decode_solana_memo_instruction_data($data);
            }
        }
    }

    return array_values(array_unique(array_filter($memoTexts, static function ($memoText) {
        return trim((string)$memoText) !== '';
    })));
}

/**
 * Verify a Solana Memo transaction against wallet + exact message.
 *
 * Plain language for DEVS:
 * This returns detailed status so caller APIs can show precise errors without
 * guessing. It only verifies proof; it does not mutate any database state.
 */
function narrrfs_verify_solana_memo_transaction(
    string $walletAddress,
    string $expectedMessage,
    string $txSignature
): array {
    $walletAddress = trim($walletAddress);
    $expectedMessage = trim($expectedMessage);
    $txSignature = trim($txSignature);

    if (!narrrfs_is_valid_solana_address($walletAddress)) {
        return [
            'success' => false,
            'error' => 'Invalid Solana wallet address format.'
        ];
    }

    if ($expectedMessage === '') {
        return [
            'success' => false,
            'error' => 'Expected Memo message is required.'
        ];
    }

    if (!narrrfs_is_valid_solana_transaction_signature($txSignature)) {
        return [
            'success' => false,
            'error' => 'Invalid Solana transaction signature format.'
        ];
    }

    $transactionResult = narrrfs_fetch_solana_transaction($txSignature);

    if (!$transactionResult) {
        return [
            'success' => false,
            'error' => 'Memo transaction was not found on Solana RPC yet.'
        ];
    }

    if (($transactionResult['meta']['err'] ?? null) !== null) {
        return [
            'success' => false,
            'error' => 'Memo transaction failed on-chain.'
        ];
    }

    if (!narrrfs_solana_transaction_has_signer($transactionResult, $walletAddress)) {
        return [
            'success' => false,
            'error' => 'Memo transaction was not signed by the expected wallet.'
        ];
    }

    $memoTexts = narrrfs_extract_solana_memo_texts($transactionResult);

    if (empty($memoTexts)) {
        return [
            'success' => false,
            'error' => 'Memo transaction does not contain a readable Memo instruction.'
        ];
    }

    foreach ($memoTexts as $memoText) {
        if (trim((string)$memoText) === $expectedMessage) {
            return [
                'success' => true,
                'tx_signature' => $txSignature,
                'wallet' => $walletAddress,
                'memo' => $memoText,
                'memo_program_id' => NARRRFS_SOLANA_MEMO_PROGRAM_ID
            ];
        }
    }

    return [
        'success' => false,
        'error' => 'Memo transaction message does not match the expected challenge message.',
        'memo_texts_found' => $memoTexts
    ];
}

/**
 * Compatibility wrapper for old code that expects a boolean.
 *
 * Plain language for DEVS:
 * verify-nft-holder.php currently expects true/false. The Genesis Freezer APIs
 * can use narrrfs_verify_solana_memo_transaction() directly for richer errors.
 */
function verifySolanaMemoTransaction(string $walletAddress, string $message, string $txSignature): bool
{
    $result = narrrfs_verify_solana_memo_transaction($walletAddress, $message, $txSignature);

    if (empty($result['success'])) {
        error_log('[SOLANA MEMO HELPER] Verification failed: ' . ($result['error'] ?? 'Unknown error'));
        return false;
    }

    return true;
}