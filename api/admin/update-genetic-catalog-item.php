<?php
// 🧬 Update Genetic Catalog Item API
// Admin-only catalog editing for Genetic Store Management.
//
// Stable-first rules:
// - Edits ONLY catalog presentation and sale controls
// - Does NOT mutate owned genetic items
// - Does NOT change trait_type / trait_value identity
// - Catalog visibility in shop is controlled through is_active + is_visible
// - Localhost testing may bypass strict auth like current local admin workflows

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
 * Very light admin gate for now.
 * Localhost is allowed for current development flow.
 * Production accepts both legacy admin session keys and Discord OAuth admin session keys.
 */
function ensure_admin_access() {
    if (is_localhost_env()) {
        return true;
    }

    $isAdmin = $_SESSION['is_admin'] ?? false;
    $adminRole = strtolower(trim((string)($_SESSION['admin_role'] ?? '')));

    $discordAdminAuthenticated = $_SESSION['admin_authenticated'] ?? false;
    $discordUserRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));

    $allowedRoles = ['moderator', 'admin', 'owner', 'super_admin'];

    $hasLegacyAdminAccess =
        ($isAdmin === true || $isAdmin === 1 || $isAdmin === '1') ||
        in_array($adminRole, $allowedRoles, true);

    $hasDiscordAdminAccess =
        ($discordAdminAuthenticated === true || $discordAdminAuthenticated === 1 || $discordAdminAuthenticated === '1') &&
        in_array($discordUserRole, $allowedRoles, true);

    if ($hasLegacyAdminAccess || $hasDiscordAdminAccess) {
        return true;
    }

    json_response([
        'success' => false,
        'error' => 'Admin access required'
    ], 403);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    ensure_admin_access();

    $request = get_request_data();

    $catalogId = (int)($request['catalog_id'] ?? 0);
    $displayTitle = trim((string)($request['display_title'] ?? ''));
    $price = isset($request['base_price_dspoinc']) ? (int)$request['base_price_dspoinc'] : null;
    $isActive = isset($request['is_active']) ? (int)$request['is_active'] : null;
    $isVisible = isset($request['is_visible']) ? (int)$request['is_visible'] : null;

    $imageType = strtolower(trim((string)($request['image_type'] ?? '')));
    $imagePath = trim((string)($request['image_path'] ?? ''));
    $previewPath = trim((string)($request['preview_path'] ?? ''));

    if ($catalogId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid catalog_id'
        ], 400);
    }

    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare("
        SELECT
            catalog_id,
            trait_type,
            trait_value,
            display_title,
            base_price_dspoinc,
            is_active,
            is_visible,
            image_type,
            image_path,
            preview_path
        FROM tbl_genetic_trait_catalog
        WHERE catalog_id = ?
        LIMIT 1
    ");
    $stmt->execute([$catalogId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        json_response([
            'success' => false,
            'error' => 'Genetic catalog item not found'
        ], 404);
    }

    $allowedImageTypes = ['', 'png', 'glb'];
    if (!in_array($imageType, $allowedImageTypes, true)) {
        json_response([
            'success' => false,
            'error' => 'Invalid image_type. Allowed: png, glb'
        ], 400);
    }

    $newDisplayTitle = $displayTitle !== '' ? $displayTitle : (string)$existing['display_title'];
    $newPrice = $price !== null ? max(0, $price) : (int)$existing['base_price_dspoinc'];
    $newIsActive = $isActive !== null ? ($isActive ? 1 : 0) : (int)$existing['is_active'];
    $newIsVisible = $isVisible !== null ? ($isVisible ? 1 : 0) : (int)$existing['is_visible'];

    $newImageType = $imageType !== '' ? $imageType : (string)($existing['image_type'] ?? 'png');
    $newImagePath = $imagePath !== '' ? $imagePath : (string)($existing['image_path'] ?? '');
    $newPreviewPath = $previewPath !== '' ? $previewPath : (string)($existing['preview_path'] ?? '');

    $update = $pdo->prepare("
        UPDATE tbl_genetic_trait_catalog
        SET
            display_title = ?,
            base_price_dspoinc = ?,
            is_active = ?,
            is_visible = ?,
            image_type = ?,
            image_path = ?,
            preview_path = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE catalog_id = ?
    ");
    $update->execute([
        $newDisplayTitle,
        $newPrice,
        $newIsActive,
        $newIsVisible,
        $newImageType,
        $newImagePath,
        $newPreviewPath,
        $catalogId
    ]);

    json_response([
        'success' => true,
        'message' => 'Genetic catalog item updated successfully',
        'data' => [
            'catalog_id' => $catalogId,
            'trait_type' => (string)$existing['trait_type'],
            'trait_value' => (string)$existing['trait_value'],
            'display_title' => $newDisplayTitle,
            'base_price_dspoinc' => $newPrice,
            'is_active' => $newIsActive,
            'is_visible' => $newIsVisible,
            'image_type' => $newImageType,
            'image_path' => $newImagePath,
            'preview_path' => $newPreviewPath
        ]
    ]);
} catch (Exception $e) {
    error_log('🧬 Update Genetic Catalog Item ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to update genetic catalog item',
        'details' => $e->getMessage()
    ], 500);
}