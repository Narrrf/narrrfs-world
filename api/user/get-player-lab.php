<?php
// 🧬 Aggregate Player Lab API
// Read-only aggregate endpoint for Lab frontend + Discord bot integration.
//
// Stable-first rules:
// - Read-only only
// - Supports website session auth + trusted bot/internal auth
// - Backend remains authoritative
// - Preserves separation:
//   * Genesis progression = NFT-bound
//   * Genetic progression = Discord user-bound

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$localDiscordSecretPath = __DIR__ . '/../config/discord-secret.php';
if (file_exists($localDiscordSecretPath)) {
    include $localDiscordSecretPath;
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

foreach ($discordSecretCandidates as $discordSecretPath) {
    if (file_exists($discordSecretPath)) {
        include_once $discordSecretPath;
        break;
    }
}

function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}
/**
 * Return true when running in localhost-style development.
 */
function is_localhost_env(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data safely from JSON, GET, or POST.
 */
function get_request_data(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);

    if (is_array($decoded)) {
        return $decoded;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }

    return is_array($_POST) ? $_POST : [];
}

/**
 * Resolve Authorization header robustly across server setups.
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
 * Resolve internal API secret from common env locations.
 */
function get_internal_api_secret(): string {
    $candidates = [
        getenv('API_SECRET') ?: '',
        $_ENV['API_SECRET'] ?? '',
        $_SERVER['API_SECRET'] ?? '',

        getenv('INTERNAL_API_SECRET') ?: '',
        $_ENV['INTERNAL_API_SECRET'] ?? '',
        $_SERVER['INTERNAL_API_SECRET'] ?? '',

        getenv('DISCORD_SECRET') ?: '',
        $_ENV['DISCORD_SECRET'] ?? '',
        $_SERVER['DISCORD_SECRET'] ?? ''
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

/**
 * Resolve the active user.
 *
 * Rules:
 * - Trusted bot/internal requests may use Bearer secret + request user_id
 * - Website requests use Discord session auth
 * - Localhost may use request user_id directly
 */
function resolve_user_id(array $request): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($request['user_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    $expectedToken = trim((string)get_internal_api_secret());
    $authHeader = trim((string)get_authorization_header());

    $providedToken = '';
    $authSource = 'none';

    if (stripos($authHeader, 'Bearer ') === 0) {
        $providedToken = trim(substr($authHeader, 7));
        $authSource = 'authorization_bearer';
    }

    if ($providedToken === '') {
        $headerCandidates = [
            $_SERVER['HTTP_X_INTERNAL_AUTH'] ?? '',
            $_SERVER['HTTP_X_API_SECRET'] ?? '',
            $_SERVER['HTTP_X_DISCORD_SECRET'] ?? '',
            $_SERVER['HTTP_X_NARRRFS_INTERNAL_AUTH'] ?? '',
        ];

        foreach ($headerCandidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                $providedToken = $candidate;
                $authSource = 'custom_header';
                break;
            }
        }
    }

    if ($providedToken === '') {
        $bodyCandidates = [
            $request['internal_secret'] ?? '',
            $request['api_secret'] ?? '',
        ];

        foreach ($bodyCandidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                $providedToken = $candidate;
                $authSource = 'request_body';
                break;
            }
        }
    }

    $hasProvidedToken = $providedToken !== '';
    $looksLikeInternalRequest =
        $hasProvidedToken ||
        ($requestUserId !== '' && $sessionUserId === '');

    $isTrustedInternal =
        $expectedToken !== '' &&
        $providedToken !== '' &&
        hash_equals($expectedToken, $providedToken);

    if ($isTrustedInternal) {
        if ($requestUserId === '') {
            json_response([
                'success' => false,
                'error' => 'Missing user_id for internal request'
            ], 400);
        }

        error_log("🧬 Player Lab: Trusted internal request for user_id {$requestUserId} via {$authSource}");
        return $requestUserId;
    }

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🧬 Player Lab: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    if ($looksLikeInternalRequest) {
        if ($expectedToken === '') {
            json_response([
                'success' => false,
                'error' => 'Internal auth secret missing on server'
            ], 500);
        }

        json_response([
            'success' => false,
            'error' => 'Invalid internal authorization'
        ], 401);
    }

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    if ($isLocalhost) {
        error_log('🧬 Player Lab: Using local test user (Narrrf) for localhost');
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Return total DSPOINC from the ledger.
 */
function get_user_total_dspoinc(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return frozen DSPOINC from active stakes.
 */
function get_user_frozen_dspoinc(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Normalize runtime upgrade status.
 */
function normalize_upgrade_status(?string $status, ?string $endsAt): string {
    $normalized = strtolower(trim((string)$status));

    if ($normalized === 'ready') {
        return 'ready_to_claim';
    }

    if ($normalized === 'upgrading' && !empty($endsAt)) {
        $endTs = strtotime((string)$endsAt);
        if ($endTs !== false && $endTs <= time()) {
            return 'ready_to_claim';
        }
    }

    return $normalized !== '' ? $normalized : 'idle';
}

/**
 * Build holder summary from verification snapshots.
 */
function build_holder_summary(array $verificationRows): array {
    $hasGenesis = false;
    $hasVip = false;
    $latestVerifiedAt = null;
    $latestUsername = '';
    $wallets = [];
    $genesisCount = 0;
    $vipCount = 0;

    foreach ($verificationRows as $row) {
        $collection = strtolower(trim((string)($row['collection'] ?? '')));
        $nftCount = (int)($row['nft_count'] ?? 0);
        $wallet = trim((string)($row['wallet'] ?? ''));
        $verifiedAt = trim((string)($row['verified_at'] ?? ''));
        $username = trim((string)($row['username'] ?? ''));

        if ($wallet !== '') {
            $wallets[$wallet] = true;
        }

        if ($latestVerifiedAt === null || $verifiedAt > $latestVerifiedAt) {
            $latestVerifiedAt = $verifiedAt;
            $latestUsername = $username;
        }

        if ($nftCount < 1) {
            continue;
        }

        if (strpos($collection, 'genesis') !== false) {
            $hasGenesis = true;
            $genesisCount = max($genesisCount, $nftCount);
        }

        if (strpos($collection, 'vip') !== false) {
            $hasVip = true;
            $vipCount = max($vipCount, $nftCount);
        }
    }

    return [
        'has_genesis' => $hasGenesis,
        'has_vip' => $hasVip,
        'can_use_lab' => ($hasGenesis || $hasVip),
        'genesis_count' => $genesisCount,
        'vip_count' => $vipCount,
        'wallet_count' => count($wallets),
        'wallets' => array_keys($wallets),
        'latest_verified_at' => $latestVerifiedAt,
        'latest_username' => $latestUsername
    ];
}

/**
 * Pick one stable primary wallet for overview rendering.
 * We prefer the first verified wallet from holder verification history.
 */
function get_primary_wallet(array $holderSummary): string {
    $wallets = $holderSummary['wallets'] ?? [];
    if (!is_array($wallets) || !$wallets) {
        return '';
    }

    foreach ($wallets as $wallet) {
        $wallet = trim((string)$wallet);
        if ($wallet !== '') {
            return $wallet;
        }
    }

    return '';
}

/**
 * Build a frontend-safe overview contract for the Lab Overview tab.
 *
 * This does not replace the legacy payload keys yet.
 * It gives lab.html one stable place to read identity, economy,
 * Genesis progression, and Genetic progression without guessing field names.
 */
function build_lab_overview_payload(
    array $profile,
    array $holderSummary,
    array $genesisTraitUpgrades,
    array $geneticInventory,
    array $boosterInventory,
    array $stakingStats,
    array $summary
): array {
    $primaryWallet = get_primary_wallet($holderSummary);

    $genesisLabPower = 0;
    $genesisHighestLevel = 0;
    foreach ($genesisTraitUpgrades as $row) {
        $level = (int)($row['current_level'] ?? 0);
        $genesisLabPower += $level;
        if ($level > $genesisHighestLevel) {
            $genesisHighestLevel = $level;
        }
    }

    $geneticTotalPower = 0;
    $geneticHighestLevel = 0;
    foreach ($geneticInventory as $row) {
        $level = (int)($row['current_level'] ?? 0);
        $geneticTotalPower += $level;
        if ($level > $geneticHighestLevel) {
            $geneticHighestLevel = $level;
        }
    }

    $boosterTotal = 0;
    foreach ($boosterInventory as $row) {
        $boosterTotal += (int)($row['quantity'] ?? 0);
    }

    return [
        'contract_version' => 'lab_overview_v1',
        'identity' => [
            'discord_id' => (string)($profile['discord_id'] ?? ''),
            'discord_name' => (string)($profile['username'] ?? 'Unknown User'),
            'wallet_address' => $primaryWallet,
            'wallet_count' => (int)($holderSummary['wallet_count'] ?? 0),
            'wallets' => is_array($holderSummary['wallets'] ?? null) ? array_values($holderSummary['wallets']) : [],
            'verified_genesis_count' => (int)($summary['verified_genesis_count'] ?? 0),
            'verified_vip_count' => (int)($summary['verified_vip_count'] ?? 0),
            'latest_verified_at' => $holderSummary['latest_verified_at'] ?? null
        ],
        'economy' => [
            'total_dspoinc' => (int)($profile['total_dspoinc'] ?? 0),
            'available_dspoinc' => (int)($profile['available_dspoinc'] ?? 0),
            'frozen_dspoinc' => (int)($profile['frozen_dspoinc'] ?? 0),
            'active_stake_count' => (int)($stakingStats['active_stake_count'] ?? 0),
            'active_frozen_total' => (int)($stakingStats['active_frozen_total'] ?? 0),
            'booster_total_quantity' => $boosterTotal
        ],
        'genesis' => [
            'verified_nft_count' => (int)($summary['verified_genesis_count'] ?? 0),
            'lab_power_total' => $genesisLabPower,
            'highest_trait_level' => $genesisHighestLevel,
            'active_upgrade_count' => (int)($summary['genesis_active_upgrade_count'] ?? 0),
            'ready_claim_count' => (int)($summary['genesis_ready_claim_count'] ?? 0)
        ],
        'genetic' => [
            'item_count' => (int)($summary['genetic_item_count'] ?? 0),
            'power_total' => $geneticTotalPower,
            'highest_level' => $geneticHighestLevel,
            'active_upgrade_count' => (int)($summary['genetic_active_upgrade_count'] ?? 0),
            'ready_claim_count' => (int)($summary['genetic_ready_claim_count'] ?? 0),
            'listed_count' => (int)($summary['genetic_listed_count'] ?? 0)
        ]
    ];
}

try {
    $request = get_request_data();
    $userId = resolve_user_id($request);

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $pdo = getDatabaseConnection();

    /**
     * Load core user profile.
     */
    $profileStmt = $pdo->prepare("
        SELECT discord_id, username
        FROM tbl_users
        WHERE discord_id = ?
        LIMIT 1
    ");
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'discord_id' => $userId,
        'username' => 'Unknown User'
    ];

    $totalDspoinc = get_user_total_dspoinc($pdo, $userId);
    $frozenDspoinc = get_user_frozen_dspoinc($pdo, $userId);
    $availableDspoinc = max(0, $totalDspoinc - $frozenDspoinc);

    $profile['total_dspoinc'] = $totalDspoinc;
    $profile['frozen_dspoinc'] = $frozenDspoinc;
    $profile['available_dspoinc'] = $availableDspoinc;

    /**
     * Load verification summary rows.
     * This table is collection-summary data, not token-level NFT data.
     */
    $verificationStmt = $pdo->prepare("
        SELECT
            verification_id,
            user_id,
            username,
            wallet,
            collection,
            nft_count,
            role_granted,
            verified_at,
            created_at
        FROM tbl_holder_verifications
        WHERE user_id = ?
        ORDER BY verified_at DESC, verification_id DESC
    ");
    $verificationStmt->execute([$userId]);
    $verificationRows = $verificationStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $holderSummary = build_holder_summary($verificationRows);
    $primaryWallet = get_primary_wallet($holderSummary);

    // Stable aliases for frontend identity rendering.
    $profile['wallet_address'] = $primaryWallet;
    $profile['wallet'] = $primaryWallet;
    $profile['linked_wallets'] = is_array($holderSummary['wallets'] ?? null) ? array_values($holderSummary['wallets']) : [];

    /**
     * Load Genesis NFT-bound trait progression.
     */
    $genesisStmt = $pdo->prepare("
        SELECT
            upgrade_id,
            token_id,
            collection,
            trait_type,
            trait_value,
            current_level,
            upgrade_status,
            upgrade_started_at,
            upgrade_ends_at,
            last_completed_at,
            last_owner_user_id,
            ready_claim_notified_at,
            ready_claim_notification_count,
            active_booster_item_id,
            active_booster_used_at,
            created_at,
            updated_at
        FROM tbl_nft_trait_upgrades
        WHERE last_owner_user_id = ?
        ORDER BY
            CASE
                WHEN upgrade_status = 'upgrading' THEN 0
                WHEN upgrade_status = 'ready' THEN 1
                ELSE 2
            END,
            upgrade_ends_at ASC,
            updated_at DESC,
            upgrade_id DESC
    ");
    $genesisStmt->execute([$userId]);
    $genesisTraitUpgrades = $genesisStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($genesisTraitUpgrades as &$row) {
        $row['runtime_status'] = normalize_upgrade_status(
            $row['upgrade_status'] ?? 'idle',
            $row['upgrade_ends_at'] ?? null
        );
    }
    unset($row);

    /**
     * Load Discord-bound Genetic progression.
     */
    $geneticStmt = $pdo->prepare("
        SELECT
            genetic_item_id,
            user_id,
            catalog_id,
            trait_type,
            trait_value,
            current_level,
            upgrade_status,
            upgrade_started_at,
            upgrade_ends_at,
            last_completed_at,
            ready_claim_notified_at,
            ready_claim_notification_count,
            active_booster_item_id,
            active_booster_used_at,
            acquired_method,
            is_listed_for_sale,
            listed_listing_id,
            created_at,
            updated_at,
            last_transfer_at,
            last_owner_user_id
        FROM tbl_user_genetic_items
        WHERE user_id = ?
        ORDER BY
            CASE
                WHEN upgrade_status = 'upgrading' THEN 0
                WHEN upgrade_status = 'ready' THEN 1
                ELSE 2
            END,
            upgrade_ends_at ASC,
            updated_at DESC,
            genetic_item_id DESC
    ");
    $geneticStmt->execute([$userId]);
    $geneticInventory = $geneticStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($geneticInventory as &$item) {
        $item['runtime_status'] = normalize_upgrade_status(
            $item['upgrade_status'] ?? 'idle',
            $item['upgrade_ends_at'] ?? null
        );
    }
    unset($item);

    /**
     * Load booster inventory.
     * Locked booster IDs:
     * 33 = Green
     * 34 = Blue
     * 35 = Red
     */
    $boosterStmt = $pdo->prepare("
        SELECT
            ui.item_id,
            ui.quantity,
            si.item_name,
            si.description,
            si.price
        FROM tbl_user_inventory ui
        LEFT JOIN tbl_store_items si
            ON si.item_id = ui.item_id
        WHERE ui.user_id = ?
          AND ui.item_id IN (33, 34, 35)
        ORDER BY ui.item_id ASC
    ");
    $boosterStmt->execute([$userId]);
    $boosterInventory = $boosterStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    /**
     * Load staking stats.
     */
    $stakingStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS active_stake_count,
            COALESCE(SUM(amount), 0) AS active_frozen_total
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stakingStmt->execute([$userId]);
    $stakingStats = $stakingStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'active_stake_count' => 0,
        'active_frozen_total' => 0
    ];

    // Stable aliases for frontend economy rendering.
    $stakingStats['total_balance'] = (int)($profile['total_dspoinc'] ?? 0);
    $stakingStats['available_balance'] = (int)($profile['available_dspoinc'] ?? 0);
    $stakingStats['frozen_balance'] = (int)($profile['frozen_dspoinc'] ?? 0);

    /**
     * Build summary counts.
     */
    $genesisActive = 0;
    $genesisReady = 0;

    foreach ($genesisTraitUpgrades as $row) {
        $runtimeStatus = (string)($row['runtime_status'] ?? '');
        if ($runtimeStatus === 'upgrading') {
            $genesisActive++;
        }
        if ($runtimeStatus === 'ready_to_claim') {
            $genesisReady++;
        }
    }

    $geneticActive = 0;
    $geneticReady = 0;
    $geneticListed = 0;

    foreach ($geneticInventory as $row) {
        $runtimeStatus = (string)($row['runtime_status'] ?? '');
        if ($runtimeStatus === 'upgrading') {
            $geneticActive++;
        }
        if ($runtimeStatus === 'ready_to_claim') {
            $geneticReady++;
        }
        if ((int)($row['is_listed_for_sale'] ?? 0) === 1) {
            $geneticListed++;
        }
    }

    $boosterTotal = 0;
    foreach ($boosterInventory as $row) {
        $boosterTotal += (int)($row['quantity'] ?? 0);
    }

    $summary = [
        'verified_genesis_count' => (int)($holderSummary['genesis_count'] ?? 0),
        'verified_vip_count' => (int)($holderSummary['vip_count'] ?? 0),
        'genesis_active_upgrade_count' => $genesisActive,
        'genesis_ready_claim_count' => $genesisReady,
        'genetic_item_count' => count($geneticInventory),
        'genetic_active_upgrade_count' => $geneticActive,
        'genetic_ready_claim_count' => $geneticReady,
        'genetic_listed_count' => $geneticListed,
        'booster_total_quantity' => $boosterTotal
    ];

    $overview = build_lab_overview_payload(
        $profile,
        $holderSummary,
        $genesisTraitUpgrades,
        $geneticInventory,
        $boosterInventory,
        $stakingStats,
        $summary
    );

    json_response([
        'success' => true,
        'data' => [
            'profile' => $profile,
            'holder_summary' => $holderSummary,
            'verification_rows' => $verificationRows,
            'genesis_trait_upgrades' => $genesisTraitUpgrades,
            'genetic_inventory' => $geneticInventory,
            'booster_inventory' => $boosterInventory,
            'staking_stats' => $stakingStats,
            'summary' => $summary,
            'overview' => $overview
        ]
    ]);
} catch (Throwable $e) {
    error_log('🧬 Aggregate Player Lab API ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load player lab data',
        'details' => $e->getMessage()
    ], 500);
}