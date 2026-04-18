<?php
// 🧬 Start Bulk Favorite Trait Upgrades API
// Starts timed Genesis trait upgrades across all eligible verified Genesis NFTs
// for the active user's favorite trait types.
//
// Stability-first rules:
// - Genesis progression remains NFT-bound
// - Favorite trait preferences remain USER-bound
// - Backend is authoritative for eligibility, cost, and execution
// - SQLite-safe transaction flow
// - Localhost may use request user_id or Narrrf fallback
// - Production stays session-first with mismatch protection

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$databaseIncludeCandidates = [
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php'
];

foreach ($databaseIncludeCandidates as $databaseIncludePath) {
    if (file_exists($databaseIncludePath)) {
        require_once $databaseIncludePath;
        break;
    }
}

$discordSecretCandidates = [
    __DIR__ . '/../../config/discord-secret.php',
    __DIR__ . '/../config/discord-secret.php'
];

foreach ($discordSecretCandidates as $discordSecretPath) {
    if (file_exists($discordSecretPath)) {
        include_once $discordSecretPath;
        break;
    }
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback
const GENESIS_COLLECTION = 'genesis';
const BULK_UPGRADE_DEFAULT_DURATION_HOURS = 8;
const BULK_UPGRADE_BASE_COST = 100;

/**
 * Return JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running on localhost.
 */
function is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data from JSON, POST, or GET.
 */
function get_request_data(): array {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = [];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            $cached = $json;
        } elseif (!empty($_POST) && is_array($_POST)) {
            $cached = $_POST;
        }
    } else {
        $cached = $_GET ?? [];
    }

    return is_array($cached) ? $cached : [];
}

/**
 * Resolve active user using the same production/local model as the Lab APIs.
 */
function resolve_user_id(): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🧬 Bulk Favorite Trait Upgrade: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Bulk Favorite Trait Upgrade - user_id mismatch. Session: {$sessionUserId}, Request: {$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalhost) {
        error_log("🧬 Bulk Favorite Trait Upgrade: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $userId;
}

/**
 * Open database connection in local or production.
 */
function get_lab_database_connection(): PDO {
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }
    }

    if (function_exists('getDB')) {
        $pdo = getDB();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }
    }

    $dbPathCandidates = [];

    if (is_localhost_env()) {
        $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
        $dbPathCandidates[] = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
    }

    $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
    $dbPathCandidates[] = '/var/www/html/db/narrrf_world.sqlite';

    foreach ($dbPathCandidates as $dbPath) {
        if (!$dbPath || !file_exists($dbPath)) {
            continue;
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }

    throw new Exception('Database connection helper not available and SQLite file not found');
}

/**
 * Normalize trait type labels to canonical compare-safe values.
 */
function normalize_trait_type(string $rawTraitType): string {
    $value = trim((string)$rawTraitType);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s+/', ' ', $value);
    return mb_strtolower(trim((string)$value));
}

/**
 * Normalize collection labels.
 */
function normalize_collection_label(string $rawCollection): string {
    $collection = mb_strtolower(trim((string)$rawCollection));
    if ($collection === 'holder' || $collection === 'holders') {
        return GENESIS_COLLECTION;
    }
    return $collection;
}

/**
 * Return available DSPOINC = total score - active frozen stakes.
 * Reuses the same economy logic pattern as other backend-authoritative systems.
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    if ($userId === '') {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $frozen = (int)$stmt->fetchColumn();

    return max(0, $total - $frozen);
}

/**
 * Credit a negative DSPOINC ledger entry for upgrade cost.
 */
function record_upgrade_spend(PDO $pdo, string $userId, int $amount, string $reason, array $metadata = []): void {
    $amount = abs($amount);
    if ($amount <= 0) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (
            user_id,
            score,
            game,
            season,
            timestamp
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $userId,
        -$amount,
        'lab_trait_upgrade',
        'Genesis Lab'
    ]);

    // Optional audit trail skipped for now because tbl_score_adjustments
    // schema differs across environments. tbl_user_scores remains the
    // authoritative DSPOINC ledger for this bulk upgrade spend.
}

/**
 * Check if a SQLite table exists before querying it.
 */
function sqlite_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1");
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Load the user's favorite trait types.
 */
function fetch_favorite_trait_types(PDO $pdo, string $userId): array {
    $stmt = $pdo->prepare("
        SELECT trait_type
        FROM tbl_user_favorite_traits
        WHERE user_id = ?
          AND is_favorite = 1
        ORDER BY priority_order DESC, created_at ASC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $favorites = [];
    foreach ($rows as $row) {
        $normalized = normalize_trait_type((string)($row['trait_type'] ?? ''));
        if ($normalized !== '') {
            $favorites[$normalized] = true;
        }
    }

    return $favorites;
}

/**
 * Load token-level verified Genesis NFTs for the active user.
 * This uses the same underlying token ownership / verification lane the Lab depends on.
 */
function fetch_verified_genesis_nfts(PDO $pdo, string $userId): array {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            token_id,
            collection,
            COALESCE(nft_name, 'Genesis Mouse ' || token_id) AS nft_name
        FROM tbl_nft_traits
        WHERE user_id = ?
          AND LOWER(TRIM(COALESCE(collection, ''))) = 'genesis'
          AND TRIM(COALESCE(token_id, '')) <> ''
        ORDER BY token_id ASC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nfts = [];
    foreach ($rows as $row) {
        $tokenId = trim((string)($row['token_id'] ?? ''));
        if ($tokenId === '') {
            continue;
        }

        $nfts[$tokenId] = [
            'token_id' => $tokenId,
            'collection' => GENESIS_COLLECTION,
            'nft_name' => trim((string)($row['nft_name'] ?? ('Genesis Mouse ' . $tokenId)))
        ];
    }

    return array_values($nfts);
}

/**
 * Load all trait upgrade rows for the user's verified Genesis NFTs.
 */
function fetch_trait_upgrade_rows(PDO $pdo, array $tokenIds): array {
    if (empty($tokenIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($tokenIds), '?'));

        $stmt = $pdo->prepare("
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
            ready_claim_notified_at,
            ready_claim_notification_count,
            active_booster_item_id,
            active_booster_used_at,
            last_owner_user_id,
            created_at,
            updated_at
        FROM tbl_nft_trait_upgrades
        WHERE token_id IN ($placeholders)
          AND LOWER(TRIM(COALESCE(collection, ''))) = 'genesis'
    ");
    $stmt->execute($tokenIds);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Compute cost for the next trait level.
 * v1 simple model:
 * next level 2 => 100
 * next level 3 => 200
 * next level 4 => 300
 * etc.
 */
function get_next_upgrade_cost(int $currentLevel): int {
    $nextLevel = max(1, $currentLevel) + 1;
    return max(BULK_UPGRADE_BASE_COST, ($nextLevel - 1) * BULK_UPGRADE_BASE_COST);
}

/**
 * Compute duration for the next trait level.
 * v1 simple model: fixed 8h.
 */
function get_next_upgrade_duration_hours(int $currentLevel): int {
    return BULK_UPGRADE_DEFAULT_DURATION_HOURS;
}

/**
 * Insert a missing upgrade row if the NFT trait exists in tbl_nft_traits but has no upgrade row yet.
 * Returns the newly created row data.
 */
function create_missing_upgrade_row(
    PDO $pdo,
    string $userId,
    string $tokenId,
    string $traitType,
    string $traitValue
): array {
    $insertStmt = $pdo->prepare("
        INSERT INTO tbl_nft_trait_upgrades (
            token_id,
            collection,
            trait_type,
            trait_value,
            current_level,
            upgrade_status,
            last_owner_user_id,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, 1, 'idle', ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $insertStmt->execute([
        $tokenId,
        GENESIS_COLLECTION,
        $traitType,
        $traitValue,
        $userId
    ]);

    $upgradeId = (int)$pdo->lastInsertId();

    return [
        'upgrade_id' => $upgradeId,
        'token_id' => $tokenId,
        'collection' => GENESIS_COLLECTION,
        'trait_type' => $traitType,
        'trait_value' => $traitValue,
        'current_level' => 1,
        'upgrade_status' => 'idle',
        'last_owner_user_id' => $userId
    ];
}

/**
 * Load NFT traits for verified tokens so favorite trait types can be matched even when an upgrade row doesn't exist yet.
 */
function fetch_nft_traits(PDO $pdo, array $tokenIds): array {
    if (empty($tokenIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($tokenIds), '?'));
    $stmt = $pdo->prepare("
        SELECT
            token_id,
            trait_type,
            trait_value,
            collection
        FROM tbl_nft_traits
        WHERE token_id IN ($placeholders)
          AND LOWER(TRIM(COALESCE(collection, ''))) = 'genesis'
    ");
    $stmt->execute($tokenIds);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $request = get_request_data();
    $userId = resolve_user_id();

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not authenticated'
        ], 401);
    }

    $pdo = get_lab_database_connection();

    $favoriteTraitTypes = fetch_favorite_trait_types($pdo, $userId);
    if (empty($favoriteTraitTypes)) {
        json_response([
            'success' => false,
            'error' => 'No favorite traits configured'
        ], 400);
    }

    $verifiedNfts = fetch_verified_genesis_nfts($pdo, $userId);
    if (empty($verifiedNfts)) {
        json_response([
            'success' => false,
            'error' => 'No verified Genesis NFTs found'
        ], 404);
    }

    $tokenIds = array_map(static fn(array $row): string => (string)$row['token_id'], $verifiedNfts);
    $upgradeRows = fetch_trait_upgrade_rows($pdo, $tokenIds);
    $traitRows = fetch_nft_traits($pdo, $tokenIds);

    $upgradeIndex = [];
    foreach ($upgradeRows as $row) {
        $key = implode('::', [
            (string)$row['token_id'],
            normalize_trait_type((string)$row['trait_type']),
            trim((string)$row['trait_value'])
        ]);
        $upgradeIndex[$key] = $row;
    }

    $eligibleTargets = [];
    $tokenAlreadyQueued = [];
    foreach ($traitRows as $traitRow) {
        $tokenId = trim((string)($traitRow['token_id'] ?? ''));
        $traitTypeRaw = trim((string)($traitRow['trait_type'] ?? ''));
        $traitValue = trim((string)($traitRow['trait_value'] ?? ''));
        $traitType = normalize_trait_type($traitTypeRaw);

        if ($tokenId === '' || $traitType === '' || $traitValue === '') {
            continue;
        }

        if (empty($favoriteTraitTypes[$traitType])) {
            continue;
        }

        $key = implode('::', [$tokenId, $traitType, $traitValue]);
        $upgradeRow = $upgradeIndex[$key] ?? null;

        if (!$upgradeRow) {
            $upgradeRow = create_missing_upgrade_row($pdo, $userId, $tokenId, $traitTypeRaw, $traitValue);
            $upgradeIndex[$key] = $upgradeRow;
        }

        $status = strtolower(trim((string)($upgradeRow['upgrade_status'] ?? 'idle')));
        if ($status !== 'idle') {
            continue;
        }

        if (!empty($tokenAlreadyQueued[$tokenId])) {
            continue;
        }

        $currentLevel = max(1, (int)($upgradeRow['current_level'] ?? 1));

        $eligibleTargets[] = [
            'upgrade_id' => (int)$upgradeRow['upgrade_id'],
            'token_id' => $tokenId,
            'trait_type' => $traitTypeRaw,
            'trait_value' => $traitValue,
            'current_level' => $currentLevel,
            'next_level' => $currentLevel + 1,
            'cost_dspoinc' => get_next_upgrade_cost($currentLevel),
            'duration_hours' => get_next_upgrade_duration_hours($currentLevel)
        ];

        $tokenAlreadyQueued[$tokenId] = true;
    }

    if (empty($eligibleTargets)) {
        json_response([
            'success' => false,
            'error' => 'No eligible favorite trait upgrades found'
        ], 400);
    }

    usort($eligibleTargets, static function(array $a, array $b): int {
        if ($a['cost_dspoinc'] === $b['cost_dspoinc']) {
            if ($a['token_id'] === $b['token_id']) {
                return strcmp($a['trait_type'], $b['trait_type']);
            }
            return strcmp($a['token_id'], $b['token_id']);
        }
        return $a['cost_dspoinc'] <=> $b['cost_dspoinc'];
    });

    $availableDspoinc = get_user_available_dspoinc($pdo, $userId);

    $started = [];
    $skipped = [];
    $spentTotal = 0;

    $pdo->beginTransaction();

    foreach ($eligibleTargets as $target) {
    $cost = (int)$target['cost_dspoinc'];
    $tokenId = trim((string)($target['token_id'] ?? ''));

    if (($availableDspoinc - $spentTotal) < $cost) {
        $skipped[] = [
            'token_id' => $target['token_id'],
            'trait_type' => $target['trait_type'],
            'trait_value' => $target['trait_value'],
            'reason' => 'insufficient_dspoinc'
        ];
        continue;
    }

    if ($tokenId === '') {
        $skipped[] = [
            'token_id' => '',
            'trait_type' => $target['trait_type'],
            'trait_value' => $target['trait_value'],
            'reason' => 'missing_token_id'
        ];
        continue;
    }

    $activeUpgradeCheckStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND LOWER(TRIM(COALESCE(collection, ''))) = 'genesis'
          AND LOWER(TRIM(COALESCE(upgrade_status, 'idle'))) = 'upgrading'
    ");
    $activeUpgradeCheckStmt->execute([$tokenId]);
    $activeUpgradeCount = (int)$activeUpgradeCheckStmt->fetchColumn();

    if ($activeUpgradeCount > 0) {
        $skipped[] = [
            'token_id' => $target['token_id'],
            'trait_type' => $target['trait_type'],
            'trait_value' => $target['trait_value'],
            'reason' => 'token_already_upgrading'
        ];
        continue;
    }

    $endAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $endAt = $endAt->modify('+' . (int)$target['duration_hours'] . ' hours');

    $updateStmt = $pdo->prepare("
        UPDATE tbl_nft_trait_upgrades
        SET
            upgrade_status = 'upgrading',
            upgrade_started_at = CURRENT_TIMESTAMP,
            upgrade_ends_at = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE upgrade_id = ?
          AND LOWER(TRIM(COALESCE(upgrade_status, 'idle'))) = 'idle'
    ");

    $updateStmt->execute([
        $endAt->format('Y-m-d H:i:s'),
        (int)$target['upgrade_id']
    ]);

    if ($updateStmt->rowCount() < 1) {
        $skipped[] = [
            'token_id' => $target['token_id'],
            'trait_type' => $target['trait_type'],
            'trait_value' => $target['trait_value'],
            'reason' => 'race_or_not_idle'
        ];
        continue;
    }

    record_upgrade_spend(
        $pdo,
        $userId,
        $cost,
        'Bulk favorite Genesis trait upgrade',
        [
            'token_id' => $target['token_id'],
            'trait_type' => $target['trait_type'],
            'trait_value' => $target['trait_value'],
            'upgrade_id' => $target['upgrade_id']
        ]
    );

    $spentTotal += $cost;

    $started[] = [
        'upgrade_id' => (int)$target['upgrade_id'],
        'token_id' => $target['token_id'],
        'trait_type' => $target['trait_type'],
        'trait_value' => $target['trait_value'],
        'from_level' => (int)$target['current_level'],
        'to_level' => (int)$target['next_level'],
        'cost_dspoinc' => $cost,
        'upgrade_ends_at' => $endAt->format('Y-m-d H:i:s')
    ];
}

    $pdo->commit();

    json_response([
        'success' => true,
        'user_id' => $userId,
        'favorite_trait_count' => count($favoriteTraitTypes),
        'verified_genesis_count' => count($verifiedNfts),
        'eligible_count' => count($eligibleTargets),
        'started_count' => count($started),
        'skipped_count' => count($skipped),
        'spent_total_dspoinc' => $spentTotal,
        'remaining_available_dspoinc' => max(0, $availableDspoinc - $spentTotal),
        'started' => $started,
        'skipped' => $skipped
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Server error',
        'details' => $e->getMessage()
    ], 500);
}