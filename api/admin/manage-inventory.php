<?php
session_start();
header('Content-Type: application/json');

// Verify admin/mod status
require_once(__DIR__ . '/../auth/verify-admin.php');
if (!isAdminOrMod()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized: Admin or Mod role required']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$userId = $data['user_id'] ?? '';
$itemId = $data['item_id'] ?? '';
$reason = $data['reason'] ?? 'admin_adjustment';
$adminId = $_SESSION['discord_id'];

if (!$userId || !$action || !$itemId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Connect to database
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Start transaction
$db->beginTransaction();

try {
    switch ($action) {
        case 'add':
            // Check if item exists
            $checkStmt = $db->prepare("SELECT id FROM tbl_store_items WHERE id = ?");
            $checkStmt->execute([$itemId]);
            if (!$checkStmt->fetch()) {
                throw new Exception('Item does not exist');
            }

            // Add item to inventory
            $stmt = $db->prepare("
                INSERT INTO tbl_user_inventory (user_id, item_id, source) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $itemId, $reason]);
            break;

        case 'remove':
            // Remove item from inventory
            $stmt = $db->prepare("
                DELETE FROM tbl_user_inventory 
                WHERE user_id = ? AND item_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$userId, $itemId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Item not found in user inventory');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    // Log the adjustment
    $logStmt = $db->prepare("
        INSERT INTO tbl_inventory_adjustments (
            user_id, 
            admin_id, 
            item_id,
            action, 
            reason, 
            timestamp
        ) VALUES (?, ?, ?, ?, ?, datetime('now'))
    ");
    $logStmt->execute([$userId, $adminId, $itemId, $action, $reason]);

    $db->commit();
    echo json_encode(['success' => true, 'action' => $action]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 