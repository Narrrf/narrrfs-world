<?php
// 🧬 Instant Finish NFT Trait Upgrade API
// Pays DSPOINC to finish an active Genesis trait upgrade immediately.
// Stability-first rules:
// - Only verified Genesis NFTs may use instant finish
// - Exact trait value must exist on the NFT
// - Upgrade belongs to NFT token + exact trait value
// - Backend computes final price (frontend preview is only advisory)
// - Instant finish moves the row to ready_to_claim, it does NOT auto-claim
// - DSPOINC deduction uses the existing tbl_user_scores balance model
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
const INSTANT_FINISH_BASE_COST = 10000;

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
        error_log("🧬 Instant Finish: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Instant Finish - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Instant Finish: Using local test user (Narrrf) for localhost");
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
 * Ensure the progression table exists before logic runs.
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

        error_log("🧬 Instant Finish: Created tbl_nft_trait_upgrades");
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
 * Resolve current runtime status in a frontend-safe way.
 */
function resolve_runtime_status($status, $upgradeEndsAt) {
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

/**
 * Calculate the backend instant-finish cost.
 * Mirrors the frontend preview formula intentionally:
 * base_cost * 2.35^(level-1) * max(1, remaining_hours / 24)
 */
function calculate_instant_finish_cost($current_level, $remaining_seconds) {
    $level = max(1, (int)$current_level);
    $remainingHours = max(0, (float)$remaining_seconds / 3600);
    $levelMultiplier = pow(2.35, max(0, $level - 1));
    $timeMultiplier = max(1, $remainingHours / 24);

    return (int)ceil(INSTANT_FINISH_BASE_COST * $levelMultiplier * $timeMultiplier);
}

/**
 * Get total and available DSPOINC exactly like staking endpoints do:
 * available = SUM(tbl_user_scores.score) - SUM(active stakes)
 */
function get_user_balance_snapshot(PDO $pdo, $user_id) {
    $balanceStmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0) AS total_balance
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $balanceStmt->execute([$user_id]);
    $balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $totalBalance = (int)($balanceRow['total_balance'] ?? 0);

    $frozenBalance = 0;

    $stakesExistsStmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = 'tbl_dspoinc_stakes'");
    $stakesExistsStmt->execute();
    $stakesExists = $stakesExistsStmt->fetch(PDO::FETCH_ASSOC);

    if ($stakesExists) {
        $frozenStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS frozen_balance
            FROM tbl_dspoinc_stakes
            WHERE user_id = ? AND status = 'active'
        ");
        $frozenStmt->execute([$user_id]);
        $frozenRow = $frozenStmt->fetch(PDO::FETCH_ASSOC);
        $frozenBalance = (int)($frozenRow['frozen_balance'] ?? 0);
    }

    return [
        'total_balance' => $totalBalance,
        'frozen_balance' => $frozenBalance,
        'available_balance' => max(0, $totalBalance - $frozenBalance)
    ];
}

/**
 * Deduct DSPOINC by inserting a negative entry into tbl_user_scores.
 * This matches the existing balance model based on SUM(score).
 */
function deduct_user_dspoinc(PDO $pdo, $user_id, $amount) {
    $amount = (int)$amount;
    if ($amount <= 0) {
        throw new Exception('Invalid deduction amount');
    }

    $insertScoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (user_id, game, score, source)
        VALUES (?, ?, ?, ?)
    ");
    $insertScoreStmt->execute([
        $user_id,
        'lab_trait_upgrade',
        -$amount,
        'instant_finish_nft_trait_upgrade'
    ]);
}

/**
 * Optional audit entry in tbl_score_adjustments when the table exists.
 * Uses the player as both user_id and admin_id for a self-initiated spend log.
 *
 * TODO: Replace this with a dedicated lab economy ledger table when the economy
 * audit layer is formally designed for long-term ecosystem reporting.
 */
function insert_optional_score_adjustment_audit(PDO $pdo, $user_id, $amount, $reason) {
    $tableExistsStmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = 'tbl_score_adjustments'");
    $tableExistsStmt->execute();
    $exists = $tableExistsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        return;
    }

    $userExistsStmt = $pdo->prepare("SELECT discord_id FROM tbl_users WHERE discord_id = ? LIMIT 1");
    $userExistsStmt->execute([$user_id]);
    $userExists = $userExistsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userExists) {
        return;
    }

    $auditStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
        VALUES (?, ?, ?, 'remove', ?, CURRENT_TIMESTAMP)
    ");
    $auditStmt->execute([
        $user_id,
        $user_id,
        -abs((int)$amount),
        $reason
    ]);
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
    $effectiveStatus = resolve_runtime_status(
        $existingRow['upgrade_status'] ?? 'idle',
        $existingRow['upgrade_ends_at'] ?? null
    );

    if ($effectiveStatus === 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This trait is already ready to claim',
            'data' => [
                'upgrade_id' => $existingRow['upgrade_id'] ?? null,
                'token_id' => $token_id,
                'collection' => 'genesis',
                'trait_type' => $trait_type,
                'trait_value' => $trait_value,
                'current_level' => $currentLevel,
                'upgrade_status' => 'ready_to_claim',
                'upgrade_started_at' => $existingRow['upgrade_started_at'] ?? null,
                'upgrade_ends_at' => $existingRow['upgrade_ends_at'] ?? null
            ]
        ], 409);
    }

    if ($effectiveStatus !== 'upgrading') {
        json_response([
            'success' => false,
            'error' => 'This trait is not actively upgrading',
            'data' => [
                'upgrade_id' => $existingRow['upgrade_id'] ?? null,
                'token_id' => $token_id,
                'collection' => 'genesis',
                'trait_type' => $trait_type,
                'trait_value' => $trait_value,
                'current_level' => $currentLevel,
                'upgrade_status' => 'idle'
            ]
        ], 409);
    }

    $endTs = !empty($existingRow['upgrade_ends_at']) ? strtotime($existingRow['upgrade_ends_at']) : false;
    $remainingSeconds = $endTs ? max(0, $endTs - time()) : 0;

    if ($remainingSeconds <= 0) {
        json_response([
            'success' => false,
            'error' => 'This upgrade is already complete and ready to claim'
        ], 409);
    }

    $finalCost = calculate_instant_finish_cost($currentLevel, $remainingSeconds);
    $balance = get_user_balance_snapshot($pdo, $user_id);

    if ((int)$balance['available_balance'] < $finalCost) {
        json_response([
            'success' => false,
            'error' => 'Not enough available DSPOINC for instant finish',
            'data' => [
                'required_cost' => $finalCost,
                'available_balance' => (int)$balance['available_balance'],
                'total_balance' => (int)$balance['total_balance'],
                'frozen_balance' => (int)$balance['frozen_balance']
            ]
        ], 409);
    }

    $finishedAt = gmdate('Y-m-d H:i:s');
    $auditReason = sprintf(
        'Lab instant finish: %s → %s on Genesis token %s (remaining_seconds: %d, current_level: %d)',
        $trait_type,
        $trait_value,
        $token_id,
        $remainingSeconds,
        $currentLevel
    );

$pdo->beginTransaction();

// Re-check current upgrade row inside transaction to reduce double-finish risk.
$lockedRow = get_existing_upgrade_row($pdo, $token_id, 'genesis', $trait_type, $trait_value);
if (!$lockedRow) {
    $pdo->rollBack();
    json_response([
        'success' => false,
        'error' => 'Upgrade row no longer exists'
    ], 404);
}

$lockedStatus = resolve_runtime_status(
    $lockedRow['upgrade_status'] ?? 'idle',
    $lockedRow['upgrade_ends_at'] ?? null
);

if ($lockedStatus !== 'upgrading') {
    $pdo->rollBack();
    json_response([
        'success' => false,
        'error' => 'This trait is no longer actively upgrading'
    ], 409);
}

// Re-check available balance inside transaction to reduce double-spend risk.
$lockedBalance = get_user_balance_snapshot($pdo, $user_id);

if ((int)$lockedBalance['available_balance'] < $finalCost) {
    $pdo->rollBack();
    json_response([
        'success' => false,
        'error' => 'Not enough available DSPOINC for instant finish',
        'data' => [
            'required_cost' => $finalCost,
            'available_balance' => (int)$lockedBalance['available_balance'],
            'total_balance' => (int)$lockedBalance['total_balance'],
            'frozen_balance' => (int)$lockedBalance['frozen_balance']
        ]
    ], 409);
}

deduct_user_dspoinc($pdo, $user_id, $finalCost);
insert_optional_score_adjustment_audit($pdo, $user_id, $finalCost, $auditReason);

$finishStmt = $pdo->prepare("
    UPDATE tbl_nft_trait_upgrades
    SET upgrade_status = 'ready_to_claim',
        upgrade_ends_at = ?,
        last_owner_user_id = ?,
        ready_claim_notified_at = NULL,
        updated_at = CURRENT_TIMESTAMP
    WHERE upgrade_id = ?
");
$finishStmt->execute([
    $finishedAt,
    $user_id,
    $existingRow['upgrade_id']
]);

    $updatedBalance = get_user_balance_snapshot($pdo, $user_id);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Instant finish completed successfully. Trait is now ready to claim.',
        'data' => [
            'upgrade_id' => $existingRow['upgrade_id'],
            'token_id' => $token_id,
            'collection' => 'genesis',
            'trait_type' => $trait_type,
            'trait_value' => $trait_value,
            'current_level' => $currentLevel,
            'next_level' => $currentLevel + 1,
            'upgrade_status' => 'ready_to_claim',
            'upgrade_started_at' => $existingRow['upgrade_started_at'] ?? null,
            'upgrade_ends_at' => $finishedAt,
            'last_completed_at' => $existingRow['last_completed_at'] ?? null,
            'instant_finish_cost' => $finalCost,
            'remaining_seconds_before_finish' => $remainingSeconds,
            'balance' => [
                'total_balance' => (int)$updatedBalance['total_balance'],
                'available_balance' => (int)$updatedBalance['available_balance'],
                'frozen_balance' => (int)$updatedBalance['frozen_balance']
            ],
            'source' => 'instant_finish_existing_row'
        ]
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Failed to instant-finish NFT trait upgrade',
        'details' => $e->getMessage()
    ], 500);
}