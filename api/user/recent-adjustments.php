<?php
session_start();

// 🚨 CRITICAL FIX: Handle OPTIONS preflight requests FIRST (before any other headers)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight request - return allowed methods and headers
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
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
    http_response_code(200);
    exit;
}

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
// Also check JSON body for POST requests
$request_user_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try POST data first
    $request_user_id = $_POST['user_id'] ?? '';
    // If not in POST, try JSON body
    if (!$request_user_id) {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $request_user_id = $json_input['user_id'] ?? '';
    }
} else {
    // GET request
    $request_user_id = $_GET['user_id'] ?? '';
}

// Use request user_id if provided (takes priority over session)
if ($request_user_id) {
    $user_id = $request_user_id;
    error_log("📊 Recent adjustments: Using user_id from request: " . substr($user_id, 0, 10) . "...");
} else if ($user_id) {
    error_log("📊 Recent adjustments: Using user_id from session: " . substr($user_id, 0, 10) . "...");
}

// For local development, use Narrrf's account if no session exists
if (!$user_id && $isLocalDevelopment) {
    // If empty or legacy LOCAL_TEST_DISCORD, use Narrrf's account
    if ($user_id === 'LOCAL_TEST_DISCORD' || $user_id === '') {
        $user_id = $LOCAL_TEST_DISCORD_ID;
        error_log("📊 Recent adjustments: Using local test user ID");
    }
}

if (!$user_id) {
    error_log("❌ Recent adjustments: No user_id found (session: " . ($_SESSION['discord_id'] ?? 'none') . ", GET: " . ($_GET['user_id'] ?? 'none') . ", POST: " . ($_POST['user_id'] ?? 'none') . ")");
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in', 'debug' => [
        'has_session' => isset($_SESSION['discord_id']),
        'session_id' => $_SESSION['discord_id'] ?? null,
        'get_user_id' => $_GET['user_id'] ?? null,
        'post_user_id' => $_POST['user_id'] ?? null,
        'request_method' => $_SERVER['REQUEST_METHOD']
    ]]);
    exit;
}

// Prevent test user IDs in production (only allow real Discord sessions)
if (!$isLocalDevelopment && ($user_id === 'LOCAL_TEST_DISCORD' || $user_id === $LOCAL_TEST_DISCORD_ID)) {
    error_log("❌ Recent adjustments: Test user blocked in production: " . $user_id);
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
