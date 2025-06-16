<?php
session_start();

// CORS and JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    exit;
}

// ✅ Ensure user is logged in
if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '❌ User not logged in.']);
    exit;
}

$discord_id = $_SESSION['discord_id'];

try {
    // ✅ Use relative path (for portability)
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Query roles
    $stmt = $pdo->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
    $stmt->execute([$discord_id]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['roles' => $roles ?: []]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '❌ DB Error', 'details' => $e->getMessage()]);
}
