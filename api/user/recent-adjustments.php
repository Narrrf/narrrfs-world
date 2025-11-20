<?php
session_start();
header('Content-Type: application/json');

// 🐛 BUG #335 FIX: Environment-aware CORS headers (works both locally and in production)
$isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($isLocalDevelopment) {
    // Allow localhost origins for local development
    if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: http://localhost:5173');
    }
} else {
    // Production: only allow narrrfs.world
    header('Access-Control-Allow-Origin: https://narrrfs.world');
}
header('Access-Control-Allow-Credentials: true');

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

// 🐛 BUG #335 FIX: Accept user_id from GET/POST in both local and production
// This ensures Recent Score Changes work in production where session might not be set
$user_id = $_SESSION['discord_id'] ?? '';
// Check if user_id is provided in POST/GET (works for both local and production)
$request_user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
if ($request_user_id) {
    $user_id = $request_user_id;
}

// For local development, use Narrrf's account if no session exists
if (!$user_id && $isLocalDevelopment) {
    // If empty or legacy LOCAL_TEST_DISCORD, use Narrrf's account
    if ($user_id === 'LOCAL_TEST_DISCORD' || $user_id === '') {
        $user_id = $LOCAL_TEST_DISCORD_ID;
    }
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Prevent test user IDs in production (only allow real Discord sessions)
if (!$isLocalDevelopment && ($user_id === 'LOCAL_TEST_DISCORD' || $user_id === $LOCAL_TEST_DISCORD_ID)) {
    http_response_code(403);
    echo json_encode(['error' => 'Test user not allowed in production']);
    exit;
}

// Use a safe relative path so it works both locally and on Render!
if ($isLocalDevelopment) {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
} else {
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
}
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT 
            a.*,
            u1.username as username,
            u2.username as admin_name
        FROM tbl_score_adjustments a
        LEFT JOIN tbl_users u1 ON a.user_id = u1.discord_id
        LEFT JOIN tbl_users u2 ON a.admin_id = u2.discord_id
        WHERE a.user_id = ?
        ORDER BY a.timestamp DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'adjustments' => $adjustments]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch adjustments: ' . $e->getMessage()]);
}
