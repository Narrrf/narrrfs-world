<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$userId = $_GET['user_id'] ?? '';
if (!$userId && isset($_SESSION['discord_id'])) {
    $userId = $_SESSION['discord_id'];
}

if (empty($userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'User ID required and no active session found.'
    ]);
    exit;
}

$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
$dbPath = $isProduction
    ? '/var/www/html/db/narrrf_world.sqlite'
    : __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

try {
    $userStmt = $db->prepare("SELECT discord_id, username, avatar_url, created_at FROM tbl_users WHERE discord_id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }

    $balanceStmt = $db->prepare("SELECT COALESCE(SUM(score), 0) AS total FROM tbl_user_scores WHERE user_id = ?");
    $balanceStmt->execute([$userId]);
    $balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $balance = (int)($balanceRow['total'] ?? 0);

    $rolesStmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
    $rolesStmt->execute([$userId]);
    $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $traitsStmt = $db->prepare("SELECT trait FROM tbl_user_traits WHERE user_id = ?");
    $traitsStmt->execute([$userId]);
    $traits = $traitsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    echo json_encode([
        'success' => true,
        'user' => [
            'discord_id' => $user['discord_id'],
            'username' => $user['username'],
            'avatar_url' => $user['avatar_url'] ?? '',
            'member_since' => $user['created_at'] ?? '',
            'balance' => $balance,
            'roles' => $roles,
            'traits' => $traits
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load user details'
    ]);
}