<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

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
$user_id = $data['user_id'] ?? '';
$amount = intval($data['amount'] ?? 0);
$reason = $data['reason'] ?? 'admin_adjustment';
$admin_id = $_SESSION['discord_id'];

if (!$user_id || !$action || !$amount) {
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
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Start transaction
$db->beginTransaction();

try {
    // Log the adjustment
    $logStmt = $db->prepare("
        INSERT INTO tbl_score_adjustments (
            user_id, 
            admin_id, 
            amount, 
            action, 
            reason, 
            timestamp
        ) VALUES (?, ?, ?, ?, ?, datetime('now'))
    ");
    $logStmt->execute([$user_id, $admin_id, $amount, $action, $reason]);

    // Update or insert score
    switch ($action) {
        case 'add':
            $stmt = $db->prepare("
                INSERT INTO tbl_user_scores (user_id, score, source) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $amount, $reason]);
            break;

        case 'remove':
            $stmt = $db->prepare("
                INSERT INTO tbl_user_scores (user_id, score, source) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, -$amount, $reason]);
            break;

        case 'set':
            // First, get current total
            $currentStmt = $db->prepare("
                SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?
            ");
            $currentStmt->execute([$user_id]);
            $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
            $currentTotal = intval($current['total'] ?? 0);
            
            // Calculate adjustment needed
            $adjustment = $amount - $currentTotal;
            
            // Insert adjustment
            if ($adjustment !== 0) {
                $stmt = $db->prepare("
                    INSERT INTO tbl_user_scores (user_id, score, source) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user_id, $adjustment, $reason]);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    // Get new total
    $totalStmt = $db->prepare("
        SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?
    ");
    $totalStmt->execute([$user_id]);
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC);

    $db->commit();

    echo json_encode([
        'success' => true,
        'new_total' => intval($total['total'] ?? 0),
        'action' => $action,
        'amount' => $amount
    ]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 