<?php
/**
 * NFT Holder Verification API
 *
 * VERIFICATION FLOW:
 * 1. Frontend calls this API with wallet address and collection address
 * 2. API uses get-nfts.php to verify NFT ownership (same logic as frontend display)
 * 3. If NFTs found, grants Discord role based on collection address mapping:
 *    - 'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML' → '🏆 Holder' (role_id: 1402668301414563971)
 *    - 'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg' → '🎴 VIP Holder' (role_id: 1332016526848692345)
 *
 * CRITICAL: Collection address is used to determine which role to grant
 * - VIP NFTs (CUJH8MV...) → VIP Holder role ONLY
 * - Genesis NFTs (AtJCkW4...) → Holder role ONLY
 * - No cross-granting: VIP collection grants VIP role, Genesis collection grants Holder role
 */

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

register_shutdown_function(function () {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json');
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $error['message'],
        'file' => basename($error['file']),
        'line' => $error['line']
    ]);

    if (ob_get_length() !== false) {
        ob_end_flush();
    }

    exit;
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (ob_get_length()) {
        ob_end_clean();
    }
    exit(0);
}

const VERIFICATION_MODE_MESSAGE = 'message';
const VERIFICATION_MODE_MEMO = 'memo_transaction';

const GENESIS_COLLECTION_ADDRESS = 'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML';
const VIP_COLLECTION_ADDRESS = 'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg';

const GENESIS_ROLE_ID = '1402668301414563971';
const VIP_ROLE_ID = '1332016526848692345';

const MEMO_PROGRAM_ID = 'MemoSq4gqABAXKb96qnH8TysNcWxMyWCqXgDLGmfcHr';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new Exception('Invalid JSON input');
    }

    $userId = trim((string)($input['user_id'] ?? ''));
    $walletAddress = trim((string)($input['wallet_address'] ?? ''));
    $collection = trim((string)($input['collection'] ?? ''));
    $signature = trim((string)($input['signature'] ?? ''));
    $message = trim((string)($input['message'] ?? ''));
    $verificationMode = trim((string)($input['verification_mode'] ?? VERIFICATION_MODE_MESSAGE));
    $botToken = trim((string)($input['bot_token'] ?? ''));

    $isBotRequest = $botToken !== '';
    $validBotToken = getenv('DISCORD_SECRET') ?: 'admin_quest_system';

    if ($isBotRequest && $botToken !== $validBotToken) {
        throw new Exception('Invalid bot token for Discord bot verification');
    }

    if ($userId === '') {
        throw new Exception('User ID is required');
    }

    if ($walletAddress === '') {
        throw new Exception('Wallet address is required');
    }

    if (!isValidSolanaAddress($walletAddress)) {
        throw new Exception('Invalid Solana wallet address format');
    }

    if (!in_array($verificationMode, [VERIFICATION_MODE_MESSAGE, VERIFICATION_MODE_MEMO], true)) {
        throw new Exception('Invalid verification mode');
    }

    if (!$isBotRequest) {
        validateVerificationProof($walletAddress, $message, $signature, $verificationMode);
    }

    $db = createDatabaseConnection();
    $user = findUserForVerification($db, $userId);

    $collectionsConfig = getCollectionsConfig();
    $collectionsToVerify = resolveCollectionsToVerify($collectionsConfig, $collection);
    $verifiedCollections = [];

    error_log("🔍 [VERIFY-NFT-HOLDER] Starting verification for user: {$userId} (" . ($user['username'] ?? 'N/A') . ")");
    error_log("🔍 [VERIFY-NFT-HOLDER] Wallet: {$walletAddress}");
    error_log("🔍 [VERIFY-NFT-HOLDER] Verification mode: {$verificationMode}");
    error_log("🔍 [VERIFY-NFT-HOLDER] Collections to verify: " . count($collectionsToVerify));

    foreach ($collectionsToVerify as $collectionAddress => $info) {
        error_log("\n🔍 [VERIFY-NFT-HOLDER] Processing collection: {$info['name']}");
        error_log("   Collection Address: {$collectionAddress}");
        error_log("   Expected Role: {$info['role_name']} (ID: {$info['role_id']})");

        $collectionCount = fetchCollectionNFTCountViaAPI($walletAddress, $collectionAddress);
        error_log("   ✅ NFT Count Result: {$collectionCount} NFT(s) found");

        $roleGranted = false;

        if ($collectionCount > 0) {
            error_log("   🎯 NFTs found! Attempting to grant role: {$info['role_name']} (ID: {$info['role_id']})");

            $roleGranted = grantDiscordRole(
                $userId,
                (string)($user['username'] ?? ''),
                $info['role_id'],
                $info['role_name']
            );

            if ($roleGranted) {
                error_log("   ✅ Role granted successfully: {$info['role_name']}");
            } else {
                error_log("   ❌ Role grant failed: {$info['role_name']}");
            }

            logHolderVerification(
                $db,
                $userId,
                (string)($user['username'] ?? ''),
                $walletAddress,
                $info['name'],
                $collectionCount,
                $roleGranted
            );

            if ($roleGranted) {
                logRoleGrant(
                    $db,
                    $userId,
                    (string)($user['username'] ?? ''),
                    $info['role_id'],
                    $info['role_name']
                );
            }
        } else {
            error_log("   ❌ No NFTs found for collection: {$info['name']}");

            logHolderVerification(
                $db,
                $userId,
                (string)($user['username'] ?? ''),
                $walletAddress,
                $info['name'],
                0,
                false
            );
        }

        $verifiedCollections[] = [
            'collection_address' => $collectionAddress,
            'collection' => $info['name'],
            'role' => $info['role_name'],
            'role_id' => $info['role_id'],
            'count' => $collectionCount,
            'granted' => $roleGranted
        ];

        error_log('   📊 Collection verification result: ' . json_encode([
            'collection' => $info['name'],
            'count' => $collectionCount,
            'granted' => $roleGranted ? 'YES' : 'NO'
        ]));
    }

    error_log("\n✅ [VERIFY-NFT-HOLDER] Verification complete. Total collections verified: " . count($verifiedCollections));

    respondJson([
        'success' => true,
        'wallet' => $walletAddress,
        'verified_collections' => $verifiedCollections,
        'message' => 'NFT verification completed.'
    ]);
} catch (Exception $e) {
    error_log('NFT Holder Verification Error: ' . $e->getMessage());

    respondJson([
        'success' => false,
        'error' => $e->getMessage()
    ], 400);
} catch (Error $e) {
    error_log('NFT Holder Verification Fatal Error: ' . $e->getMessage());

    respondJson([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}

/**
 * Send a JSON response and stop execution.
 */
function respondJson(array $payload, int $statusCode = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($statusCode);
    echo json_encode($payload);

    if (ob_get_length() !== false) {
        ob_end_flush();
    }

    exit;
}

/**
 * Return the SQLite path for local or production environments.
 */
function getDatabasePath(): string
{
    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
    $isProduction = strpos($host, 'narrrfs.world') !== false;

    return $isProduction
        ? '/var/www/html/db/narrrf_world.sqlite'
        : __DIR__ . '/../../db/narrrf_world.sqlite';
}

/**
 * Create the PDO connection used by the verification flow.
 */
function createDatabaseConnection(): PDO
{
    $db = new PDO('sqlite:' . getDatabasePath());
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
}

/**
 * Return the supported NFT collection and Discord role mapping.
 */
function getCollectionsConfig(): array
{
    return [
        GENESIS_COLLECTION_ADDRESS => [
            'name' => 'Narrrfs World: Genesis Genetic',
            'role_name' => '🏆 Holder',
            'role_id' => GENESIS_ROLE_ID
        ],
        VIP_COLLECTION_ADDRESS => [
            'name' => 'Narrrf Genesis VIP Drop',
            'role_name' => '🎴 VIP Holder',
            'role_id' => VIP_ROLE_ID
        ]
    ];
}

/**
 * Validate a Solana wallet address shape.
 */
function isValidSolanaAddress(string $value): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value);
}

/**
 * Validate the supplied verification proof before any role or NFT logic runs.
 */
function validateVerificationProof(
    string $walletAddress,
    string $message,
    string $signature,
    string $verificationMode
): void {
    if ($signature === '') {
        throw new Exception('Cryptographic signature is required for security');
    }

    if ($message === '') {
        throw new Exception('Signed message is required');
    }

    error_log("🔐 [VERIFY-NFT-HOLDER] Verification mode: {$verificationMode}");
    error_log("🔐 [VERIFY-NFT-HOLDER] Wallet: {$walletAddress}");
    error_log("🔐 [VERIFY-NFT-HOLDER] Proof: {$signature}");
    error_log("🔐 [VERIFY-NFT-HOLDER] Message: {$message}");

    if ($verificationMode === VERIFICATION_MODE_MEMO) {
        if (!verifySolanaMemoTransaction($walletAddress, $message, $signature)) {
            throw new Exception('Invalid Ledger verification transaction.');
        }

        return;
    }

    if (!verifySolanaSignature($walletAddress, $message, $signature)) {
        throw new Exception('Invalid signature. Please sign the verification message with your wallet.');
    }
}

/**
 * Find the user in the same Season 9-compatible way the existing system expects.
 */
function findUserForVerification(PDO $db, string $userId): array
{
    $userStmt = $db->prepare("
        SELECT us.user_id, SUM(us.score) as total_score, u.username, u.discord_id
        FROM tbl_user_scores us
        LEFT JOIN tbl_users u ON us.user_id = u.discord_id
        WHERE us.user_id = ?
        GROUP BY us.user_id
    ");
    $userStmt->execute([$userId]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return $user;
    }

    $userCheckStmt = $db->prepare("
        SELECT discord_id, username
        FROM tbl_users
        WHERE discord_id = ?
    ");
    $userCheckStmt->execute([$userId]);

    $userExists = $userCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userExists) {
        throw new Exception('User not found in database. User must be registered in the system.');
    }

    error_log("User {$userId} exists but hasn't played games yet - allowing verification");

    return [
        'user_id' => $userId,
        'total_score' => 0,
        'username' => $userExists['username'],
        'discord_id' => $userId
    ];
}

/**
 * Resolve which collections should be verified for this request.
 */
function resolveCollectionsToVerify(array $collectionsConfig, string $requestedCollection): array
{
    if ($requestedCollection === '') {
        return $collectionsConfig;
    }

    if (isset($collectionsConfig[$requestedCollection])) {
        return [$requestedCollection => $collectionsConfig[$requestedCollection]];
    }

    $collectionsByRole = [];
    $collectionsByName = [];

    foreach ($collectionsConfig as $address => $info) {
        $collectionsByRole[$info['role_name']] = $address;
        $collectionsByName[strtolower($info['name'])] = $address;
    }

    if (isset($collectionsByRole[$requestedCollection])) {
        $address = $collectionsByRole[$requestedCollection];
        return [$address => $collectionsConfig[$address]];
    }

    $lowerRequestedCollection = strtolower($requestedCollection);
    if (isset($collectionsByName[$lowerRequestedCollection])) {
        $address = $collectionsByName[$lowerRequestedCollection];
        return [$address => $collectionsConfig[$address]];
    }

    return $collectionsConfig;
}

/**
 * Fetch the number of NFTs a wallet holds for a specific collection using get-nfts.php.
 * This keeps frontend display logic and backend role logic aligned.
 */
function fetchCollectionNFTCountViaAPI(string $walletAddress, string $collectionAddress): int
{
    try {
        error_log('   🔍 [FETCH-NFT-COUNT] Calling get-nfts.php API');
        error_log("      Wallet: {$walletAddress}");
        error_log("      Collection: {$collectionAddress}");

        $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
        $isProduction = strpos($host, 'narrrfs.world') !== false;
        $apiBaseUrl = $isProduction ? 'https://narrrfs.world' : 'http://localhost';

        $apiUrl = $apiBaseUrl
            . '/api/wallet/get-nfts.php?wallet=' . urlencode($walletAddress)
            . '&collection=' . urlencode($collectionAddress);

        error_log("      API URL: {$apiUrl}");

        $response = performJsonHttpRequest($apiUrl, null, [
            'User-Agent: Narrrfs-World-NFT-Verification/1.0'
        ], 30);

        $data = $response['json'];

        error_log('      ✅ API Response: ' . json_encode([
            'success' => $data['success'] ?? false,
            'count' => $data['count'] ?? 0,
            'has_assets' => $data['has_assets'] ?? false,
            'method' => $data['method'] ?? 'unknown',
            'nfts_length' => isset($data['nfts']) && is_array($data['nfts']) ? count($data['nfts']) : 0
        ]));

        if (($data['success'] ?? false) && isset($data['count'])) {
            $count = (int)$data['count'];
            error_log("      ✅ Collection NFT count via API ({$collectionAddress}): {$count} NFTs found");
            return $count;
        }

        if (isset($data['nfts']) && is_array($data['nfts'])) {
            $count = count($data['nfts']);
            error_log("      ✅ Collection NFT count via API (fallback, {$collectionAddress}): {$count} NFTs found");
            return $count;
        }

        error_log("      ⚠️ Collection NFT count via API ({$collectionAddress}): No NFTs found or API error");
        return 0;
    } catch (Exception $e) {
        error_log("      ❌ Collection NFT count error via API ({$walletAddress}, {$collectionAddress}): " . $e->getMessage());
        return 0;
    }
}

/**
 * Legacy compatibility wrapper.
 */
function fetchCollectionNFTCount(string $walletAddress, string $collectionAddress): int
{
    return fetchCollectionNFTCountViaAPI($walletAddress, $collectionAddress);
}

/**
 * Grant a Discord role through the internal API.
 */
function grantDiscordRole(string $userId, string $username, string $roleId, string $roleName): bool
{
    try {
        error_log('   🎯 [GRANT-ROLE] Attempting to grant Discord role');
        error_log("      User ID: {$userId}");
        error_log("      Username: {$username}");
        error_log("      Role: {$roleName} (ID: {$roleId})");

        $discordApiUrl = 'https://narrrfs.world/api/discord/grant-role.php';
        $payload = [
            'action' => 'add_role',
            'user_id' => $userId,
            'role_id' => $roleId
        ];

        error_log("      API URL: {$discordApiUrl}");
        error_log('      Payload: ' . json_encode($payload));

        $response = performJsonHttpRequest(
            $discordApiUrl,
            $payload,
            [
                'Content-Type: application/json',
                'Authorization: Bearer admin_quest_system'
            ],
            10
        );

        $result = $response['json'];
        $success = (bool)($result['success'] ?? false);

        error_log('      API Response: ' . json_encode($result));

        if (!$success) {
            $error = $result['error'] ?? 'Unknown error';
            error_log("      ❌ Role grant failed: {$error}");
            throw new Exception('Discord role grant failed: ' . $error);
        }

        error_log('      ✅ Role granted successfully!');
        return true;
    } catch (Exception $e) {
        error_log("      ❌ Failed to grant Discord role {$roleName} ({$roleId}) to {$userId} ({$username}): " . $e->getMessage());
        return false;
    }
}

/**
 * Log holder verification attempts and outcomes.
 */
function logHolderVerification(
    PDO $db,
    string $userId,
    string $username,
    string $wallet,
    string $collectionName,
    int $count,
    bool $granted
): void {
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_holder_verifications
        (user_id, username, wallet, collection, nft_count, role_granted, verified_at)
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $userId,
        $username,
        $wallet,
        $collectionName,
        $count,
        $granted ? 1 : 0
    ]);
}

/**
 * Log successful role grants for auditing.
 */
function logRoleGrant(PDO $db, string $userId, string $username, string $roleId, string $roleName): void
{
    $stmt = $db->prepare("
        INSERT INTO tbl_role_grants
        (user_id, username, role_id, role_name, granted_at, reason, granted_by)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $username,
        $roleId,
        $roleName,
        'NFT Holder Verification - Helius verification',
        'system'
    ]);
}

/**
 * Perform an HTTP request and return both raw response and decoded JSON.
 */
function performJsonHttpRequest(
    string $url,
    ?array $payload = null,
    array $headers = [],
    int $timeoutSeconds = 20
): array {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('CURL error: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception("HTTP {$httpCode}: " . substr((string)$responseBody, 0, 500));
    }

    $decoded = json_decode((string)$responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response: ' . json_last_error_msg());
    }

    return [
        'status' => $httpCode,
        'body' => $responseBody,
        'json' => $decoded
    ];
}

/**
 * Return the RPC URLs used for server-side Solana verification calls.
 */
function getSolanaRpcUrls(): array
{
    return [
        'https://rpc.ankr.com/solana',
        'https://api.mainnet-beta.solana.com'
    ];
}

/**
 * Fetch a Solana transaction from RPC with fallback across multiple endpoints.
 */
function fetchSolanaTransaction(string $txSignature): ?array
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

    $lastError = null;

    foreach (getSolanaRpcUrls() as $rpcUrl) {
        try {
            error_log("🔗 [SOLANA RPC] Trying getTransaction via {$rpcUrl}");

            $response = performJsonHttpRequest(
                $rpcUrl,
                $payload,
                ['Content-Type: application/json'],
                20
            );

            if (!isset($response['json']['result'])) {
                error_log("⚠️ [SOLANA RPC] No result field from {$rpcUrl}");
                continue;
            }

            if (!$response['json']['result']) {
                error_log("⚠️ [SOLANA RPC] Empty transaction result from {$rpcUrl}");
                continue;
            }

            error_log("✅ [SOLANA RPC] Transaction fetched successfully from {$rpcUrl}");
            return $response['json']['result'];
        } catch (Exception $e) {
            $lastError = $e;
            error_log("❌ [SOLANA RPC] getTransaction failed via {$rpcUrl}: " . $e->getMessage());
        }
    }

    if ($lastError) {
        error_log('❌ [SOLANA RPC] All getTransaction RPC endpoints failed: ' . $lastError->getMessage());
    }

    return null;
}

/**
 * Verify Solana signature on the server side.
 *
 * TODO: Replace placeholder Solana message verification with real Ed25519 verification.
 * Current implementation only validates signature shape and is not cryptographically secure.
 */
function verifySolanaSignature(string $publicKey, string $message, string $signature): bool
{
    try {
        if (!isValidSolanaAddress($publicKey)) {
            error_log("Invalid public key format: {$publicKey}");
            return false;
        }

        if ($signature === '' || $message === '') {
            error_log('Missing signature or message');
            return false;
        }

        if (!preg_match('/^[0-9a-fA-F]+$/', $signature)) {
            error_log("Invalid signature format: {$signature}");
            return false;
        }

        $signatureLength = strlen($signature);
        if ($signatureLength !== 128) {
            error_log("Invalid signature length: {$signatureLength} (expected 128)");
            return false;
        }

        error_log("Signature verification attempt - PublicKey: {$publicKey}, Message: {$message}, Signature: {$signature}");

        // TODO: Implement proper Ed25519 verification using a proven library.
        return true;
    } catch (Exception $e) {
        error_log('Signature verification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verify a Solana memo transaction for Ledger fallback verification.
 * This checks that:
 * 1. the transaction exists on mainnet
 * 2. the claimed wallet signed it
 * 3. the transaction contains the Memo program
 * 4. the memo text exactly matches the expected verification message
 */
function verifySolanaMemoTransaction(string $walletAddress, string $message, string $txSignature): bool
{
    try {
        if (!isValidSolanaAddress($walletAddress)) {
            error_log("Invalid wallet address format for memo verification: {$walletAddress}");
            return false;
        }

        if ($message === '' || $txSignature === '') {
            error_log('Missing message or transaction signature for memo verification');
            return false;
        }

        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]+$/', $txSignature)) {
            error_log("Invalid Solana transaction signature format: {$txSignature}");
            return false;
        }

        $result = fetchSolanaTransaction($txSignature);
        if (!$result) {
            error_log('Memo verification RPC returned no transaction result');
            return false;
        }

        if (($result['meta']['err'] ?? null) !== null) {
            error_log('Memo verification transaction failed on-chain');
            return false;
        }

        $accountKeys = $result['transaction']['message']['accountKeys'] ?? null;
        if (!is_array($accountKeys)) {
            error_log('Memo verification missing account keys');
            return false;
        }

        $walletMatched = false;

        foreach ($accountKeys as $keyInfo) {
            if (is_array($keyInfo)) {
                $pubkey = (string)($keyInfo['pubkey'] ?? '');
                $signer = array_key_exists('signer', $keyInfo) ? !empty($keyInfo['signer']) : true;

                if ($pubkey === $walletAddress && $signer) {
                    $walletMatched = true;
                    break;
                }

                continue;
            }

            if (is_string($keyInfo) && $keyInfo === $walletAddress) {
                $walletMatched = true;
                break;
            }
        }

        if (!$walletMatched) {
            error_log("Memo verification wallet signer mismatch for {$walletAddress}");
            return false;
        }

        $instructions = $result['transaction']['message']['instructions'] ?? [];
        if (!is_array($instructions)) {
            $instructions = [];
        }

        $memoFound = false;

        foreach ($instructions as $instruction) {
            if (!is_array($instruction)) {
                continue;
            }

            $programId = (string)($instruction['programId'] ?? '');
            $program = strtolower((string)($instruction['program'] ?? ''));
            $parsed = $instruction['parsed'] ?? null;
            $rawData = $instruction['data'] ?? null;

            $isMemoInstruction =
                $programId === MEMO_PROGRAM_ID ||
                $program === 'spl-memo';

            if (!$isMemoInstruction) {
                continue;
            }

            if (is_string($parsed) && trim($parsed) === $message) {
                $memoFound = true;
                break;
            }

            if (is_array($parsed) && trim((string)($parsed['memo'] ?? '')) === $message) {
                $memoFound = true;
                break;
            }

            if (is_string($rawData)) {
                $decoded = base64_decode($rawData, true);
                if ($decoded !== false && trim($decoded) === $message) {
                    $memoFound = true;
                    break;
                }

                if (trim($rawData) === $message) {
                    $memoFound = true;
                    break;
                }
            }
        }

        if (!$memoFound) {
            error_log("Memo verification message mismatch. Expected: {$message}");
            error_log('Memo verification raw transaction: ' . json_encode($result));
            return false;
        }

        error_log("Memo verification succeeded for wallet: {$walletAddress}");
        return true;
    } catch (Exception $e) {
        error_log('Memo verification error: ' . $e->getMessage());
        return false;
    }
}
?>