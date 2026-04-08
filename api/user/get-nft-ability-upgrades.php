<?php
// 🧬 Get NFT Ability Upgrades API
// Read-only Genesis ability matrix endpoint for one verified Genesis mouse.
//
// Stability-first rules:
// - Genesis lane stays NFT-bound and holder-protected
// - Verified Genesis NFT scan data stays the identity layer
// - Ability progression is separate from tbl_nft_trait_upgrades
// - Ability progression belongs to exact token_id + collection
// - One NFT owns one 9-stat ability matrix
// - Missing ability rows are lazily seeded
// - Expired ability upgrades auto-complete on read
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
        error_log("🧬 Get NFT Ability Upgrades: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Get NFT Ability Upgrades - user_id mismatch. Session: {$sessionUserId}, Request: {$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalhost) {
        error_log("🧬 Get NFT Ability Upgrades: Using local test user (Narrrf) for localhost");
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
    $stmt = $pdo->prepare("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
          AND name = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Return available columns for a table.
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
 * Normalize collection labels into the Genesis lane canon.
 */
function normalize_collection_label(string $collection): string {
    $value = strtolower(trim($collection));

    if ($value === '' || $value === 'genesis') {
        return GENESIS_ABILITY_COLLECTION;
    }

    return $value;
}

/**
 * Normalize trait type labels into the same canon as the Lab progression layer.
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
    $parts = array_map(static function ($part) {
        return $part === '' ? '' : strtoupper(substr($part, 0, 1)) . strtolower(substr($part, 1));
    }, $parts ?: []);

    return str_replace('Sub-trait', 'Sub-Trait', implode(' ', $parts));
}

/**
 * Normalize an arbitrary traits array into deduplicated trait rows.
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

        $traitType = normalize_trait_type((string)($trait['trait_type'] ?? $trait['type'] ?? $trait['trait'] ?? ''));
        $traitValue = trim((string)($trait['trait_value'] ?? $trait['value'] ?? ''));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $compoundKey = strtolower($traitType . '::' . $traitValue);
        if (isset($seen[$compoundKey])) {
            continue;
        }

        $seen[$compoundKey] = true;

        $normalized[] = [
            'trait_type' => $traitType,
            'trait_value' => $traitValue
        ];
    }

    return $normalized;
}

/**
 * Load trait rows for the given Genesis token ids from tbl_nft_traits when available.
 *
 * This mirrors the live Genesis trait loader so the ability matrix sees the
 * same NFT identity and same trait payload that the Lab trait chamber uses.
 */
function load_traits_by_token(PDO $pdo, array $tokenIds): array {
    $traitsByToken = [];

    if (empty($tokenIds) || !sqlite_table_exists($pdo, 'tbl_nft_traits')) {
        return $traitsByToken;
    }

    $placeholders = implode(',', array_fill(0, count($tokenIds), '?'));
    $stmt = $pdo->prepare("
        SELECT token_id, trait_type, trait_value
        FROM tbl_nft_traits
        WHERE LOWER(collection) = 'genesis'
          AND token_id IN ($placeholders)
        ORDER BY token_id ASC, trait_type ASC, trait_value ASC
    ");
    $stmt->execute($tokenIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
        $tokenId = trim((string)($row['token_id'] ?? ''));
        $traitType = normalize_trait_type((string)($row['trait_type'] ?? ''));
        $traitValue = trim((string)($row['trait_value'] ?? ''));

        if ($tokenId === '' || $traitType === '' || $traitValue === '') {
            continue;
        }

        if (!isset($traitsByToken[$tokenId])) {
            $traitsByToken[$tokenId] = [];
        }

        $traitsByToken[$tokenId][] = [
            'trait_type' => $traitType,
            'trait_value' => $traitValue
        ];
    }

    foreach ($traitsByToken as $tokenId => $traits) {
        $traitsByToken[$tokenId] = normalize_traits_array($traits);
    }

    return $traitsByToken;
}

/**
 * Normalize one NFT row into the Genesis lane shape used by the ability matrix.
 */
function normalize_verified_genesis_nft_row(array $rawNft): ?array {
    $tokenId = trim((string)($rawNft['token_id'] ?? ''));
    $collection = normalize_collection_label((string)($rawNft['collection'] ?? 'genesis'));

    if ($tokenId === '' || $collection !== GENESIS_ABILITY_COLLECTION) {
        return null;
    }

    $traits = normalize_traits_array($rawNft['traits'] ?? []);

    return [
        'user_id' => trim((string)($rawNft['user_id'] ?? '')),
        'token_id' => $tokenId,
        'collection' => $collection,
        'nft_name' => trim((string)($rawNft['nft_name'] ?? $rawNft['name'] ?? ('Genesis Mouse #' . $tokenId))) ?: ('Genesis Mouse #' . $tokenId),
        'image_url' => trim((string)($rawNft['image_url'] ?? $rawNft['image'] ?? $rawNft['image_path'] ?? '')),
        'mint' => trim((string)($rawNft['mint'] ?? '')),
        'wallet_address' => trim((string)($rawNft['wallet_address'] ?? '')),
        'traits' => $traits
    ];
}

/**
 * Deduplicate normalized NFT rows by token id so ownership moves keep one canon row.
 */
function dedupe_verified_genesis_nfts(array $nfts): array {
    $map = [];

    foreach ($nfts as $nft) {
        if (!is_array($nft)) {
            continue;
        }

        $tokenId = trim((string)($nft['token_id'] ?? ''));
        if ($tokenId === '') {
            continue;
        }

        $map[$tokenId] = $nft;
    }

    ksort($map, SORT_NATURAL);
    return array_values($map);
}

/**
 * Strict current-owner loader using tbl_nft_ownership, which is the preferred Genesis identity layer.
 *
 * This is intentionally aligned with get-player-lab.php so ability reads follow the
 * same owner resolution and so a newly verified owner receives the same persistent
 * token-bound values back that the Genesis trait system already preserves.
 */
function load_verified_genesis_from_ownership(PDO $pdo, string $userId): array {
    if (!sqlite_table_exists($pdo, 'tbl_nft_ownership')) {
        return [];
    }

    $columns = get_table_columns($pdo, 'tbl_nft_ownership');
    if (!in_array('token_id', $columns, true) || !in_array('collection', $columns, true)) {
        return [];
    }

    $userColumn = null;
    foreach (['user_id', 'discord_id', 'owner_user_id'] as $candidateColumn) {
        if (in_array($candidateColumn, $columns, true)) {
            $userColumn = $candidateColumn;
            break;
        }
    }

    if ($userColumn === null) {
        return [];
    }

    $selectFields = ['token_id', 'collection'];
    foreach (['nft_name', 'image_url', 'metadata_json', 'name', 'image', 'mint', 'wallet_address'] as $optionalColumn) {
        if (in_array($optionalColumn, $columns, true)) {
            $selectFields[] = $optionalColumn;
        }
    }

    $whereParts = ["$userColumn = :user_id", "LOWER(collection) = 'genesis'"];
    if (in_array('is_verified', $columns, true)) {
        $whereParts[] = 'COALESCE(is_verified, 0) = 1';
    }

    $sql = 'SELECT ' . implode(', ', $selectFields) . ' FROM tbl_nft_ownership WHERE ' . implode(' AND ', $whereParts) . ' ORDER BY token_id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (!$rows) {
        return [];
    }

    $tokenIds = [];
    foreach ($rows as $row) {
        $tokenId = trim((string)($row['token_id'] ?? ''));
        if ($tokenId !== '') {
            $tokenIds[] = $tokenId;
        }
    }

    $tokenIds = array_values(array_unique($tokenIds));
    $traitsByToken = load_traits_by_token($pdo, $tokenIds);

    $nfts = [];
    foreach ($rows as $row) {
        $metadata = [];
        if (!empty($row['metadata_json'])) {
            $decodedMetadata = json_decode((string)($row['metadata_json'] ?? ''), true);
            if (is_array($decodedMetadata)) {
                $metadata = $decodedMetadata;
            }
        }

        $tokenId = (string)($row['token_id'] ?? '');
        $normalized = normalize_verified_genesis_nft_row([
            'user_id' => $userId,
            'token_id' => $tokenId,
            'collection' => $row['collection'] ?? 'genesis',
            'nft_name' => $row['nft_name'] ?? $row['name'] ?? ($metadata['name'] ?? ('Genesis Mouse #' . $tokenId)),
            'image_url' => $row['image_url'] ?? $row['image'] ?? ($metadata['image'] ?? ''),
            'mint' => $row['mint'] ?? ($metadata['mint'] ?? ''),
            'wallet_address' => $row['wallet_address'] ?? ($metadata['wallet_address'] ?? ''),
            'traits' => $traitsByToken[$tokenId] ?? ($metadata['attributes'] ?? $metadata['traits'] ?? [])
        ]);

        if ($normalized !== null) {
            $nfts[] = $normalized;
        }
    }

    return dedupe_verified_genesis_nfts($nfts);
}

/**
 * Fallback loader for older verified scan tables that may still hold Genesis data.
 */
function load_verified_genesis_from_fallback_tables(PDO $pdo, string $userId): array {
    $candidateTables = [
        'tbl_verified_nft_scans',
        'tbl_nft_verification_scans',
        'tbl_user_verified_nfts',
        'tbl_verified_wallet_scans'
    ];

    foreach ($candidateTables as $tableName) {
        if (!sqlite_table_exists($pdo, $tableName)) {
            continue;
        }

        $columns = get_table_columns($pdo, $tableName);

        $userColumn = null;
        foreach (['user_id', 'discord_id', 'owner_user_id'] as $candidateColumn) {
            if (in_array($candidateColumn, $columns, true)) {
                $userColumn = $candidateColumn;
                break;
            }
        }

        if ($userColumn === null) {
            continue;
        }

        $jsonColumn = null;
        foreach (['genesis_nfts', 'nfts', 'nfts_json', 'verified_nfts', 'scan_data'] as $candidateColumn) {
            if (in_array($candidateColumn, $columns, true)) {
                $jsonColumn = $candidateColumn;
                break;
            }
        }

        if ($jsonColumn === null) {
            continue;
        }

        $stmt = $pdo->prepare("SELECT $jsonColumn AS nft_blob FROM $tableName WHERE $userColumn = ? ORDER BY rowid DESC LIMIT 10");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $nfts = [];
        foreach ($rows as $row) {
            $decoded = json_decode((string)($row['nft_blob'] ?? ''), true);
            if (!is_array($decoded)) {
                continue;
            }

            $candidateNfts = [];
            if (isset($decoded['genesis_nfts']) && is_array($decoded['genesis_nfts'])) {
                $candidateNfts = $decoded['genesis_nfts'];
            } elseif (isset($decoded['nfts']) && is_array($decoded['nfts'])) {
                $candidateNfts = $decoded['nfts'];
            } else {
                $candidateNfts = $decoded;
            }

            foreach ($candidateNfts as $candidateNft) {
                if (!is_array($candidateNft)) {
                    continue;
                }

                $normalized = normalize_verified_genesis_nft_row([
                    'user_id' => $userId,
                    'token_id' => $candidateNft['token_id'] ?? $candidateNft['id'] ?? '',
                    'collection' => $candidateNft['collection'] ?? 'genesis',
                    'nft_name' => $candidateNft['nft_name'] ?? $candidateNft['name'] ?? '',
                    'image_url' => $candidateNft['image_url'] ?? $candidateNft['image'] ?? $candidateNft['image_path'] ?? '',
                    'mint' => $candidateNft['mint'] ?? '',
                    'wallet_address' => $candidateNft['wallet_address'] ?? '',
                    'traits' => $candidateNft['traits'] ?? $candidateNft['attributes'] ?? []
                ]);

                if ($normalized !== null) {
                    $nfts[] = $normalized;
                }
            }
        }

        if (!empty($nfts)) {
            return dedupe_verified_genesis_nfts($nfts);
        }
    }

    return [];
}

/**
 * Final compatibility loader for direct-row verified holder tables used by some environments.
 */
function load_verified_genesis_from_legacy_direct_tables(PDO $pdo, string $userId): array {
    $candidateTables = [
        'tbl_verified_holders',
        'tbl_holder_verifications',
        'tbl_holder_scan_results',
        'tbl_user_holder_verifications',
        'tbl_verified_wallet_nfts',
        'tbl_verified_nfts'
    ];

    $matchedTable = null;
    $matchedColumns = [];

    foreach ($candidateTables as $tableName) {
        $columns = get_table_columns($pdo, $tableName);
        if (!$columns) {
            continue;
        }

        $requiredUserColumn = null;
        foreach (['user_id', 'discord_id'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $requiredUserColumn = $candidate;
                break;
            }
        }

        if ($requiredUserColumn === null) {
            continue;
        }

        if (!in_array('token_id', $columns, true) || !in_array('collection', $columns, true)) {
            continue;
        }

        $matchedTable = $tableName;
        $matchedColumns = $columns;
        break;
    }

    if ($matchedTable === null) {
        return [];
    }

    $userColumn = in_array('user_id', $matchedColumns, true) ? 'user_id' : 'discord_id';
    $selectParts = [$userColumn, 'token_id', 'collection'];

    $optionalColumns = [
        'nft_name',
        'name',
        'image_url',
        'image',
        'image_path',
        'mint',
        'wallet_address',
        'traits_json',
        'traits',
        'trait_data_json',
        'metadata_json',
        'is_verified',
        'verification_status',
        'status'
    ];

    foreach ($optionalColumns as $column) {
        if (in_array($column, $matchedColumns, true) && !in_array($column, $selectParts, true)) {
            $selectParts[] = $column;
        }
    }

    $sql = "
        SELECT " . implode(', ', $selectParts) . "
        FROM {$matchedTable}
        WHERE {$userColumn} = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
    ";

    if (in_array('is_verified', $matchedColumns, true)) {
        $sql .= " AND COALESCE(is_verified, 1) = 1 ";
    } elseif (in_array('verification_status', $matchedColumns, true)) {
        $sql .= " AND LOWER(COALESCE(verification_status, 'verified')) = 'verified' ";
    } elseif (in_array('status', $matchedColumns, true)) {
        $sql .= " AND LOWER(COALESCE(status, 'verified')) IN ('verified', 'active') ";
    }

    $sql .= ' ORDER BY token_id ASC ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $nfts = [];
    foreach ($rows as $row) {
        $traits = [];
        foreach (['traits_json', 'traits', 'trait_data_json'] as $traitColumn) {
            if (!empty($row[$traitColumn])) {
                $decoded = json_decode((string)$row[$traitColumn], true);
                if (is_array($decoded)) {
                    $traits = normalize_traits_array($decoded);
                    break;
                }
            }
        }

        if (!$traits && !empty($row['metadata_json'])) {
            $metadata = json_decode((string)$row['metadata_json'], true);
            if (is_array($metadata)) {
                $traits = normalize_traits_array($metadata['attributes'] ?? $metadata['traits'] ?? []);
            }
        }

        $normalized = normalize_verified_genesis_nft_row([
            'user_id' => $userId,
            'token_id' => $row['token_id'] ?? '',
            'collection' => $row['collection'] ?? 'genesis',
            'nft_name' => $row['nft_name'] ?? $row['name'] ?? '',
            'image_url' => $row['image_url'] ?? $row['image'] ?? $row['image_path'] ?? '',
            'mint' => $row['mint'] ?? '',
            'wallet_address' => $row['wallet_address'] ?? '',
            'traits' => $traits
        ]);

        if ($normalized !== null) {
            $nfts[] = $normalized;
        }
    }

    return dedupe_verified_genesis_nfts($nfts);
}

/**
 * Load verified Genesis NFTs using the same ownership-first identity order as the live Genesis trait system.
 */
function fetch_verified_genesis_nfts(PDO $pdo, string $userId): array {
    $ownershipNfts = load_verified_genesis_from_ownership($pdo, $userId);
    if (!empty($ownershipNfts)) {
        return $ownershipNfts;
    }

    $fallbackNfts = load_verified_genesis_from_fallback_tables($pdo, $userId);
    if (!empty($fallbackNfts)) {
        return $fallbackNfts;
    }

    return load_verified_genesis_from_legacy_direct_tables($pdo, $userId);
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
 * Return the highest Genesis trait level for one NFT.
 * This reads the NFT-bound Genesis trait progression table only.
 */
function get_highest_genesis_trait_level(PDO $pdo, string $tokenId, string $collection): int {
    $collection = trim(strtolower($collection));
    if ($collection === '') {
        $collection = 'genesis';
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(current_level), 1) AS highest_level
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND LOWER(COALESCE(collection, 'genesis')) = ?
    ");
    $stmt->execute([$tokenId, $collection]);

    $highestLevel = (int)$stmt->fetchColumn();

    return max(1, $highestLevel);
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
 * Decorate ability rows for frontend rendering.
 */
function decorate_nft_ability_rows(PDO $pdo, array $rows, int $highestTraitLevel, string $tokenId, string $collection): array {
    $hasActiveUpgrade = nft_has_active_ability_upgrade($pdo, $tokenId, $collection);
    $decorated = [];

    foreach ($rows as $row) {
        $category = (string)($row['category'] ?? '');
        $abilityKey = (string)($row['ability_key'] ?? '');
        $currentLevel = max(1, (int)($row['current_level'] ?? 1));
        $upgradeStatus = (string)($row['upgrade_status'] ?? NFT_ABILITY_STATUS_IDLE);
        $isUnlocked = is_nft_ability_category_unlocked($category, $highestTraitLevel);
        $requiredTraitLevel = (int)(NFT_ABILITY_UNLOCK_LEVELS[$category] ?? 999);
        $isMaxLevel = $currentLevel >= NFT_ABILITY_MAX_LEVEL;

        $decorated[] = array_merge($row, [
            'category' => $category,
            'ability_key' => $abilityKey,
            'current_level' => $currentLevel,
            'upgrade_status' => $upgradeStatus,
            'required_trait_level' => $requiredTraitLevel,
            'is_unlocked' => $isUnlocked,
            'is_max_level' => $isMaxLevel,
            'next_upgrade_duration_seconds' => $isMaxLevel ? 0 : get_nft_ability_upgrade_duration_seconds($currentLevel),
            'next_upgrade_cost_dspoinc' => $isMaxLevel ? 0 : get_nft_ability_upgrade_cost_dspoinc($currentLevel),
            'can_upgrade' => (
                $isUnlocked &&
                !$isMaxLevel &&
                $upgradeStatus === NFT_ABILITY_STATUS_IDLE &&
                !$hasActiveUpgrade
            )
        ]);
    }

    return $decorated;
}

function get_highest_genesis_trait_level_for_nft(PDO $pdo, array $nft): int {
    $tokenId = trim((string)($nft['token_id'] ?? ''));
    $collection = normalize_collection_label((string)($nft['collection'] ?? 'genesis'));

    if ($tokenId === '') {
        return 1;
    }

    return get_highest_genesis_trait_level($pdo, $tokenId, $collection);
}

try {
    $pdo = get_lab_database_connection();
    $request = get_request_data();
    $userId = resolve_user_id();

    if (trim($userId) === '') {
        json_response([
            'success' => false,
            'error' => 'Discord login required'
        ], 401);
    }

    $verifiedGenesisNfts = fetch_verified_genesis_nfts($pdo, $userId);

    if (!$verifiedGenesisNfts) {
        json_response([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'selected_token_id' => null,
                'selected_collection' => GENESIS_ABILITY_COLLECTION,
                'selected_nft' => null,
                'verified_genesis_nfts' => [],
                'highest_trait_level' => 0,
                'unlock_map' => get_nft_ability_unlock_map(0),
                'ability_rows' => [],
                'active_ability_upgrade_count' => 0,
                'auto_completed' => [],
                'available_dspoinc' => get_user_available_dspoinc($pdo, $userId),
                'message' => 'No verified Genesis NFTs found for this user'
            ]
        ]);
    }

    $requestedTokenId = trim((string)($request['token_id'] ?? ''));
    $selectedNft = null;

    if ($requestedTokenId !== '') {
        foreach ($verifiedGenesisNfts as $nft) {
            if ((string)$nft['token_id'] === $requestedTokenId) {
                $selectedNft = $nft;
                break;
            }
        }

        if ($selectedNft === null) {
            json_response([
                'success' => false,
                'error' => 'Requested token_id is not part of the user verified Genesis set'
            ], 403);
        }
    } else {
        $selectedNft = $verifiedGenesisNfts[0];
    }

    $tokenId = (string)$selectedNft['token_id'];
    $collection = GENESIS_ABILITY_COLLECTION;

    $pdo->beginTransaction();

    seed_missing_nft_ability_rows($pdo, $userId, $tokenId, $collection);
    $autoCompleted = auto_finalize_expired_nft_ability_upgrades($pdo, $tokenId, $collection);
    $abilityRows = fetch_nft_ability_rows($pdo, $tokenId, $collection);

    $pdo->commit();

    $highestTraitLevel = get_highest_genesis_trait_level($pdo, $tokenId, $collection);
    $unlockMap = get_nft_ability_unlock_map($highestTraitLevel);
    $activeAbilityUpgradeCount = get_active_nft_ability_upgrade_count($pdo, $tokenId, $collection);
    $availableDspoinc = get_user_available_dspoinc($pdo, $userId);

    $decoratedRows = decorate_nft_ability_rows(
        $pdo,
        $abilityRows,
        $highestTraitLevel,
        $tokenId,
        $collection
    );

    json_response([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'selected_token_id' => $tokenId,
            'selected_collection' => $collection,
            'selected_nft' => $selectedNft,
            'verified_genesis_nfts' => $verifiedGenesisNfts,
            'highest_trait_level' => $highestTraitLevel,
            'unlock_map' => $unlockMap,
            'ability_rows' => $decoratedRows,
            'active_ability_upgrade_count' => $activeAbilityUpgradeCount,
            'auto_completed' => $autoCompleted,
            'available_dspoinc' => $availableDspoinc
        ]
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Failed to load Genesis ability upgrades',
        'details' => $error->getMessage()
    ], 500);
}