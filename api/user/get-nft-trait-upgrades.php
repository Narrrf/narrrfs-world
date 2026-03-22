<?php
// 🧬 Get NFT Trait Upgrades API
// Returns trait upgrade rows for the CURRENT verified Genesis NFTs of the current player.
// Stable-first design:
// 1. Resolve current user safely (session in production, local override on localhost)
// 2. Load current verified Genesis NFTs
// 3. Ensure upgrade table exists
// 4. Return one resolved upgrade row per verified Genesis trait
// 5. If no stored row exists yet, return level-1 idle fallback rows

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Optional local secret config for consistency with other endpoints
$localDiscordSecretPath = __DIR__ . '/../config/discord-secret.php';
if (file_exists($localDiscordSecretPath)) {
    include $localDiscordSecretPath;
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true if current environment is localhost-style development.
 */
function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request body safely once.
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
 * Resolve current user ID with the same local/prod pattern used in staking endpoints.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Trait Upgrades: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Trait Upgrades - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Trait Upgrades: Using local test user (Narrrf) for localhost");
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
 * Normalize a trait list into [{trait_type, trait_value}] with duplicates removed.
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
 * Normalize one NFT row into the lab-safe shape.
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
 * Deduplicate NFTs by token_id.
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
 * Create the upgrade table if it does not exist yet.
 * This keeps the read endpoint stable even before write endpoints are added.
 */
function ensure_upgrade_table(PDO $pdo) {
    $tableCreated = false;

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

        $tableCreated = true;
        error_log("🧬 Trait Upgrades: Created tbl_nft_trait_upgrades");
    }

    return $tableCreated;
}

/**
 * Try to load current verified Genesis NFTs through the existing admin traits endpoint.
 * This avoids guessing your verification-table schema.
 */
function load_verified_genesis_from_admin_endpoint($user_id) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $url = $scheme . '://' . $host . $basePath . '/get-holder-verification-traits.php';

    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $raw = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $raw = @file_get_contents($url);
    }

    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return [];
    }

    $records = [];
    if (isset($decoded['records']) && is_array($decoded['records'])) {
        $records = $decoded['records'];
    } elseif (isset($decoded['users']) && is_array($decoded['users'])) {
        $records = $decoded['users'];
    }

    foreach ($records as $record) {
        $recordUserId = (string)($record['user_id'] ?? $record['discord_id'] ?? '');
        if ($recordUserId !== (string)$user_id) {
            continue;
        }

        $genesisNfts = [];
        if (isset($record['genesis_nfts']) && is_array($record['genesis_nfts'])) {
            $genesisNfts = $record['genesis_nfts'];
        } elseif (isset($record['nfts']) && is_array($record['nfts'])) {
            $genesisNfts = array_values(array_filter($record['nfts'], function ($nft) {
                return strtolower((string)($nft['collection'] ?? '')) === 'genesis';
            }));
        }

        $normalized = [];
        foreach ($genesisNfts as $nft) {
            $item = normalize_nft_row($nft);
            if ($item) {
                $normalized[] = $item;
            }
        }

        return dedupe_nfts_by_token($normalized);
    }

    return [];
}

/**
 * Fallback loader with schema inspection.
 * This is intentionally tolerant because the exact verification schema was not uploaded here.
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
                } elseif (array_is_list($decoded)) {
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
 * Load current verified Genesis NFTs using the safest available path.
 */
function load_current_verified_genesis_nfts(PDO $pdo, $user_id) {
    $fromAdmin = load_verified_genesis_from_admin_endpoint($user_id);
    if (!empty($fromAdmin)) {
        return [
            'source' => 'admin_traits_endpoint',
            'nfts' => $fromAdmin
        ];
    }

    $fromDb = load_verified_genesis_from_db($pdo, $user_id);
    if (!empty($fromDb)) {
        return [
            'source' => 'database_fallback',
            'nfts' => $fromDb
        ];
    }

    return [
        'source' => 'none',
        'nfts' => []
    ];
}

/**
 * Load stored upgrade rows for a token set.
 */
function load_upgrade_rows(PDO $pdo, $tokenIds) {
    if (empty($tokenIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($tokenIds), '?'));
    $sql = "
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
            created_at,
            updated_at
        FROM tbl_nft_trait_upgrades
        WHERE collection = 'genesis'
          AND token_id IN ({$placeholders})
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($tokenIds);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

try {
    $user_id = resolve_user_id();

    if (!$user_id) {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $pdo = getDatabaseConnection();

    $tableCreated = ensure_upgrade_table($pdo);

    /**
     * Auto-flip overdue upgrades into claimable state.
     * This keeps Lab state correct even if a background monitor
     * or notification worker missed the status transition.
     */
    $normalizeStmt = $pdo->prepare("
        UPDATE tbl_nft_trait_upgrades
        SET upgrade_status = 'ready_to_claim',
            updated_at = CURRENT_TIMESTAMP
        WHERE upgrade_status = 'upgrading'
          AND upgrade_ends_at IS NOT NULL
          AND upgrade_ends_at <= CURRENT_TIMESTAMP
    ");
    $normalizeStmt->execute();

    $verifiedResult = load_current_verified_genesis_nfts($pdo, $user_id);
    $verifiedGenesisNfts = $verifiedResult['nfts'];
    $verifiedSource = $verifiedResult['source'];

    $request = get_request_data();
    $requestedTokenId = trim((string)($request['token_id'] ?? ''));

    if ($requestedTokenId !== '') {
        $verifiedGenesisNfts = array_values(array_filter($verifiedGenesisNfts, function ($nft) use ($requestedTokenId) {
            return (string)($nft['token_id'] ?? '') === $requestedTokenId;
        }));
    }

    $tokenIds = array_values(array_map(function ($nft) {
        return $nft['token_id'];
    }, $verifiedGenesisNfts));

    $storedUpgradeRows = load_upgrade_rows($pdo, $tokenIds);

    $storedMap = [];
    foreach ($storedUpgradeRows as $row) {
        $key = implode('::', [
            (string)($row['token_id'] ?? ''),
            normalize_trait_type($row['trait_type'] ?? ''),
            (string)($row['trait_value'] ?? '')
        ]);
        $storedMap[$key] = $row;
    }

    $resolvedRows = [];
    foreach ($verifiedGenesisNfts as $nft) {
        $traits = $nft['traits'] ?? [];
        foreach ($traits as $trait) {
            $key = implode('::', [
                (string)$nft['token_id'],
                normalize_trait_type($trait['trait_type'] ?? ''),
                (string)($trait['trait_value'] ?? '')
            ]);

            if (isset($storedMap[$key])) {
                $row = $storedMap[$key];
                $resolvedRows[] = [
                    'upgrade_id' => $row['upgrade_id'] ?? null,
                    'token_id' => $row['token_id'],
                    'collection' => 'genesis',
                    'trait_type' => normalize_trait_type($row['trait_type'] ?? ''),
                    'trait_value' => (string)($row['trait_value'] ?? ''),
                    'current_level' => max(1, (int)($row['current_level'] ?? 1)),
                    'upgrade_status' => $row['upgrade_status'] ?? 'idle',
                    'upgrade_started_at' => $row['upgrade_started_at'] ?? null,
                    'upgrade_ends_at' => $row['upgrade_ends_at'] ?? null,
                    'last_completed_at' => $row['last_completed_at'] ?? null,
                    'last_owner_user_id' => $row['last_owner_user_id'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                    'source' => 'stored'
                ];
            } else {
                $resolvedRows[] = [
                    'upgrade_id' => null,
                    'token_id' => $nft['token_id'],
                    'collection' => 'genesis',
                    'trait_type' => normalize_trait_type($trait['trait_type'] ?? ''),
                    'trait_value' => (string)($trait['trait_value'] ?? ''),
                    'current_level' => 1,
                    'upgrade_status' => 'idle',
                    'upgrade_started_at' => null,
                    'upgrade_ends_at' => null,
                    'last_completed_at' => null,
                    'last_owner_user_id' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'source' => 'fallback'
                ];
            }
        }
    }

    $activeCount = 0;
    $readyCount = 0;
    $nowTs = time();

    foreach ($resolvedRows as $row) {
        $status = strtolower((string)($row['upgrade_status'] ?? 'idle'));
        $endTs = !empty($row['upgrade_ends_at']) ? strtotime($row['upgrade_ends_at']) : false;

        if ($status === 'upgrading' && $endTs && $endTs <= $nowTs) {
            $readyCount++;
        } elseif ($status === 'ready' || $status === 'ready_to_claim') {
            $readyCount++;
        } elseif ($status === 'upgrading') {
            $activeCount++;
        }
    }

    json_response([
        'success' => true,
        'data' => [
            'user_id' => $user_id,
            'verified_source' => $verifiedSource,
            'upgrade_table' => 'tbl_nft_trait_upgrades',
            'table_created_now' => $tableCreated,
            'verified_genesis_nfts' => $verifiedGenesisNfts,
            'upgrades' => $resolvedRows,
            'summary' => [
                'verified_nft_count' => count($verifiedGenesisNfts),
                'upgrade_row_count' => count($resolvedRows),
                'active_count' => $activeCount,
                'ready_count' => $readyCount
            ]
        ]
    ]);
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Failed to load NFT trait upgrades',
        'details' => $e->getMessage()
    ], 500);
}