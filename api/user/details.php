<?php
header('Content-Type: application/json');

// Get user ID
$userId = $_GET['user_id'] ?? '';
if (empty($userId)) {
    echo json_encode(['error' => 'User ID required']);
    exit;
}

// Connect to database
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Get user info
    $userStmt = $db->prepare("SELECT * FROM tbl_users WHERE discord_id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Get balance
    $balanceStmt = $db->prepare("SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?");
    $balanceStmt->execute([$userId]);
    $balance = $balanceStmt->fetch(PDO::FETCH_ASSOC);

    // Get inventory
    $inventoryStmt = $db->prepare("
        SELECT i.*, s.name, s.description, s.price, s.image_url 
        FROM tbl_user_inventory i 
        JOIN tbl_store_items s ON i.item_id = s.id 
        WHERE i.user_id = ?
    ");
    $inventoryStmt->execute([$userId]);
    $inventory = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get roles
    $rolesStmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
    $rolesStmt->execute([$userId]);
    $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'user' => [
            'discord_id' => $user['discord_id'],
            'username' => $user['username'],
            'balance' => intval($balance['total'] ?? 0),
            'inventory' => $inventory,
            'roles' => $roles
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to get user details']);
} 