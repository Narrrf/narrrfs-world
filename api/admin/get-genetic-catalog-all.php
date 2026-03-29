<?php
// 🧬 Get Full Genetic Catalog API (Admin)
// Returns ALL genetic catalog rows, including hidden / deactivated traits.

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

session_start();

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

function ensure_admin_access() {
    if (is_localhost_env()) {
        return true;
    }

    $isAdmin = $_SESSION['is_admin'] ?? false;
    $adminRole = strtolower(trim((string)($_SESSION['admin_role'] ?? '')));
    $adminDiscordId = trim((string)($_SESSION['admin_discord_id'] ?? ''));
    $adminAuthType = strtolower(trim((string)($_SESSION['admin_auth_type'] ?? '')));

    $allowedRoles = ['moderator', 'admin', 'owner', 'super_admin'];

    $hasAdminFlag = ($isAdmin === true || $isAdmin === 1 || $isAdmin === '1');
    $hasAllowedRole = in_array($adminRole, $allowedRoles, true);

    $hasSessionAdminAccess = $hasAdminFlag && $hasAllowedRole;

    $hasDiscordSessionAccess =
        $adminAuthType === 'discord' &&
        $adminDiscordId !== '' &&
        $hasAdminFlag &&
        $hasAllowedRole;

    if ($hasSessionAdminAccess || $hasDiscordSessionAccess) {
        return true;
    }

    json_response([
        'success' => false,
        'error' => 'Admin access required'
    ], 403);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    ensure_admin_access();

    $pdo = getDatabaseConnection();

    $stmt = $pdo->query("
        SELECT
            catalog_id,
            trait_type,
            trait_value,
            display_title,
            description,
            rarity_tier,
            rarity_count,
            base_price_dspoinc,
            is_active,
            is_visible,
            image_type,
            image_path,
            preview_path,
            created_at,
            updated_at
        FROM tbl_genetic_trait_catalog
        ORDER BY trait_type ASC, display_title ASC, catalog_id ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_items' => count($rows),
        'active_items' => count(array_filter($rows, fn($row) => (int)($row['is_active'] ?? 0) === 1 && (int)($row['is_visible'] ?? 0) === 1)),
        'hidden_items' => count(array_filter($rows, fn($row) => !((int)($row['is_active'] ?? 0) === 1 && (int)($row['is_visible'] ?? 0) === 1))),
        'outfit_items' => count(array_filter($rows, fn($row) => ($row['trait_type'] ?? '') === 'Outfit')),
        'accessories_items' => count(array_filter($rows, fn($row) => ($row['trait_type'] ?? '') === 'Accessories'))
    ];

    json_response([
        'success' => true,
        'data' => [
            'items' => $rows,
            'summary' => $summary
        ]
    ]);
} catch (Exception $e) {
    error_log('🧬 Get Full Genetic Catalog ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load full genetic catalog',
        'details' => $e->getMessage()
    ], 500);
}