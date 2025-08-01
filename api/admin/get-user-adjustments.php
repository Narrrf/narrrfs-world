<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$db_path = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$user_id = $input['user_id'] ?? '';

if ($action !== 'get_user_adjustments' || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Get user adjustments with admin usernames
    $stmt = $db->prepare("SELECT 
                            a.*,
                            u.username as admin_username
                          FROM tbl_score_adjustments a
                          LEFT JOIN tbl_users u ON a.admin_id = u.discord_id
                          WHERE a.user_id = ?
                          ORDER BY a.timestamp DESC
                          LIMIT 50");
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $adjustments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $adjustments[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'admin_id' => $row['admin_id'],
            'admin_username' => $row['admin_username'],
            'amount' => $row['amount'],
            'action' => $row['action'],
            'reason' => $row['reason'],
            'quest_id' => $row['quest_id'],
            'timestamp' => $row['timestamp']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'adjustments' => $adjustments,
        'total' => count($adjustments)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get user adjustments: ' . $e->getMessage()]);
}
?>