<?php
// 🧬 Start NFT Ability Upgrade API
// Starts one timed Genesis Ability Matrix upgrade for one verified Genesis mouse.
//
// Stability-first rules:
// - Genesis ability upgrades are NFT-bound, never user-bound inventory
// - Genesis lane stays holder-protected
// - Upgrade belongs to exact token_id + collection + category + ability_key
// - Only one active ability upgrade per NFT
// - Unlock source = highest single Genesis trait level on that NFT
// - Ability progression stays separate from tbl_nft_trait_upgrades
// - No instant finish in v1
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

require_once __DIR__ . '/genesis-ability-helpers.php';

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback
const GENESIS_ABILITY_COLLECTION = 'genesis';

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
 * Resolve active user using the same production/local model as the live Lab APIs.
 */
function resolve_user_id(): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🧬 Start NFT Ability Upgrade: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Start NFT Ability Upgrade - user_id mismatch. Session: {$sessionUserId}, Request: {$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalhost) {
        error_log("🧬 Start NFT Ability Upgrade: Using local test user (Narrrf) for localhost");
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
 * Check if a SQLite table exists before querying it.
 */
function sqlite_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1");
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Return available columns for a SQLite table.
 */
function get_table_columns(PDO $pdo, string $tableName): array {
    if (!sqlite_table_exists($pdo, $tableName)) {
        return [];
    }

    $stmt = $pdo->query("PRAGMA table_info($tableName)");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $columns = [];

    foreach ($rows as $row) {
        if (!empty($row['name'])) {
            $columns[] = (string)$row['name'];
        }
    }

    return $columns;
}

/**
 * Normalize collection labels into the Genesis ability canon.
 */
function normalize_collection_label(string $rawCollection): string {
    $collection = strtolower(trim($rawCollection));

    if ($collection === '' || $collection === 'genesis' || $collection === 'holder' || $collection === 'holders') {
        return GENESIS_ABILITY_COLLECTION;
    }

    return $collection;
}

/**
 * Normalize trait type labels into the lab canon used by upgrade rows.
 */
function normalize_trait_type(string $rawTraitType): string {
    $traitType = trim($rawTraitType);
    if ($traitType === '') {
        return '';
    }

    $lower = strtolower($traitType);

    if (in_array($lower, ['sub trait', 'sub-trait', 'subtrait'], true)) {
        return 'Sub-Trait';
    }

    if (in_array($lower, ['special trait', 'special'], true)) {
        return 'Special';
    }

    $parts = preg_split('/\s+/', $traitType);
    $parts = array_map(function ($part) {
        return $part === '' ? '' : strtoupper(substr($part, 0, 1)) . strtolower(substr($part, 1));
    }, $parts ?: []);

    return str_replace('Sub-trait', 'Sub-Trait', implode(' ', $parts));
}

/**
 * Normalize an arbitrary traits array into deduplicated lab trait rows.
 */
function normalize_traits_array($rawTraits): array {
    if (!is_array($rawTraits)) {
        return [];
    }

    $seen = [];
    $normalized = [];

    foreach ($rawTraits as $trait) {
        if (!is_array($trait)) {
            continue;
        }

        $traitType = normalize_trait_type((string)($trait['trait_type'] ?? ''));
        $traitValue = trim((string)($trait['trait_value'] ?? $trait['value'] ?? ''));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $compound = strtolower($traitType . '::' . $traitValue);
        if (isset($seen[$compound])) {
            continue;
        }

        $seen[$compound] = true;
        $normalized[] = [
            'trait_type' => $traitType,
            'trait_value' => $traitValue
        ];
    }

    return $normalized;
}

/**
 * Load current verified Genesis NFTs for the user.
 * Order:
 * 1) tbl_nft_ownership
 * 2) JSON fallback scan tables
 * 3) older direct verification tables
 */
function fetch_verified_genesis_nfts(PDO $pdo, string $userId): array {
    $normalized = [];
    $seen = [];

        // 1) Preferred current-owner source
    if (sqlite_table_exists($pdo, 'tbl_nft_ownership')) {
        $ownershipColumns = get_table_columns($pdo, 'tbl_nft_ownership');

        $hasTokenId = in_array('token_id', $ownershipColumns, true);
        $hasCollection = in_array('collection', $ownershipColumns, true);

        $userColumn = null;
        foreach (['user_id', 'discord_id', 'owner_user_id'] as $candidateColumn) {
            if (in_array($candidateColumn, $ownershipColumns, true)) {
                $userColumn = $candidateColumn;
                break;
            }
        }

        if ($hasTokenId && $userColumn !== null) {
            $selectFields = [
                "{$userColumn} AS user_id",
                'token_id',
                $hasCollection ? 'collection' : "'genesis' AS collection"
            ];

            foreach (['nft_name', 'image_url', 'metadata_json', 'name', 'image', 'mint', 'wallet_address'] as $optionalColumn) {
                if (in_array($optionalColumn, $ownershipColumns, true)) {
                    $selectFields[] = $optionalColumn;
                }
            }

            $whereParts = ["{$userColumn} = :user_id"];
            if ($hasCollection) {
                $whereParts[] = "LOWER(collection) = 'genesis'";
            }
            if (in_array('is_verified', $ownershipColumns, true)) {
                $whereParts[] = 'COALESCE(is_verified, 0) = 1';
            }

            $sql = 'SELECT ' . implode(', ', $selectFields) .
                   ' FROM tbl_nft_ownership WHERE ' . implode(' AND ', $whereParts) .
                   ' ORDER BY token_id ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $tokenId = trim((string)($row['token_id'] ?? ''));
                $collection = normalize_collection_label((string)($row['collection'] ?? 'genesis'));

                if ($tokenId === '' || $collection !== GENESIS_ABILITY_COLLECTION) {
                    continue;
                }

                $compoundKey = strtolower($collection . '::' . $tokenId);
                if (isset($seen[$compoundKey])) {
                    continue;
                }

                $metadata = [];
                if (!empty($row['metadata_json'])) {
                    $decodedMetadata = json_decode((string)($row['metadata_json'] ?? ''), true);
                    if (is_array($decodedMetadata)) {
                        $metadata = $decodedMetadata;
                    }
                }

                $seen[$compoundKey] = true;
                $normalized[] = [
                    'user_id' => (string)($row['user_id'] ?? $userId),
                    'token_id' => $tokenId,
                    'collection' => $collection,
                    'nft_name' => (string)($row['nft_name'] ?? $row['name'] ?? ($metadata['name'] ?? '')),
                    'image_url' => (string)($row['image_url'] ?? $row['image'] ?? ($metadata['image'] ?? '')),
                    'mint' => (string)($row['mint'] ?? $tokenId),
                    'wallet_address' => (string)($row['wallet_address'] ?? ''),
                    'traits' => fetch_nft_traits_for_token($pdo, $tokenId)
                ];
            }

            if (!empty($normalized)) {
                return $normalized;
            }
        }
    }

    // 2) JSON fallback scan tables
    $jsonTables = [
        'tbl_verified_nft_scans',
        'tbl_nft_verification_scans',
        'tbl_user_verified_nfts',
        'tbl_verified_wallet_scans'
    ];

    foreach ($jsonTables as $tableName) {
        if (!sqlite_table_exists($pdo, $tableName)) {
            continue;
        }

        $columns = get_table_columns($pdo, $tableName);
        $userColumn = null;
        foreach (['user_id', 'discord_id'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $userColumn = $candidate;
                break;
            }
        }

        if ($userColumn === null) {
            continue;
        }

        $jsonColumn = null;
        foreach (['verified_nfts_json', 'nfts_json', 'scan_result_json', 'verification_json', 'result_json', 'wallet_nfts_json'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $jsonColumn = $candidate;
                break;
            }
        }

        if ($jsonColumn === null) {
            continue;
        }

        $stmt = $pdo->prepare("SELECT {$jsonColumn} AS payload_json FROM {$tableName} WHERE {$userColumn} = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $payload = json_decode((string)($row['payload_json'] ?? ''), true);
            if (!is_array($payload)) {
                continue;
            }

            $candidateArrays = [];
            if (isset($payload[0]) && is_array($payload[0])) {
                $candidateArrays[] = $payload;
            }
            foreach (['verified_nfts', 'nfts', 'items', 'assets', 'wallet_nfts'] as $key) {
                if (isset($payload[$key]) && is_array($payload[$key])) {
                    $candidateArrays[] = $payload[$key];
                }
            }

            foreach ($candidateArrays as $candidateArray) {
                foreach ($candidateArray as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $tokenId = trim((string)($item['token_id'] ?? $item['mint'] ?? $item['token'] ?? ''));
                    $collection = normalize_collection_label((string)($item['collection'] ?? $item['collection_name'] ?? 'genesis'));

                    if ($tokenId === '' || $collection !== GENESIS_ABILITY_COLLECTION) {
                        continue;
                    }

                    $compoundKey = strtolower($collection . '::' . $tokenId);
                    if (isset($seen[$compoundKey])) {
                        continue;
                    }

                    $seen[$compoundKey] = true;
                    $normalized[] = [
                        'user_id' => $userId,
                        'token_id' => $tokenId,
                        'collection' => $collection,
                        'nft_name' => (string)($item['nft_name'] ?? $item['name'] ?? ''),
                        'image_url' => (string)($item['image_url'] ?? $item['image'] ?? ''),
                        'mint' => (string)($item['mint'] ?? $tokenId),
                        'wallet_address' => (string)($item['wallet_address'] ?? ''),
                        'traits' => normalize_traits_array($item['traits'] ?? [])
                    ];
                }
            }
        }

        if (!empty($normalized)) {
            return $normalized;
        }
    }

    // 3) Older direct verification tables
    $legacyTables = [
        'tbl_verified_holders',
        'tbl_holder_verifications',
        'tbl_holder_scan_results',
        'tbl_user_holder_verifications',
        'tbl_verified_wallet_nfts',
        'tbl_verified_nfts'
    ];

    foreach ($legacyTables as $tableName) {
        if (!sqlite_table_exists($pdo, $tableName)) {
            continue;
        }

        $columns = get_table_columns($pdo, $tableName);

        $userColumn = null;
        foreach (['user_id', 'discord_id'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $userColumn = $candidate;
                break;
            }
        }

        if ($userColumn === null || !in_array('token_id', $columns, true)) {
            continue;
        }

        $collectionExpr = in_array('collection', $columns, true)
            ? "LOWER(COALESCE(collection, '')) AS collection"
            : "'genesis' AS collection";

        $nftNameExpr = in_array('nft_name', $columns, true) ? "COALESCE(nft_name, '') AS nft_name" : "'' AS nft_name";
        $imageExpr = in_array('image_url', $columns, true) ? "COALESCE(image_url, '') AS image_url" : "'' AS image_url";
        $mintExpr = in_array('mint', $columns, true) ? "COALESCE(mint, token_id, '') AS mint" : "COALESCE(token_id, '') AS mint";
        $walletExpr = in_array('wallet_address', $columns, true) ? "COALESCE(wallet_address, '') AS wallet_address" : "'' AS wallet_address";

        $stmt = $pdo->prepare("
            SELECT
                {$userColumn} AS user_id,
                token_id,
                {$collectionExpr},
                {$nftNameExpr},
                {$imageExpr},
                {$mintExpr},
                {$walletExpr}
            FROM {$tableName}
            WHERE {$userColumn} = ?
        ");
        $stmt->execute([$userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $tokenId = trim((string)($row['token_id'] ?? ''));
            $collection = normalize_collection_label((string)($row['collection'] ?? 'genesis'));

            if ($tokenId === '' || $collection !== GENESIS_ABILITY_COLLECTION) {
                continue;
            }

            $compoundKey = strtolower($collection . '::' . $tokenId);
            if (isset($seen[$compoundKey])) {
                continue;
            }

            $seen[$compoundKey] = true;
            $normalized[] = [
                'user_id' => (string)($row['user_id'] ?? $userId),
                'token_id' => $tokenId,
                'collection' => $collection,
                'nft_name' => (string)($row['nft_name'] ?? ''),
                'image_url' => (string)($row['image_url'] ?? ''),
                'mint' => (string)($row['mint'] ?? $tokenId),
                'wallet_address' => (string)($row['wallet_address'] ?? ''),
                'traits' => fetch_nft_traits_for_token($pdo, $tokenId)
            ];
        }

        if (!empty($normalized)) {
            return $normalized;
        }
    }

    return [];
}



/**
 * Load static traits for a Genesis token when available.
 */
function fetch_nft_traits_for_token(PDO $pdo, string $tokenId): array {
    if (!sqlite_table_exists($pdo, 'tbl_nft_traits')) {
        return [];
    }

    $columns = get_table_columns($pdo, 'tbl_nft_traits');
    if (!in_array('token_id', $columns, true)) {
        return [];
    }

    $traitTypeColumn = in_array('trait_type', $columns, true) ? 'trait_type' : null;
    $traitValueColumn = in_array('trait_value', $columns, true) ? 'trait_value' : null;

    if ($traitTypeColumn === null || $traitValueColumn === null) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT
            {$traitTypeColumn} AS trait_type,
            {$traitValueColumn} AS trait_value
        FROM tbl_nft_traits
        WHERE token_id = ?
    ");
    $stmt->execute([$tokenId]);

    return normalize_traits_array($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}


/**
 * Load current trait upgrade levels for one NFT.
 */
function fetch_nft_trait_upgrade_rows(PDO $pdo, string $tokenId, string $collection): array {
    if (!sqlite_table_exists($pdo, 'tbl_nft_trait_upgrades')) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT
            trait_type,
            trait_value,
            current_level
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND LOWER(COALESCE(collection, '')) = LOWER(?)
    ");
    $stmt->execute([$tokenId, $collection]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

/**
 * Compute highest Genesis trait level for the selected NFT.
 * Missing rows default to level 1.
 */
function get_highest_genesis_trait_level_for_nft(PDO $pdo, array $selectedNft): int {
    $tokenId = trim((string)($selectedNft['token_id'] ?? ''));
    $collection = normalize_collection_label((string)($selectedNft['collection'] ?? GENESIS_ABILITY_COLLECTION));

    if ($tokenId === '') {
        return 1;
    }

    $traitRows = fetch_nft_trait_upgrade_rows($pdo, $tokenId, $collection);

    $levelMap = [];
    foreach ($traitRows as $row) {
        $traitType = normalize_trait_type((string)($row['trait_type'] ?? ''));
        $traitValue = trim((string)($row['trait_value'] ?? ''));
        $currentLevel = max(1, (int)($row['current_level'] ?? 1));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $levelMap[strtolower($traitType . '::' . $traitValue)] = $currentLevel;
    }

    $highest = 1;
    $nftTraits = normalize_traits_array($selectedNft['traits'] ?? []);

    if (!$nftTraits) {
        foreach ($levelMap as $level) {
            $highest = max($highest, (int)$level);
        }
        return max(1, $highest);
    }

    foreach ($nftTraits as $trait) {
        $traitType = normalize_trait_type((string)($trait['trait_type'] ?? ''));
        $traitValue = trim((string)($trait['trait_value'] ?? ''));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $compoundKey = strtolower($traitType . '::' . $traitValue);
        $level = isset($levelMap[$compoundKey]) ? (int)$levelMap[$compoundKey] : 1;
        $highest = max($highest, $level);
    }

    return max(1, $highest);
}

/**
 * Count active ability upgrades for one NFT.
 */
function get_active_nft_ability_upgrade_count(PDO $pdo, string $tokenId, string $collection): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND upgrade_status = ?
    ");
    $stmt->execute([$tokenId, $collection, NFT_ABILITY_STATUS_UPGRADING]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return canonical available DSPOINC = total score - active frozen stakes.
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $totalStmt->execute([$userId]);
    $total = (int)$totalStmt->fetchColumn();

    if (!sqlite_table_exists($pdo, 'tbl_dspoinc_stakes')) {
        return max(0, $total);
    }

    $frozenStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $frozenStmt->execute([$userId]);
    $frozen = (int)$frozenStmt->fetchColumn();

    return max(0, $total - $frozen);
}

/**
 * Find one ability row by token/category/key.
 */
function fetch_nft_ability_row_by_key(PDO $pdo, string $tokenId, string $collection, string $category, string $abilityKey): ?array {
    $stmt = $pdo->prepare("
        SELECT
            ability_upgrade_id,
            user_id,
            token_id,
            collection,
            category,
            ability_key,
            current_level,
            upgrade_status,
            upgrade_started_at,
            upgrade_ends_at,
            last_completed_at,
            last_notified_ready_at,
            ready_notification_count,
            base_duration_seconds,
            last_duration_seconds,
            last_upgrade_cost_dspoinc,
            unlock_source_trait_level,
            created_at,
            updated_at,
            last_owner_user_id
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND category = ?
          AND ability_key = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenId, $collection, $category, $abilityKey]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

try {
    $pdo = get_lab_database_connection();
    $request = get_request_data();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $userId = resolve_user_id();

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Discord authentication required'
        ], 401);
    }

    $tokenId = trim((string)($request['token_id'] ?? ''));
    $category = trim((string)($request['category'] ?? ''));
    $abilityKey = trim((string)($request['ability_key'] ?? ''));

    if ($tokenId === '') {
        json_response([
            'success' => false,
            'error' => 'token_id is required'
        ], 422);
    }

    if ($category === '' || !is_valid_nft_ability_category($category)) {
        json_response([
            'success' => false,
            'error' => 'Valid category is required'
        ], 422);
    }

    if ($abilityKey === '' || !is_valid_nft_ability_key($category, $abilityKey)) {
        json_response([
            'success' => false,
            'error' => 'Valid ability_key is required for the chosen category'
        ], 422);
    }

    $verifiedGenesisNfts = fetch_verified_genesis_nfts($pdo, $userId);
    if (empty($verifiedGenesisNfts)) {
        json_response([
            'success' => false,
            'error' => 'No verified Genesis NFTs found for this user'
        ], 403);
    }

    $selectedNft = null;
    foreach ($verifiedGenesisNfts as $nft) {
        if ((string)($nft['token_id'] ?? '') === $tokenId) {
            $selectedNft = $nft;
            break;
        }
    }

    if (!$selectedNft) {
        json_response([
            'success' => false,
            'error' => 'Requested token_id is not part of the user verified Genesis set'
        ], 403);
    }

    $collection = normalize_collection_label((string)($selectedNft['collection'] ?? GENESIS_ABILITY_COLLECTION));

    $pdo->beginTransaction();

    // Keep ownership and state fresh inside the transaction
    $verifiedGenesisNfts = fetch_verified_genesis_nfts($pdo, $userId);
    $selectedNft = null;
    foreach ($verifiedGenesisNfts as $nft) {
        if ((string)($nft['token_id'] ?? '') === $tokenId) {
            $selectedNft = $nft;
            break;
        }
    }

    if (!$selectedNft) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Requested token_id is no longer part of the user verified Genesis set'
        ], 403);
    }

    $collection = normalize_collection_label((string)($selectedNft['collection'] ?? GENESIS_ABILITY_COLLECTION));

    seed_missing_nft_ability_rows($pdo, $userId, $tokenId, $collection);
    auto_finalize_expired_nft_ability_upgrades($pdo, $tokenId, $collection);

    $highestTraitLevel = get_highest_genesis_trait_level_for_nft($pdo, $selectedNft);

    if (!is_nft_ability_category_unlocked($category, $highestTraitLevel)) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This ability category is still locked for the selected Genesis mouse',
            'data' => [
                'highest_trait_level' => $highestTraitLevel,
                'unlock_map' => get_nft_ability_unlock_map($highestTraitLevel)
            ]
        ], 409);
    }

    if (nft_has_active_ability_upgrade($pdo, $tokenId, $collection)) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This Genesis mouse already has an active ability upgrade'
        ], 409);
    }

    $abilityRow = fetch_nft_ability_row_by_key($pdo, $tokenId, $collection, $category, $abilityKey);
    if (!$abilityRow) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Ability row not found for the selected Genesis mouse'
        ], 404);
    }

    $currentLevel = max(1, (int)($abilityRow['current_level'] ?? 1));
    $upgradeStatus = (string)($abilityRow['upgrade_status'] ?? NFT_ABILITY_STATUS_IDLE);
    $abilityUpgradeId = (int)($abilityRow['ability_upgrade_id'] ?? 0);

    if ($upgradeStatus !== NFT_ABILITY_STATUS_IDLE) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This ability row is not idle right now'
        ], 409);
    }

    if ($currentLevel >= NFT_ABILITY_MAX_LEVEL) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This ability stat is already at max level'
        ], 409);
    }

    $availableDspoinc = get_user_available_dspoinc($pdo, $userId);
    $upgradeCost = get_nft_ability_upgrade_cost_dspoinc($currentLevel);
    $durationSeconds = get_nft_ability_upgrade_duration_seconds($currentLevel);

    if ($availableDspoinc < $upgradeCost) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Not enough available DSPOINC for this ability upgrade',
            'data' => [
                'available_dspoinc' => $availableDspoinc,
                'required_dspoinc' => $upgradeCost
            ]
        ], 409);
    }

    $scoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (
            user_id,
            game,
            score,
            source,
            timestamp
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $scoreStmt->execute([
        $userId,
        'lab_ability_upgrade',
        -$upgradeCost,
        'Genesis Ability Upgrade'
    ]);

        if (sqlite_table_exists($pdo, 'tbl_score_adjustments')) {
        $adjustmentColumns = get_table_columns($pdo, 'tbl_score_adjustments');

        // Use the same score-adjustment shape the live marketplace flow already uses.
        // This avoids NOT NULL failures on admin_id in the current SQLite schema.
        if (
            in_array('user_id', $adjustmentColumns, true) &&
            in_array('admin_id', $adjustmentColumns, true) &&
            in_array('amount', $adjustmentColumns, true) &&
            in_array('action', $adjustmentColumns, true) &&
            in_array('reason', $adjustmentColumns, true)
        ) {
            $adjustmentStmt = $pdo->prepare("
                INSERT INTO tbl_score_adjustments (
                    user_id,
                    admin_id,
                    amount,
                    action,
                    reason,
                    timestamp
                ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");

            $adjustmentStmt->execute([
                $userId,
                'system',
                -$upgradeCost,
                'remove',
                'Genesis ability upgrade spend'
            ]);
        }
    }

    $updateStmt = $pdo->prepare("
        UPDATE tbl_nft_ability_upgrades
        SET
            user_id = ?,
            upgrade_status = ?,
            upgrade_started_at = CURRENT_TIMESTAMP,
            upgrade_ends_at = datetime('now', '+' || ? || ' seconds'),
            base_duration_seconds = ?,
            last_duration_seconds = ?,
            last_upgrade_cost_dspoinc = ?,
            unlock_source_trait_level = ?,
            updated_at = CURRENT_TIMESTAMP,
            last_owner_user_id = ?
        WHERE ability_upgrade_id = ?
    ");

    $updateStmt->execute([
        $userId,
        NFT_ABILITY_STATUS_UPGRADING,
        $durationSeconds,
        $durationSeconds,
        $durationSeconds,
        $upgradeCost,
        $highestTraitLevel,
        $userId,
        $abilityUpgradeId
    ]);

    $updatedAbilityRow = fetch_nft_ability_row_by_key($pdo, $tokenId, $collection, $category, $abilityKey);
    if (!$updatedAbilityRow) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Ability upgrade row could not be reloaded after start'
        ], 500);
    }

    insert_nft_ability_history(
        $pdo,
        $abilityUpgradeId,
        $userId,
        $tokenId,
        $collection,
        $category,
        $abilityKey,
        'start_upgrade',
        $abilityRow,
        $updatedAbilityRow,
        null
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genesis ability upgrade started successfully',
        'data' => [
            'user_id' => $userId,
            'token_id' => $tokenId,
            'collection' => $collection,
            'category' => $category,
            'ability_key' => $abilityKey,
            'highest_trait_level' => $highestTraitLevel,
            'unlock_map' => get_nft_ability_unlock_map($highestTraitLevel),
            'available_dspoinc_after' => max(0, $availableDspoinc - $upgradeCost),
            'spent_dspoinc' => $upgradeCost,
            'duration_seconds' => $durationSeconds,
            'ability_row' => $updatedAbilityRow
        ]
    ], 200);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('❌ Start NFT Ability Upgrade Error: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Server error while starting Genesis ability upgrade',
        'details' => is_localhost_env() ? $e->getMessage() : null
    ], 500);
}