<?php
/**
 * 🧬 Admin Player Lab API
 *
 * Read-only admin endpoint for Narrrf's Lab progression.
 *
 * Stability-first rules:
 * - verified NFT scan data stays the identity layer
 * - tbl_nft_trait_upgrades stays the progression layer
 * - admin reads only the CURRENT verified Genesis NFTs for the requested user
 * - progression stays NFT-bound via token_id + collection + trait_type + trait_value
 */

error_reporting(0);
ini_set('display_errors', 0);

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

require_once __DIR__ . '/../user/genesis-ability-helpers.php';


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

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Read request data from JSON body, regular POST body, or GET.
 */
function get_request_data() {
    static $cachedRequest = null;

    if ($cachedRequest !== null) {
        return $cachedRequest;
    }

    $cachedRequest = [];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawInput, true);

        if (is_array($jsonInput)) {
            $cachedRequest = $jsonInput;
        } elseif (!empty($_POST) && is_array($_POST)) {
            $cachedRequest = $_POST;
        }
    } else {
        $cachedRequest = $_GET ?? [];
    }

    return is_array($cachedRequest) ? $cachedRequest : [];
}

/**
 * Determine if the request is running on localhost.
 */
function is_localhost_request() {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Open the Narrrfs World database in a way that works both locally and in production.
 */
function get_lab_database_connection() {
    if (function_exists('getDatabaseConnection')) {
        return getDatabaseConnection();
    }

    if (function_exists('getDB')) {
        return getDB();
    }

    $dbPathCandidates = [];

    if (is_localhost_request()) {
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
 * Check whether a SQLite table exists before querying it.
 */
function sqlite_table_exists(PDO $pdo, $tableName) {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1");
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Return the available columns for a table.
 */
function get_table_columns(PDO $pdo, $tableName) {
    if (!sqlite_table_exists($pdo, $tableName)) {
        return [];
    }

    $stmt = $pdo->query("PRAGMA table_info($tableName)");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $columns = [];

    foreach ($rows as $row) {
        if (!empty($row['name'])) {
            $columns[] = $row['name'];
        }
    }

    return $columns;
}

/**
 * Normalize trait type labels into the lab canon used by upgrade rows.
 */
function normalize_trait_type($rawTraitType) {
    $traitType = trim((string)$rawTraitType);
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
function normalize_traits_array($rawTraits) {
    if (!is_array($rawTraits)) {
        return [];
    }

    $seen = [];
    $normalized = [];

    foreach ($rawTraits as $trait) {
        if (!is_array($trait)) {
            continue;
        }

        $traitType = normalize_trait_type($trait['trait_type'] ?? $trait['type'] ?? $trait['trait'] ?? '');
        $traitValue = trim((string)($trait['trait_value'] ?? $trait['value'] ?? ''));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $key = $traitType . '::' . $traitValue;
        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $normalized[] = [
            'trait_type' => $traitType,
            'trait_value' => $traitValue
        ];
    }

    return $normalized;
}

/**
 * Normalize one NFT row into the stable lab shape.
 */
function normalize_nft_row($nftRow) {
    if (!is_array($nftRow)) {
        return null;
    }

    $tokenId = trim((string)($nftRow['token_id'] ?? $nftRow['tokenId'] ?? $nftRow['mint'] ?? $nftRow['mintAddress'] ?? ''));
    $collection = strtolower(trim((string)($nftRow['collection'] ?? '')));

    if ($tokenId === '' || $collection !== 'genesis') {
        return null;
    }

    $rawTraits = [];
    if (isset($nftRow['traits']) && is_array($nftRow['traits'])) {
        $rawTraits = $nftRow['traits'];
    } elseif (isset($nftRow['attributes']) && is_array($nftRow['attributes'])) {
        $rawTraits = $nftRow['attributes'];
    }

    return [
        'token_id' => $tokenId,
        'collection' => 'genesis',
        'nft_name' => (string)($nftRow['nft_name'] ?? $nftRow['name'] ?? 'Unnamed NFT'),
        'image_url' => (string)($nftRow['image_url'] ?? $nftRow['image'] ?? ''),
        'traits' => normalize_traits_array($rawTraits)
    ];
}

/**
 * Deduplicate NFTs by token id so the admin panel gets one current identity row per NFT.
 */
function dedupe_nfts_by_token($nfts) {
    $map = [];

    foreach ($nfts as $nft) {
        if (!is_array($nft) || empty($nft['token_id'])) {
            continue;
        }

        $map[(string)$nft['token_id']] = $nft;
    }

    return array_values($map);
}

/**
 * Ensure the upgrade table exists because admin reads from the same progression layer as the live lab.
 */
function ensure_upgrade_table(PDO $pdo) {
    if (sqlite_table_exists($pdo, 'tbl_nft_trait_upgrades')) {
        return false;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_nft_trait_upgrades (
        upgrade_id INTEGER PRIMARY KEY AUTOINCREMENT,
        token_id TEXT NOT NULL,
        collection TEXT NOT NULL,
        trait_type TEXT NOT NULL,
        trait_value TEXT NOT NULL,
        current_level INTEGER NOT NULL DEFAULT 1,
        upgrade_status TEXT NOT NULL DEFAULT 'idle',
        upgrade_started_at DATETIME NULL,
        upgrade_ends_at DATETIME NULL,
        last_completed_at DATETIME NULL,
        last_owner_user_id TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(token_id, collection, trait_type, trait_value)
    )");

    return true;
}

/**
 * Load traits for the given Genesis token ids from tbl_nft_traits when available.
 */
function load_traits_by_token(PDO $pdo, $tokenIds) {
    $traitsByToken = [];

    if (empty($tokenIds) || !sqlite_table_exists($pdo, 'tbl_nft_traits')) {
        return $traitsByToken;
    }

    $placeholders = implode(',', array_fill(0, count($tokenIds), '?'));
    $stmt = $pdo->prepare("SELECT token_id, trait_type, trait_value
        FROM tbl_nft_traits
        WHERE LOWER(collection) = 'genesis'
          AND token_id IN ($placeholders)
        ORDER BY token_id ASC, trait_type ASC, trait_value ASC");
    $stmt->execute($tokenIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
        $tokenId = trim((string)($row['token_id'] ?? ''));
        $traitType = normalize_trait_type($row['trait_type'] ?? '');
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
 * Strict current-owner loader using tbl_nft_ownership, which is the preferred verified identity layer.
 */
function load_verified_genesis_from_ownership(PDO $pdo, $userId, &$source) {
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

    if (!$userColumn) {
        return [];
    }

    $selectFields = ['token_id', 'collection'];
    foreach (['nft_name', 'image_url', 'metadata_json', 'name', 'image'] as $optionalColumn) {
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
            $decodedMetadata = json_decode((string)$row['metadata_json'], true);
            if (is_array($decodedMetadata)) {
                $metadata = $decodedMetadata;
            }
        }

        $tokenId = (string)($row['token_id'] ?? '');
        $nft = normalize_nft_row([
            'token_id' => $tokenId,
            'collection' => $row['collection'] ?? '',
            'nft_name' => $row['nft_name'] ?? $row['name'] ?? ($metadata['name'] ?? 'Unnamed NFT'),
            'image_url' => $row['image_url'] ?? $row['image'] ?? ($metadata['image'] ?? ''),
            'traits' => $traitsByToken[$tokenId] ?? ($metadata['attributes'] ?? $metadata['traits'] ?? [])
        ]);

        if ($nft !== null) {
            $nfts[] = $nft;
        }
    }

    if (!empty($nfts)) {
        $source = 'tbl_nft_ownership';
    }

    return dedupe_nfts_by_token($nfts);
}

/**
 * Fallback loader for older local tables that may still contain verified scan data.
 */
function load_verified_genesis_from_fallback_tables(PDO $pdo, $userId, &$source) {
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

        if (!$userColumn) {
            continue;
        }

        $jsonColumn = null;
        foreach (['genesis_nfts', 'nfts', 'nfts_json', 'verified_nfts', 'scan_data'] as $candidateColumn) {
            if (in_array($candidateColumn, $columns, true)) {
                $jsonColumn = $candidateColumn;
                break;
            }
        }

        if (!$jsonColumn) {
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
                $nft = normalize_nft_row($candidateNft);
                if ($nft !== null) {
                    $nfts[] = $nft;
                }
            }
        }

        if (!empty($nfts)) {
            $source = $tableName;
            return dedupe_nfts_by_token($nfts);
        }
    }

    return [];
}

/**
 * Load the current verified Genesis NFT set for the requested player.
 */
function load_current_verified_genesis_nfts(PDO $pdo, $userId, &$source) {
    $source = 'none';

    $strictNfts = load_verified_genesis_from_ownership($pdo, $userId, $source);
    if (!empty($strictNfts)) {
        return $strictNfts;
    }

    $fallbackNfts = load_verified_genesis_from_fallback_tables($pdo, $userId, $source);
    if (!empty($fallbackNfts)) {
        return $fallbackNfts;
    }

    return [];
}

/**
 * Build per-NFT summaries used by the admin player profile.
 */
function build_nft_summaries($verifiedGenesisNfts, $resolvedRows) {
    $summaryByToken = [];

    foreach ($verifiedGenesisNfts as $nft) {
        $tokenId = (string)($nft['token_id'] ?? '');
        if ($tokenId === '') {
            continue;
        }

        $summaryByToken[$tokenId] = [
            'token_id' => $tokenId,
            'collection' => $nft['collection'] ?? 'genesis',
            'nft_name' => $nft['nft_name'] ?? 'Unnamed NFT',
            'nft_level' => 0,
            'trait_count' => 0,
            'active_count' => 0,
            'ready_count' => 0,
            'highest_level' => 1,
            'highest_level_trait' => null
        ];
    }

    foreach ($resolvedRows as $row) {
        $tokenId = (string)($row['token_id'] ?? '');
        if ($tokenId === '') {
            continue;
        }

        if (!isset($summaryByToken[$tokenId])) {
            $summaryByToken[$tokenId] = [
                'token_id' => $tokenId,
                'collection' => $row['collection'] ?? 'genesis',
                'nft_name' => $row['nft_name'] ?? 'Unnamed NFT',
                'nft_level' => 0,
                'trait_count' => 0,
                'active_count' => 0,
                'ready_count' => 0,
                'highest_level' => 1,
                'highest_level_trait' => null
            ];
        }

        $status = strtolower((string)($row['upgrade_status'] ?? 'idle'));
        $level = max(1, (int)($row['current_level'] ?? 1));
        $traitLabel = trim((string)($row['trait_type'] ?? '')) . ': ' . trim((string)($row['trait_value'] ?? ''));

        $summaryByToken[$tokenId]['nft_level'] += $level;
        $summaryByToken[$tokenId]['trait_count']++;

        if ($status === 'upgrading') {
            $summaryByToken[$tokenId]['active_count']++;
        }

        if ($status === 'ready' || $status === 'ready_to_claim') {
            $summaryByToken[$tokenId]['ready_count']++;
        }

        if ($level > (int)$summaryByToken[$tokenId]['highest_level']) {
            $summaryByToken[$tokenId]['highest_level'] = $level;
            $summaryByToken[$tokenId]['highest_level_trait'] = $traitLabel;
        }
    }

    $summaries = array_values($summaryByToken);
    usort($summaries, function ($left, $right) {
        if ((int)$left['nft_level'] === (int)$right['nft_level']) {
            return strcmp((string)$left['token_id'], (string)$right['token_id']);
        }
        return ((int)$right['nft_level']) <=> ((int)$left['nft_level']);
    });

    return $summaries;
}

function load_user_genetic_items(PDO $pdo, $userId) {
    if (!sqlite_table_exists($pdo, 'tbl_user_genetic_items')) {
        return [];
    }

    $catalogExists = sqlite_table_exists($pdo, 'tbl_genetic_trait_catalog');

    if ($catalogExists) {
        $stmt = $pdo->prepare("
            SELECT
                ugi.genetic_item_id,
                ugi.user_id,
                ugi.catalog_id,
                ugi.trait_type,
                ugi.trait_value,
                ugi.current_level,
                ugi.upgrade_status,
                ugi.upgrade_started_at,
                ugi.upgrade_ends_at,
                ugi.last_completed_at,
                ugi.acquired_method,
                ugi.is_listed_for_sale,
                ugi.listed_listing_id,
                ugi.last_transfer_at,
                ugi.last_owner_user_id,
                ugi.created_at,
                ugi.updated_at,

                gtc.display_title,
                gtc.description,
                gtc.rarity_tier,
                gtc.rarity_count,
                gtc.base_price_dspoinc,
                gtc.image_type,
                gtc.image_path,
                gtc.preview_path,
                gtc.source_origin,
                gtc.effect_metadata_json,
                gtc.is_active,
                gtc.is_visible
            FROM tbl_user_genetic_items ugi
            LEFT JOIN tbl_genetic_trait_catalog gtc
                ON gtc.catalog_id = ugi.catalog_id
            WHERE ugi.user_id = ?
            ORDER BY
                ugi.current_level DESC,
                LOWER(COALESCE(gtc.display_title, ugi.trait_value, '')) ASC
        ");
        $stmt->execute([$userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $stmt = $pdo->prepare("
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
                acquired_method,
                is_listed_for_sale,
                listed_listing_id,
                last_transfer_at,
                last_owner_user_id,
                created_at,
                updated_at
            FROM tbl_user_genetic_items
            WHERE user_id = ?
            ORDER BY current_level DESC, LOWER(COALESCE(trait_value, '')) ASC
        ");
        $stmt->execute([$userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    foreach ($rows as &$row) {
        $row['genetic_item_id'] = (int)($row['genetic_item_id'] ?? 0);
        $row['catalog_id'] = (int)($row['catalog_id'] ?? 0);
        $row['current_level'] = max(1, (int)($row['current_level'] ?? 1));
        $row['is_listed_for_sale'] = (int)($row['is_listed_for_sale'] ?? 0);
        $row['listed_listing_id'] = isset($row['listed_listing_id']) ? (int)$row['listed_listing_id'] : null;
        $row['display_title'] = (string)($row['display_title'] ?? $row['trait_value'] ?? 'Unknown Trait');
        $row['trait_type'] = (string)($row['trait_type'] ?? '');
        $row['trait_value'] = (string)($row['trait_value'] ?? '');
        $row['rarity_tier'] = (string)($row['rarity_tier'] ?? 'common');
        $row['upgrade_status'] = (string)($row['upgrade_status'] ?? 'idle');
        $row['acquired_method'] = (string)($row['acquired_method'] ?? 'unknown');
    }
    unset($row);

    return $rows;
}

/**
 * Return available DSPOINC snapshot = total score - active frozen stakes.
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    if (!sqlite_table_exists($pdo, 'tbl_dspoinc_stakes')) {
        return max(0, $total);
    }

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
 * Return the highest Genesis trait level for one NFT from the trait upgrade layer.
 */
function get_highest_genesis_trait_level_for_token(PDO $pdo, string $tokenId, string $collection = 'genesis'): int {
    if (!sqlite_table_exists($pdo, 'tbl_nft_trait_upgrades')) {
        return 1;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(current_level), 1) AS highest_level
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND LOWER(COALESCE(collection, 'genesis')) = LOWER(?)
    ");
    $stmt->execute([$tokenId, $collection]);

    $highest = (int)$stmt->fetchColumn();
    return max(1, $highest);
}

/**
 * Return one admin-safe ability snapshot for one Genesis NFT.
 * Read-only only. This does not mutate holder-owned state beyond helper-safe auto-finalize behavior.
 */
function build_admin_nft_ability_snapshot(PDO $pdo, string $userId, array $nft): array {
    $tokenId = trim((string)($nft['token_id'] ?? ''));
    $collection = strtolower(trim((string)($nft['collection'] ?? 'genesis')));

    if ($tokenId === '') {
        return [
            'token_id' => '',
            'collection' => 'genesis',
            'highest_trait_level' => 1,
            'unlock_map' => get_nft_ability_unlock_map(1),
            'ability_rows' => [],
            'active_ability_upgrade_count' => 0,
            'auto_completed' => [],
            'available_dspoinc' => get_user_available_dspoinc($pdo, $userId)
        ];
    }

    if ($collection === '') {
        $collection = 'genesis';
    }

    if (sqlite_table_exists($pdo, 'tbl_nft_ability_upgrades')) {
        seed_missing_nft_ability_rows($pdo, $userId, $tokenId, $collection);
        auto_finalize_expired_nft_ability_upgrades($pdo, $tokenId, $collection);
    }

    $highestTraitLevel = get_highest_genesis_trait_level_for_token($pdo, $tokenId, $collection);
    $unlockMap = get_nft_ability_unlock_map($highestTraitLevel);

    $abilityRows = [];
    $activeAbilityUpgradeCount = 0;

    if (sqlite_table_exists($pdo, 'tbl_nft_ability_upgrades')) {
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
              AND LOWER(COALESCE(collection, 'genesis')) = LOWER(?)
            ORDER BY
              CASE category
                WHEN 'Fitness' THEN 1
                WHEN 'Weapons' THEN 2
                WHEN 'Education' THEN 3
                ELSE 99
              END,
              CASE ability_key
                WHEN 'HP' THEN 1
                WHEN 'SPEED' THEN 2
                WHEN 'AIR' THEN 3
                WHEN 'ATK' THEN 4
                WHEN 'DEF' THEN 5
                WHEN 'SPECIAL' THEN 6
                WHEN 'SPELLS' THEN 7
                WHEN 'CRAFTING' THEN 8
                WHEN 'EXPANSION' THEN 9
                ELSE 99
              END
        ");
        $stmt->execute([$tokenId, $collection]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $row['current_level'] = max(1, (int)($row['current_level'] ?? 1));
            $row['upgrade_status'] = (string)($row['upgrade_status'] ?? 'idle');
            $row['next_cost'] = $row['current_level'] >= NFT_ABILITY_MAX_LEVEL
                ? 0
                : get_nft_ability_upgrade_cost_dspoinc((int)$row['current_level']);
            $row['next_duration_seconds'] = $row['current_level'] >= NFT_ABILITY_MAX_LEVEL
                ? 0
                : get_nft_ability_upgrade_duration_seconds((int)$row['current_level']);
            $row['state'] = $row['upgrade_status'];

            if ($row['upgrade_status'] === NFT_ABILITY_STATUS_UPGRADING) {
                $activeAbilityUpgradeCount++;
            }

            $abilityRows[] = $row;
        }
    }

    return [
        'token_id' => $tokenId,
        'collection' => $collection,
        'highest_trait_level' => $highestTraitLevel,
        'unlock_map' => $unlockMap,
        'ability_rows' => $abilityRows,
        'active_ability_upgrade_count' => $activeAbilityUpgradeCount,
        'auto_completed' => [],
        'available_dspoinc' => get_user_available_dspoinc($pdo, $userId)
    ];
}



/**
 * Build ability snapshots for all verified Genesis NFTs.
 */
function build_admin_ability_by_token(PDO $pdo, string $userId, array $verifiedGenesisNfts): array {
    $abilityByToken = [];

    foreach ($verifiedGenesisNfts as $nft) {
        $tokenId = trim((string)($nft['token_id'] ?? ''));
        if ($tokenId === '') {
            continue;
        }

        $abilityByToken[$tokenId] = build_admin_nft_ability_snapshot($pdo, $userId, $nft);
    }

    return $abilityByToken;
}


try {
    $request = get_request_data();
    $userId = trim((string)($request['user_id'] ?? ''));

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'user_id is required'
        ], 400);
    }

    $pdo = get_lab_database_connection();
    $tableCreated = ensure_upgrade_table($pdo);

$verifiedSource = 'none';
$verifiedGenesisNfts = load_current_verified_genesis_nfts($pdo, $userId, $verifiedSource);

$resolvedRows = [];

    $selectUpgrade = $pdo->prepare("SELECT upgrade_id, token_id, collection, trait_type, trait_value, current_level,
        upgrade_status, upgrade_started_at, upgrade_ends_at, last_completed_at,
        last_owner_user_id, created_at, updated_at
        FROM tbl_nft_trait_upgrades
        WHERE token_id = :token_id
          AND LOWER(collection) = 'genesis'
          AND trait_type = :trait_type
          AND trait_value = :trait_value
        LIMIT 1");

    foreach ($verifiedGenesisNfts as $nft) {
        foreach (($nft['traits'] ?? []) as $trait) {
            $traitType = normalize_trait_type($trait['trait_type'] ?? '');
            $traitValue = trim((string)($trait['trait_value'] ?? ''));

            if ($traitType === '' || $traitValue === '') {
                continue;
            }

            $selectUpgrade->execute([
                ':token_id' => $nft['token_id'],
                ':trait_type' => $traitType,
                ':trait_value' => $traitValue
            ]);

            $row = $selectUpgrade->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $row['current_level'] = max(1, (int)($row['current_level'] ?? 1));
                $row['upgrade_status'] = (string)($row['upgrade_status'] ?? 'idle');
                $row['nft_name'] = $nft['nft_name'];
                $resolvedRows[] = $row;
                continue;
            }

            $resolvedRows[] = [
                'upgrade_id' => null,
                'token_id' => $nft['token_id'],
                'collection' => 'genesis',
                'trait_type' => $traitType,
                'trait_value' => $traitValue,
                'current_level' => 1,
                'upgrade_status' => 'idle',
                'upgrade_started_at' => null,
                'upgrade_ends_at' => null,
                'last_completed_at' => null,
                'last_owner_user_id' => null,
                'created_at' => null,
                'updated_at' => null,
                'nft_name' => $nft['nft_name'],
                'source' => 'fallback'
            ];
        }
    }

    $activeCount = 0;
    $readyCount = 0;
    $highestLevel = 1;
    $highestLevelTrait = null;

    foreach ($resolvedRows as $row) {
        $status = strtolower((string)($row['upgrade_status'] ?? 'idle'));
        $level = max(1, (int)($row['current_level'] ?? 1));
        $traitLabel = trim((string)($row['trait_type'] ?? '')) . ': ' . trim((string)($row['trait_value'] ?? ''));

        if ($status === 'upgrading') {
            $activeCount++;
        }

        if ($status === 'ready' || $status === 'ready_to_claim') {
            $readyCount++;
        }

        if ($level > $highestLevel) {
            $highestLevel = $level;
            $highestLevelTrait = $traitLabel;
        }
    }

    $nftSummaries = build_nft_summaries($verifiedGenesisNfts, $resolvedRows);
    $geneticItems = load_user_genetic_items($pdo, $userId);
    $abilityByToken = build_admin_ability_by_token($pdo, $userId, $verifiedGenesisNfts);
    $geneticInventoryCount = count($geneticItems);
    $totalLabPower = array_reduce($nftSummaries, function ($sum, $row) {
        return $sum + (int)($row['nft_level'] ?? 0);
    }, 0);

    json_response([
    'success' => true,
    'data' => [
        'user_id' => $userId,
        'verified_source' => $verifiedSource,
        'upgrade_table' => 'tbl_nft_trait_upgrades',
        'table_created_now' => $tableCreated,
        'verified_genesis_nfts' => $verifiedGenesisNfts,
        'upgrades' => $resolvedRows,
        'nft_summaries' => $nftSummaries,
        'ability_by_token' => $abilityByToken,
        'genetic_items' => $geneticItems,
        'summary' => [
            'verified_nft_count' => count($verifiedGenesisNfts),
            'upgrade_row_count' => count($resolvedRows),
            'active_count' => $activeCount,
            'ready_count' => $readyCount,
            'total_lab_power' => $totalLabPower,
            'highest_level' => $highestLevel,
            'highest_level_trait' => $highestLevelTrait,
            'genetic_inventory_count' => $geneticInventoryCount
        ]
    ]
]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => 'Failed to load admin player lab profile',
        'details' => $e->getMessage()
    ], 500);
}
