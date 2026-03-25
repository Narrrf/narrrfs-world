<?php
// 🧪 Use Lab Booster API
// Applies a purchased Lab booster item to an active Genesis trait upgrade.
// Stable-first rules:
// - Only verified Genesis NFTs may use boosters
// - Booster applies to exact NFT + exact trait row
// - Booster only works on an actively upgrading row
// - Booster reduces REMAINING time, never total base duration
// - Booster consumes 1 inventory item
// - Booster usage is logged in tbl_item_usage_history
// - Localhost may use request user_id or Narrrf fallback
// - Production stays session-first with mismatch protection

date_default_timezone_set('UTC');

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

const LAB_GREEN_ELIXIR_ITEM_ID = 33;
const LAB_BLUE_ELIXIR_ITEM_ID = 34;
const LAB_RED_ELIXIR_ITEM_ID = 35;

const LAB_GREEN_ELIXIR_REDUCTION_PERCENT = 25;
const LAB_BLUE_ELIXIR_REDUCTION_PERCENT = 50;
const LAB_RED_ELIXIR_REDUCTION_PERCENT = 75;

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

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

function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧪 Use Lab Booster: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Use Lab Booster - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧪 Use Lab Booster: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

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

function find_verified_nft_by_token(PDO $pdo, $user_id, $token_id) {
    $verifiedNfts = load_verified_genesis_from_db($pdo, $user_id);

    foreach ($verifiedNfts as $nft) {
        if ((string)($nft['token_id'] ?? '') === (string)$token_id) {
            return $nft;
        }
    }

    return null;
}

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

function get_booster_config_by_item_id($item_id) {
    $configs = [
        LAB_GREEN_ELIXIR_ITEM_ID => [
            'item_name' => 'Green Elixir',
            'reduction_percent' => LAB_GREEN_ELIXIR_REDUCTION_PERCENT
        ],
        LAB_BLUE_ELIXIR_ITEM_ID => [
            'item_name' => 'Blue Elixir',
            'reduction_percent' => LAB_BLUE_ELIXIR_REDUCTION_PERCENT
        ],
        LAB_RED_ELIXIR_ITEM_ID => [
            'item_name' => 'Red Elixir',
            'reduction_percent' => LAB_RED_ELIXIR_REDUCTION_PERCENT
        ]
    ];

    return $configs[$item_id] ?? null;
}

function get_inventory_row(PDO $pdo, $user_id, $item_id) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_user_inventory
        WHERE user_id = ?
          AND item_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $item_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
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
    $trait_type = normalize_trait_type($request['trait_type'] ?? '');
    $trait_value = trim((string)($request['trait_value'] ?? ''));
    $item_id = (int)($request['item_id'] ?? 0);

    if ($token_id === '' || $trait_type === '' || $trait_value === '' || $item_id < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing required fields: token_id, trait_type, trait_value, item_id'
        ], 400);
    }

    $boosterConfig = get_booster_config_by_item_id($item_id);
    if (!$boosterConfig) {
        json_response([
            'success' => false,
            'error' => 'Invalid Lab booster item'
        ], 400);
    }

    $pdo = getDatabaseConnection();

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

    $upgradeRow = get_existing_upgrade_row($pdo, $token_id, 'genesis', $trait_type, $trait_value);
    if (!$upgradeRow) {
        json_response([
            'success' => false,
            'error' => 'No stored upgrade row exists yet for this trait'
        ], 404);
    }

    $upgradeStatus = strtolower(trim((string)($upgradeRow['upgrade_status'] ?? 'idle')));
    $upgradeEndsAt = $upgradeRow['upgrade_ends_at'] ?? null;
    $endTs = !empty($upgradeEndsAt) ? strtotime($upgradeEndsAt) : false;
    $nowTs = time();

    if ($upgradeStatus !== 'upgrading') {
        json_response([
            'success' => false,
            'error' => 'Booster can only be used on an actively upgrading trait'
        ], 409);
    }

    error_log('🧪 BOOSTER DEBUG upgrade_ends_at raw: ' . $upgradeEndsAt);
    error_log('🧪 BOOSTER DEBUG php_now_utc: ' . gmdate('Y-m-d H:i:s'));
    error_log('🧪 BOOSTER DEBUG php_now_local: ' . date('Y-m-d H:i:s'));
    error_log('🧪 BOOSTER DEBUG endTs: ' . ($endTs ?: 'false'));
    error_log('🧪 BOOSTER DEBUG nowTs: ' . $nowTs);

    if (!$endTs || $endTs <= $nowTs) {
        json_response([
            'success' => false,
            'error' => 'This upgrade is already ready to claim or has no valid active timer'
        ], 409);
    }

    $inventoryRow = get_inventory_row($pdo, $user_id, $item_id);
    if (!$inventoryRow || (int)($inventoryRow['quantity'] ?? 0) < 1) {
        json_response([
            'success' => false,
            'error' => 'You do not own this Lab booster item'
        ], 409);
    }

$remainingSeconds = max(1, $endTs - $nowTs);

/**
 * Fixed time reduction per booster
 * This replaces percentage-based reduction
 */
$reductionSeconds = match ($item_id) {
    33 => 6 * 3600,   // 🟢 Green → -6h
    34 => 18 * 3600,  // 🔵 Blue → -18h
    35 => 48 * 3600,  // 🔴 Red → -48h
    default => 0
};

$newRemainingSeconds = max(1, $remainingSeconds - $reductionSeconds);

    $newEndsAt = gmdate('Y-m-d H:i:s', $nowTs + $newRemainingSeconds);
    $newQuantity = max(0, (int)$inventoryRow['quantity'] - 1);
    $usageReason = sprintf(
        'lab_booster:%s:%s:%s',
        $token_id,
        $trait_type,
        $trait_value
    );

    $pdo->beginTransaction();

$updateUpgradeStmt = $pdo->prepare("
    UPDATE tbl_nft_trait_upgrades
    SET upgrade_ends_at = ?,
        active_booster_item_id = ?,
        active_booster_used_at = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP
    WHERE upgrade_id = ?
");
$updateUpgradeStmt->execute([
    $newEndsAt,
    $item_id,
    $upgradeRow['upgrade_id']
]);

    if ($updateUpgradeStmt->rowCount() < 1) {
        throw new Exception('Booster update did not modify the upgrade row.');
    }

    if ($newQuantity > 0) {
        $updateInventoryStmt = $pdo->prepare("
            UPDATE tbl_user_inventory
            SET quantity = ?,
                last_used_at = CURRENT_TIMESTAMP
            WHERE inventory_id = ?
        ");
        $updateInventoryStmt->execute([
            $newQuantity,
            $inventoryRow['inventory_id']
        ]);
    } else {
        $deleteInventoryStmt = $pdo->prepare("
            DELETE FROM tbl_user_inventory
            WHERE inventory_id = ?
        ");
        $deleteInventoryStmt->execute([
            $inventoryRow['inventory_id']
        ]);
    }

    $insertUsageStmt = $pdo->prepare("
        INSERT INTO tbl_item_usage_history (
            user_id,
            item_id,
            item_name,
            quantity,
            reason,
            status,
            approved_by,
            used_at,
            approved_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $insertUsageStmt->execute([
        $user_id,
        $item_id,
        $boosterConfig['item_name'],
        1,
        $usageReason,
        'approved',
        'lab_system'
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => $boosterConfig['item_name'] . ' applied successfully',
        'data' => [
            'upgrade_id' => $upgradeRow['upgrade_id'],
            'token_id' => $token_id,
            'trait_type' => $trait_type,
            'trait_value' => $trait_value,
            'item_id' => $item_id,
            'item_name' => $boosterConfig['item_name'],
            'reduction_percent' => $reductionPercent,
            'previous_ends_at' => $upgradeEndsAt,
            'new_ends_at' => $newEndsAt,
            'previous_remaining_seconds' => $remainingSeconds,
            'new_remaining_seconds' => $newRemainingSeconds,
            'remaining_inventory_quantity' => $newQuantity
        ]
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧪 Use Lab Booster ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to use Lab booster',
        'details' => $e->getMessage()
    ], 500);
}