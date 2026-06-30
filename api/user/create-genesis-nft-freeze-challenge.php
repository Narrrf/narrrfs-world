<?php
/**
 * Create Genesis NFT Freeze Challenge API.
 *
 * Plain language for DEVS:
 * This endpoint creates a short-lived pending freeze challenge for one verified
 * Genesis NFT. It does NOT freeze the NFT, does NOT create a stake row, and
 * does NOT pay DSPOINC.
 *
 * The next endpoint will verify the user's Solana Memo signature/transaction
 * and then create the active freezer row.
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/genesis-nft-staking-helpers.php';

const GENESIS_NFT_FREEZE_CHALLENGE_TTL_MINUTES = 15;

/**
 * Public activation switch for Genesis Mouse Freezer challenge creation.
 *
 * Plain language for DEVS:
 * When true, verified logged-in users may create freeze challenges in production.
 * This only opens challenge creation. It does not bypass Solana Memo proof,
 * verified ownership checks, slot limits, claim rules, or backend reward math.
 */
const GENESIS_FREEZER_PUBLIC_ACTIVATION_ENABLED = true;

/**
 * Controlled live tester allowlist for Genesis Mouse Freezer.
 *
 * Plain language for DEVS:
 * The wallet Memo production path is ready for controlled testing, but this is
 * not the public Season 13 NFT staking launch yet. Localhost remains open for
 * development. Production challenge creation is limited to Narrrf and justme
 * until the live test is proven safe.
 */
const GENESIS_FREEZER_CONTROLLED_LIVE_TEST_USER_IDS = [
    '328601656659017732', // Narrrf
    '1224428436928594015', // justme
];

/**
 * Return a JSON response and stop execution.
 */
function json_response($payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Determine whether the request is running on local development.
 */
function is_localhost_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON, POST, and GET.
 */
function get_freeze_challenge_request_data(): array
{
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);

    $requestData = [];

    if (is_array($jsonInput)) {
        $requestData = $jsonInput;
    }

    if (!empty($_POST)) {
        $requestData = array_merge($requestData, $_POST);
    }

    if (!empty($_GET)) {
        $requestData = array_merge($requestData, $_GET);
    }

    return $requestData;
}

/**
 * Resolve the authenticated user.
 *
 * Plain language for DEVS:
 * Production must use the Discord session.
 * Localhost may pass user_id for curl testing.
 */
function resolve_freeze_challenge_user_id(array $requestData): string
{
    $LOCAL_TEST_DISCORD_ID = '328601656659017732';

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($requestData['user_id'] ?? ''));

    if (is_localhost_request() && $requestUserId !== '') {
        return $requestUserId;
    }

    if ($requestUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    if (is_localhost_request()) {
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Block non-allowlisted production users during the controlled live test.
 *
 * Plain language for DEVS:
 * This gate only affects production. It does not block localhost curl/browser
 * testing. It prevents accidental public NFT staking access while we verify
 * real wallet Memo transactions with approved testers.
 */
function enforce_genesis_freezer_controlled_live_test_gate(string $userId): void
{
    if (GENESIS_FREEZER_PUBLIC_ACTIVATION_ENABLED) {
        return;
    }

    if (is_localhost_request()) {
        return;
    }

    if (in_array($userId, GENESIS_FREEZER_CONTROLLED_LIVE_TEST_USER_IDS, true)) {
        return;
    }

    json_response([
        'success' => false,
        'error' => 'Genesis Mouse Freezer is in controlled live testing. Public access opens with the Season 13 staking rollout.',
        'system_status' => [
            'phase' => 'controlled_live_test_only',
            'allowed_testers' => ['Narrrf', 'justme'],
            'public_activation' => false
        ]
    ], 403);
}

/**
 * Load the exact verified Genesis ownership row for the selected token.
 *
 * Plain language for DEVS:
 * A Discord user can have verified Genesis NFTs from multiple wallets.
 * The freeze challenge must therefore bind to the exact wallet + token pair.
 */
function load_verified_genesis_ownership(PDO $pdo, string $userId, string $tokenId, string $wallet): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            ownership_id,
            user_id,
            username,
            wallet,
            token_id,
            collection,
            nft_name,
            image_url,
            is_verified,
            verified_at,
            last_seen_at
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
          AND wallet = ?
          AND COALESCE(is_verified, 0) = 1
        ORDER BY
            COALESCE(verified_at, '') DESC,
            COALESCE(acquired_at, '') DESC,
            ownership_id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $tokenId, GENESIS_NFT_COLLECTION_KEY, $wallet]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Count active Genesis NFT freezer rows for one user.
 */
function count_active_genesis_nft_stakes(PDO $pdo, string $userId): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS active_count
        FROM tbl_genesis_nft_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);

    return genesis_nft_staking_int($stmt->fetchColumn());
}

/**
 * Check whether the selected token is already actively frozen.
 */
function has_active_genesis_nft_stake(PDO $pdo, string $tokenId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS active_count
        FROM tbl_genesis_nft_stakes
        WHERE token_id = ?
          AND collection = ?
          AND status = 'active'
    ");
    $stmt->execute([$tokenId, GENESIS_NFT_COLLECTION_KEY]);

    return genesis_nft_staking_int($stmt->fetchColumn()) > 0;
}

/**
 * Mark old pending freeze challenges for this token as expired.
 *
 * Plain language for DEVS:
 * This prevents multiple old pending freeze messages from staying open forever.
 */
function expire_old_freeze_challenges(PDO $pdo, string $userId, string $tokenId): void
{
    $stmt = $pdo->prepare("
        UPDATE tbl_genesis_nft_stake_challenges
        SET status = 'expired'
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
          AND action = 'freeze'
          AND status = 'pending'
    ");
    $stmt->execute([$userId, $tokenId, GENESIS_NFT_COLLECTION_KEY]);
}

/**
 * Build the Solana Memo message the user will sign/send later.
 */
function build_freeze_challenge_message(string $userId, string $wallet, string $tokenId, string $nonce, string $expiresAt): string
{
    return implode("\n", [
        'Narrrfs World Genesis Mouse Freezer',
        'Action: freeze',
        'User ID: ' . $userId,
        'Wallet: ' . $wallet,
        'Collection: genesis',
        'Token ID: ' . $tokenId,
        'Nonce: ' . $nonce,
        'Expires At: ' . $expiresAt,
        'Your NFT stays in your wallet. This memo only confirms freezer intent.'
    ]);
}

session_start();

$requestData = get_freeze_challenge_request_data();
$userId = resolve_freeze_challenge_user_id($requestData);

if ($userId === '') {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
    ], 401);
}

enforce_genesis_freezer_controlled_live_test_gate($userId);

$tokenId = trim((string)($requestData['token_id'] ?? ''));
$wallet = trim((string)($requestData['wallet'] ?? ''));

if ($tokenId === '') {
    json_response([
        'success' => false,
        'error' => 'token_id is required'
    ], 400);
}

if ($wallet === '') {
    json_response([
        'success' => false,
        'error' => 'wallet is required'
    ], 400);
}

try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ], 500);
}

try {
    $ownership = load_verified_genesis_ownership($pdo, $userId, $tokenId, $wallet);

    if (!$ownership) {
        json_response([
            'success' => false,
            'error' => 'Verified Genesis ownership not found for this user, wallet, and token.'
        ], 404);
    }

    if (has_active_genesis_nft_stake($pdo, $tokenId)) {
        json_response([
            'success' => false,
            'error' => 'This Genesis NFT is already actively frozen.'
        ], 409);
    }

    $genesisCount = genesis_nft_staking_count_verified_collection($pdo, $userId, GENESIS_NFT_COLLECTION_KEY);
    $vipCount = genesis_nft_staking_count_verified_collection($pdo, $userId, VIP_NFT_COLLECTION_KEY);
    $hasVip = $vipCount > 0;
    $tier = genesis_nft_staking_get_tier($genesisCount);
    $totalSlots = genesis_nft_staking_calculate_slots($tier, $hasVip);
    $usedSlots = count_active_genesis_nft_stakes($pdo, $userId);

    if ($totalSlots <= 0) {
        json_response([
            'success' => false,
            'error' => 'No Genesis Mouse Freezer slots available for this account.'
        ], 403);
    }

    if ($usedSlots >= $totalSlots) {
        json_response([
            'success' => false,
            'error' => 'All Genesis Mouse Freezer slots are already used.',
            'slots' => [
                'used' => $usedSlots,
                'total' => $totalSlots,
                'available' => 0
            ]
        ], 409);
    }

    $nonce = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', time() + (GENESIS_NFT_FREEZE_CHALLENGE_TTL_MINUTES * 60));
    $message = build_freeze_challenge_message($userId, $wallet, $tokenId, $nonce, $expiresAt);

    $pdo->beginTransaction();

    expire_old_freeze_challenges($pdo, $userId, $tokenId);

    $insertStmt = $pdo->prepare("
        INSERT INTO tbl_genesis_nft_stake_challenges (
            user_id,
            wallet,
            token_id,
            collection,
            action,
            nonce,
            message,
            status,
            expires_at,
            created_at
        )
        VALUES (
            :user_id,
            :wallet,
            :token_id,
            :collection,
            'freeze',
            :nonce,
            :message,
            'pending',
            :expires_at,
            CURRENT_TIMESTAMP
        )
    ");

    $insertStmt->execute([
        ':user_id' => $userId,
        ':wallet' => $wallet,
        ':token_id' => $tokenId,
        ':collection' => GENESIS_NFT_COLLECTION_KEY,
        ':nonce' => $nonce,
        ':message' => $message,
        ':expires_at' => $expiresAt
    ]);

    $challengeId = (int)$pdo->lastInsertId();

    $pdo->commit();

    json_response([
        'success' => true,
        'data' => [
            'challenge_id' => $challengeId,
            'action' => 'freeze',
            'nonce' => $nonce,
            'message' => $message,
            'expires_at' => $expiresAt,
            'ttl_minutes' => GENESIS_NFT_FREEZE_CHALLENGE_TTL_MINUTES,
            'ownership' => [
                'wallet' => $ownership['wallet'],
                'token_id' => $ownership['token_id'],
                'collection' => $ownership['collection'],
                'nft_name' => $ownership['nft_name'],
                'image_url' => $ownership['image_url'],
                'verified_at' => $ownership['verified_at']
            ],
            'tier' => $tier,
            'slots' => [
                'used' => $usedSlots,
                'total' => $totalSlots,
                'available_after_challenge' => max(0, $totalSlots - $usedSlots)
            ],
            'system_status' => [
                'phase' => 'phase_2_freeze_challenge_only',
                'creates_active_stake' => false,
                'writes_rewards' => false
            ]
        ]
    ]);
} catch (Exception $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Freeze challenge creation failed',
        'details' => $e->getMessage()
    ], 500);
}