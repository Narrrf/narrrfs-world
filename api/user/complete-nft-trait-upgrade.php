<?php
// 🧬 Complete NFT Trait Upgrade API
// Claims a finished upgrade for one verified Genesis NFT trait.
// Stability-first rules:
// - Only verified Genesis NFTs may be claimed
// - Exact trait value must exist on the NFT
// - Upgrade belongs to NFT token + exact trait value
// - Claim increments level only after timer is complete
// - Claimed row returns to idle state for the next cycle
// - Localhost may use request user_id or Narrrf fallback
// - Production stays session-first with mismatch protection

error_reporting(0);
ini_set('display_errors', 0);

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

/**
 * Return a JSON response and stop execution.
 */
function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running in local development.
 */
function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON, POST, or GET.
 */
function get_request_data() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
 * Resolve current user ID with localhost override support.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Complete Trait Upgrade: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Complete Trait Upgrade - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Complete Trait Upgrade: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

/**
 * Normalize trait type labels to the lab canon.
 */
function normalize_trait_type($raw) {
    $t = trim((string)$raw);
    if ($t === '') {
        return '';
    }

    $lower = strtolower($t);

    if (in_array($lower, ['sub trait', 'sub-trait', 'subtrait'], true)) {
        return 'Sub-Trait';
    }

    if (in_array($lower, ['special trait', 'special'], true)) {
        return 'Special';
    }

    $parts = explode(' ', $t);
    $parts = array_map(function ($part) {
        return $part === '' ? $part : strtoupper(substr($part, 0, 1)) . substr($part, 1);
    }, $parts);

    return str_replace('Sub-trait', 'Sub-Trait', implode(' ', $parts));
}

/**
 * Normalize a raw trait array into unique trait_type + trait_value rows.
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
 * Normalize one NFT row into the lab-safe Genesis NFT shape.
 */
function normalize_nft_row($nft) {
    if (!is_array($nft)) {
        return null;
    }

    $tokenId = trim((string)($nft['token_id'] ?? $nft['tokenId'] ?? $nft['mint'] ?? $nft['mintAddress'] ?? $nft['address'] ?? ''));
    $collection = strtolower(trim((string)($nft['collection'] ?? '')));

    if ($tokenId === '' || $collection !== 'genesis') {
        return null;
    }

    $rawTraits = [];
    if (isset($nft['traits']) && is_array($nft['traits'])) {
        $rawTraits = $nft['traits'];
    } elseif (isset($nft['attributes']) && is_array($nft['attributes'])) {
        $rawTraits = $nft['attributes'];
    }

    return [
        'token_id' => $tokenId,
        'collection' => 'genesis',
        'nft_name' => $nft['nft_name'] ?? $nft['name'] ?? 'Unnamed NFT',
        'image_url' => $nft['image_url'] ?? $nft['image'] ?? '',
        'traits' => normalize_traits_array($rawTraits)
    ];
}

/**
 * Deduplicate NFT rows by token_id.
 */
function dedupe_nfts_by_token($nfts) {
    $map = [];

    foreach ($nfts as $nft) {
        if (!is_array($nft)) {
            continue;
        }

        $tokenId = $nft['token_id'] ?? '';
        if ($tokenId === '') {
            continue;
        }

        $map[$tokenId] = $nft;
    }

    return array_values($map);
}

/**
 * Ensure the progression table exists before claim logic runs.
 */
function ensure_upgrade_table(PDO $pdo) {
    $existsStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_nft_trait_upgrades'");
    $exists = $existsStmt ? $existsStmt->fetch(PDO::FETCH_ASSOC) : false;

    if (!$exists) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tbl_nft_trait_upgrades (
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
            )
        ");

        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_trait_upgrades_token ON tbl_nft_trait_upgrades(token_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_trait_upgrades_collection ON tbl_nft_trait_upgrades(collection)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_trait_upgrades_status ON tbl_nft_trait_upgrades(upgrade_status)");

        error_log("🧬 Complete Trait Upgrade: Created tbl_nft_trait_upgrades");
    }
}

/**
 * Load verified Genesis NFTs from likely storage tables.
 */
function load_verified_genesis_from_db(PDO $pdo, $user_id) {
    $candidateTables = [
        'tbl_verified_nft_scans',
        'tbl_nft_verification_scans',
        'tbl_user_verified_nfts',
        'tbl_verified_wallet_scans',
        'tbl_nft_ownership'
    ];

    $allNfts = [];

    foreach ($candidateTables as $table) {
        $existsStmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
        $existsStmt->execute([$table]);
        $exists = $existsStmt->fetch(PDO::FETCH_ASSOC);

        if (!$exists) {
            continue;
        }

        $columnsStmt = $pdo->query("PRAGMA table_info({$table})");
        $columnsRaw = $columnsStmt ? $columnsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $columns = array_map(function ($col) {
            return $col['name'] ?? '';
        }, $columnsRaw);

        $userColumn = null;
        foreach (['user_id', 'discord_id', 'owner_user_id'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $userColumn = $candidate;
                break;
            }
        }

        if (!$userColumn) {
            continue;
        }

        $jsonColumn = null;
        foreach (['genesis_nfts', 'nfts', 'nfts_json', 'verified_nfts', 'scan_data'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $jsonColumn = $candidate;
                break;
            }
        }

        if ($jsonColumn) {
            $stmt = $pdo->prepare("SELECT {$jsonColumn} AS nft_json_blob FROM {$table} WHERE {$userColumn} = ? ORDER BY rowid DESC LIMIT 10");
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $decoded = json_decode($row['nft_json_blob'] ?? '', true);
                if (!is_array($decoded)) {
                    continue;
                }

                $nfts = [];
                if (isset($decoded['genesis_nfts']) && is_array($decoded['genesis_nfts'])) {
                    $nfts = $decoded['genesis_nfts'];
                } elseif (function_exists('array_is_list') && array_is_list($decoded)) {
                    $nfts = $decoded;
                } elseif (isset($decoded[0]) && is_array($decoded[0])) {
                    $nfts = $decoded;
                }

                foreach ($nfts as $nft) {
                    $item = normalize_nft_row($nft);
                    if ($item) {
                        $allNfts[] = $item;
                    }
                }
            }

            continue;
        }

        if (in_array('token_id', $columns, true) && in_array('collection', $columns, true)) {
            $traitColumn = null;
            foreach (['traits', 'traits_json', 'attributes', 'attributes_json', 'metadata_json'] as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    $traitColumn = $candidate;
                    break;
                }
            }

            $selectFields = "token_id, collection";
            if (in_array('nft_name', $columns, true)) {
                $selectFields .= ", nft_name";
            }
            if (in_array('name', $columns, true)) {
                $selectFields .= ", name";
            }
            if (in_array('image_url', $columns, true)) {
                $selectFields .= ", image_url";
            }
            if (in_array('image', $columns, true)) {
                $selectFields .= ", image";
            }
            if ($traitColumn) {
                $selectFields .= ", {$traitColumn} AS raw_traits";
            }

            $whereParts = [
                "{$userColumn} = ?",
                "LOWER(collection) = 'genesis'"
            ];
            $params = [$user_id];

            if (in_array('is_verified', $columns, true)) {
                $whereParts[] = 'is_verified = 1';
            }

            $whereSql = implode(' AND ', $whereParts);
            $stmt = $pdo->prepare("SELECT {$selectFields} FROM {$table} WHERE {$whereSql}");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $rawTraits = [];
                if (!empty($row['raw_traits'])) {
                    $decodedTraits = json_decode($row['raw_traits'], true);
                    if (is_array($decodedTraits)) {
                        $rawTraits = $decodedTraits;
                    }
                }

                $item = normalize_nft_row([
                    'token_id' => $row['token_id'] ?? '',
                    'collection' => $row['collection'] ?? '',
                    'nft_name' => $row['nft_name'] ?? $row['name'] ?? 'Unnamed NFT',
                    'image_url' => $row['image_url'] ?? $row['image'] ?? '',
                    'traits' => $rawTraits
                ]);

                if ($item) {
                    $allNfts[] = $item;
                }
            }
        }
    }

    return dedupe_nfts_by_token($allNfts);
}

/**
 * Find one currently verified Genesis NFT for this player by token_id.
 */
function find_verified_nft_by_token(PDO $pdo, $user_id, $token_id) {
    $verifiedNfts = load_verified_genesis_from_db($pdo, $user_id);

    foreach ($verifiedNfts as $nft) {
        if ((string)($nft['token_id'] ?? '') === (string)$token_id) {
            return $nft;
        }
    }

    return null;
}

/**
 * Check that the requested exact trait exists on the verified NFT.
 */
function trait_exists_on_nft($nft, $trait_type, $trait_value) {
    $traits = $nft['traits'] ?? [];
    $normalizedType = normalize_trait_type($trait_type);

    foreach ($traits as $trait) {
        $rowType = normalize_trait_type($trait['trait_type'] ?? '');
        $rowValue = (string)($trait['trait_value'] ?? '');

        if ($rowType === $normalizedType && $rowValue === (string)$trait_value) {
            return true;
        }
    }

    return false;
}

/**
 * Load one exact stored progression row.
 */
function get_existing_upgrade_row(PDO $pdo, $token_id, $collection, $trait_type, $trait_value) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND trait_type = ?
          AND trait_value = ?
        LIMIT 1
    ");
    $stmt->execute([$token_id, $collection, $trait_type, $trait_value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Return the next safe frontend-facing status for a row.
 */
function resolve_claimable_status($status, $upgradeEndsAt) {
    $normalizedStatus = strtolower(trim((string)$status));
    $endTs = !empty($upgradeEndsAt) ? strtotime($upgradeEndsAt) : false;

    if ($normalizedStatus === 'ready' || $normalizedStatus === 'ready_to_claim') {
        return 'ready_to_claim';
    }

    if ($normalizedStatus === 'upgrading' && $endTs && $endTs <= time()) {
        return 'ready_to_claim';
    }

    if ($normalizedStatus === 'upgrading') {
        return 'upgrading';
    }

    return 'idle';
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $user_id = resolve_user_id();
    if (!$user_id) {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $request = get_request_data();

    $token_id = trim((string)($request['token_id'] ?? ''));
    $collection = strtolower(trim((string)($request['collection'] ?? '')));
    $trait_type = normalize_trait_type($request['trait_type'] ?? '');
    $trait_value = trim((string)($request['trait_value'] ?? ''));

    if ($token_id === '' || $collection === '' || $trait_type === '' || $trait_value === '') {
        json_response([
            'success' => false,
            'error' => 'Missing required fields: token_id, collection, trait_type, trait_value'
        ], 400);
    }

    if ($collection !== 'genesis') {
        json_response([
            'success' => false,
            'error' => 'Only verified Genesis NFTs can be upgraded'
        ], 400);
    }

    $pdo = getDatabaseConnection();
    ensure_upgrade_table($pdo);

    $verifiedNft = find_verified_nft_by_token($pdo, $user_id, $token_id);
    if (!$verifiedNft) {
        json_response([
            'success' => false,
            'error' => 'This NFT is not currently verified to the current player'
        ], 403);
    }

    if (!trait_exists_on_nft($verifiedNft, $trait_type, $trait_value)) {
        json_response([
            'success' => false,
            'error' => 'The requested trait does not exist on this verified NFT'
        ], 400);
    }

    $existingRow = get_existing_upgrade_row($pdo, $token_id, 'genesis', $trait_type, $trait_value);
    if (!$existingRow) {
        json_response([
            'success' => false,
            'error' => 'No stored upgrade row exists yet for this trait'
        ], 404);
    }

    $currentLevel = max(1, (int)($existingRow['current_level'] ?? 1));
    $effectiveStatus = resolve_claimable_status(
        $existingRow['upgrade_status'] ?? 'idle',
        $existingRow['upgrade_ends_at'] ?? null
    );

    if ($effectiveStatus === 'upgrading') {
        json_response([
            'success' => false,
            'error' => 'This trait upgrade is still in progress',
            'data' => [
                'token_id' => $token_id,
                'collection' => 'genesis',
                'trait_type' => $trait_type,
                'trait_value' => $trait_value,
                'current_level' => $currentLevel,
                'upgrade_status' => 'upgrading',
                'upgrade_started_at' => $existingRow['upgrade_started_at'] ?? null,
                'upgrade_ends_at' => $existingRow['upgrade_ends_at'] ?? null
            ]
        ], 409);
    }

    if ($effectiveStatus !== 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This trait has no completed upgrade ready to claim',
            'data' => [
                'token_id' => $token_id,
                'collection' => 'genesis',
                'trait_type' => $trait_type,
                'trait_value' => $trait_value,
                'current_level' => $currentLevel,
                'upgrade_status' => 'idle'
            ]
        ], 409);
    }

    $newLevel = $currentLevel + 1;
    $claimedAt = gmdate('Y-m-d H:i:s');

    $pdo->beginTransaction();

$claimStmt = $pdo->prepare("
    UPDATE tbl_nft_trait_upgrades
    SET current_level = ?,
        upgrade_status = 'idle',
        upgrade_started_at = NULL,
        upgrade_ends_at = NULL,
        active_booster_item_id = NULL,
        active_booster_used_at = NULL,
        last_completed_at = ?,
        last_owner_user_id = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE upgrade_id = ?
");
$claimStmt->execute([
    $newLevel,
    $claimedAt,
    $user_id,
    $existingRow['upgrade_id']
]);

if ($claimStmt->rowCount() < 1) {
    throw new Exception('Claim update did not modify any upgrade row.');
}

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Trait upgrade claimed successfully',
'data' => [
    'upgrade_id' => $existingRow['upgrade_id'],
    'token_id' => $token_id,
    'collection' => 'genesis',
    'trait_type' => $trait_type,
    'trait_value' => $trait_value,
    'previous_level' => $currentLevel,
    'current_level' => $newLevel,
    'next_level' => $newLevel + 1,
    'upgrade_status' => 'idle',
    'upgrade_started_at' => null,
    'upgrade_ends_at' => null,
    'active_booster_item_id' => null,
    'active_booster_used_at' => null,
    'last_completed_at' => $claimedAt,
    'last_owner_user_id' => $user_id,
    'source' => 'claimed_existing_row'
]
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Complete Trait Upgrade ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to complete NFT trait upgrade',
        'details' => $e->getMessage()
    ], 500);
}