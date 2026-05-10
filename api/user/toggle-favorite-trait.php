<?php
// 🧬 Toggle Favorite Trait API
// Adds or removes one favorite trait type for the active user.
//
// Stability-first rules:
// - User-bound preference only (NOT NFT-bound)
// - Session-first auth in production
// - Localhost request user_id allowed
// - Safe toggle behavior
// - No impact on live upgrade rows

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf fallback

/**
 * Return JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Detect localhost environment.
 */
function is_localhost_env(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data safely.
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
 * Resolve active user using the same pattern as the live Lab APIs.
 */
function resolve_user_id(array $request): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($request['user_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        return $requestUserId;
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
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Get DB connection.
 */
function get_db(): PDO {
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

    $dbPathCandidates = [
        __DIR__ . '/../../db/narrrf_world.sqlite',
        '/var/www/html/db/narrrf_world.sqlite'
    ];

    foreach ($dbPathCandidates as $dbPath) {
        if (!$dbPath || !file_exists($dbPath)) {
            continue;
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }

    throw new Exception('Database not found');
}

/**
 * Normalize trait type for safe canonical storage.
 */
function normalize_trait_type(string $rawTraitType): string {
    $traitType = trim($rawTraitType);

    if ($traitType === '') {
        return '';
    }

    // Keep human-readable labels, but normalize whitespace.
    $traitType = preg_replace('/\s+/', ' ', $traitType);
    return trim((string)$traitType);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $request = get_request_data();
    $userId = resolve_user_id($request);

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not authenticated'
        ], 401);
    }

    $traitType = normalize_trait_type((string)($request['trait_type'] ?? ''));
    $requestedState = $request['is_favorite'] ?? null;
    $priorityOrder = isset($request['priority_order']) ? (int)$request['priority_order'] : 0;

    if ($traitType === '') {
        json_response([
            'success' => false,
            'error' => 'trait_type is required'
        ], 400);
    }

    $pdo = get_db();
    $pdo->beginTransaction();

    $existingStmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            trait_type,
            is_favorite,
            priority_order
        FROM tbl_user_favorite_traits
        WHERE user_id = ?
          AND trait_type = ?
        LIMIT 1
    ");
    $existingStmt->execute([$userId, $traitType]);
    $existingRow = $existingStmt->fetch();

    $newIsFavorite = 1;

    if ($requestedState !== null) {
        $newIsFavorite = (int)((bool)$requestedState);
    } else {
        $currentState = $existingRow ? (int)$existingRow['is_favorite'] : 0;
        $newIsFavorite = $currentState === 1 ? 0 : 1;
    }

        // BUG #726:
    // Only one favorite trait type may be active per user.
    // DEVS FOR DECADES:
    // The current favorite model is user-bound trait_type, not token_id + trait_value.
    // Before enabling a new favorite, disable all other favorite rows for this user so
    // bulk automation cannot choose between multiple favorite trait types for one mouse.
    if ($newIsFavorite === 1) {
        $clearOtherFavoritesStmt = $pdo->prepare("
            UPDATE tbl_user_favorite_traits
            SET
                is_favorite = 0,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
              AND trait_type <> ?
              AND is_favorite = 1
        ");
        $clearOtherFavoritesStmt->execute([$userId, $traitType]);
    }

    if ($existingRow) {
        $updateStmt = $pdo->prepare("
            UPDATE tbl_user_favorite_traits
            SET
                is_favorite = ?,
                priority_order = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([
            $newIsFavorite,
            $priorityOrder,
            (int)$existingRow['id']
        ]);
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO tbl_user_favorite_traits (
                user_id,
                trait_type,
                is_favorite,
                priority_order,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $insertStmt->execute([
            $userId,
            $traitType,
            $newIsFavorite,
            $priorityOrder
        ]);
    }

       $favoritesStmt = $pdo->prepare("
        SELECT
            trait_type,
            priority_order
        FROM tbl_user_favorite_traits
        WHERE user_id = ?
          AND is_favorite = 1
        ORDER BY priority_order DESC, created_at ASC
        LIMIT 1
    ");
    $favoritesStmt->execute([$userId]);
    $favoriteRows = $favoritesStmt->fetchAll();

    $pdo->commit();

    $favorites = array_map(static function(array $row): array {
        return [
            'trait_type' => (string)$row['trait_type'],
            'priority_order' => (int)$row['priority_order']
        ];
    }, $favoriteRows);

    json_response([
        'success' => true,
        'user_id' => $userId,
        'trait_type' => $traitType,
        'is_favorite' => $newIsFavorite === 1,
        'favorites' => $favorites,
        'count' => count($favorites)
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