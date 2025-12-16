<?php
// 🎁 Get Opened Chests API — Returns list of chests player has already opened
// CORS handled by .htaccess - no duplicate headers here

error_reporting(0);
ini_set('display_errors', 0);

// Set JSON response header (must be before any output)
header('Content-Type: application/json');

// Handle preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ], 500);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$discordId = isset($data['discord_id']) ? trim((string)$data['discord_id']) : '';

if ($discordId === '') {
    json_response([
        'success' => false,
        'error' => 'Missing discord_id'
    ], 400);
}

// Get all chest completions for this player (riddle_id starts with 'CHEST_')
try {
    $stmt = $pdo->prepare("
        SELECT 
            riddle_id,
            level_id,
            completed_at,
            total_reward
        FROM tbl_riddle_completions 
        WHERE discord_id = ? AND riddle_id LIKE 'CHEST_%'
        ORDER BY completed_at DESC
    ");
    $stmt->execute([$discordId]);
    $completions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extract chest IDs from riddle_id (format: CHEST_chest_001 -> chest_001)
    $openedChests = [];
    foreach ($completions as $completion) {
        $riddleId = $completion['riddle_id'];
        // Extract chest ID (remove 'CHEST_' prefix)
        if (strpos($riddleId, 'CHEST_') === 0) {
            $chestId = substr($riddleId, 6); // Remove 'CHEST_' prefix (6 characters)
            $openedChests[] = [
                'chest_id' => $chestId,
                'riddle_id' => $riddleId,
                'level_id' => $completion['level_id'],
                'completed_at' => $completion['completed_at'],
                'total_reward' => (int)$completion['total_reward']
            ];
        }
    }
    
    json_response([
        'success' => true,
        'data' => [
            'opened_chests' => $openedChests,
            'total_opened' => count($openedChests)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("❌ [GET OPENED CHESTS] Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Failed to fetch opened chests',
        'details' => $e->getMessage()
    ], 500);
}

