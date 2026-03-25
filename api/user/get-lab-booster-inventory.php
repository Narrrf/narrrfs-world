<?php
// 🧪 Get Lab Booster Inventory API
// Returns only the Lab booster inventory rows needed by lab.html.
// Stable-first rules:
// - Session-first in production, request/local override on localhost only
// - Only booster item IDs 33, 34, 35 are returned
// - Response is intentionally minimal: item_id, quantity, and display metadata

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

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

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running in a localhost-style development environment.
 */
function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON/POST/GET.
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
 * Resolve the active user using the same production/local pattern as the Lab upgrade APIs.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧪 Booster Inventory: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Booster Inventory - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log('🧪 Booster Inventory: Using local test user (Narrrf) for localhost');
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

/**
 * Return canonical booster metadata keyed by item ID.
 */
function get_lab_booster_configs() {
    return [
        LAB_GREEN_ELIXIR_ITEM_ID => [
            'item_name' => 'Green Elixir',
            'effect' => '-6 hours remaining time',
            'reduction_hours' => 6
        ],
        LAB_BLUE_ELIXIR_ITEM_ID => [
            'item_name' => 'Blue Elixir',
            'effect' => '-18 hours remaining time',
            'reduction_hours' => 18
        ],
        LAB_RED_ELIXIR_ITEM_ID => [
            'item_name' => 'Red Elixir',
            'effect' => '-48 hours remaining time',
            'reduction_hours' => 48
        ]
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    $pdo = getDatabaseConnection();
    $boosterConfigs = get_lab_booster_configs();
    $boosterItemIds = array_keys($boosterConfigs);

    $placeholders = implode(',', array_fill(0, count($boosterItemIds), '?'));

    $stmt = $pdo->prepare("\n        SELECT
            inventory_id,
            user_id,
            item_id,
            quantity,
            acquired_at,
            last_used_at
        FROM tbl_user_inventory
        WHERE user_id = ?
          AND item_id IN ($placeholders)
        ORDER BY item_id ASC
    ");

    $stmt->execute(array_merge([$user_id], $boosterItemIds));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $item_id = (int)($row['item_id'] ?? 0);
        $config = $boosterConfigs[$item_id] ?? null;
        if (!$config) {
            continue;
        }

        $items[] = [
            'inventory_id' => isset($row['inventory_id']) ? (int)$row['inventory_id'] : null,
            'user_id' => (string)($row['user_id'] ?? ''),
            'item_id' => $item_id,
            'item_name' => $config['item_name'],
            'effect' => $config['effect'],
            'reduction_percent' => (int)$config['reduction_percent'],
            'quantity' => max(0, (int)($row['quantity'] ?? 0)),
            'acquired_at' => $row['acquired_at'] ?? null,
            'last_used_at' => $row['last_used_at'] ?? null
        ];
    }

    json_response([
        'success' => true,
        'data' => [
            'user_id' => $user_id,
            'items' => $items
        ]
    ]);
} catch (Exception $e) {
    error_log('🧪 Booster Inventory ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load Lab booster inventory',
        'details' => $e->getMessage()
    ], 500);
}
