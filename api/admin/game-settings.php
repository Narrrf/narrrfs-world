<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Handle JSON input for POST requests
$input_data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['CONTENT_TYPE']) && 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input_data = json_decode(file_get_contents('php://input'), true);
} else {
    $input_data = $_POST;
}

$action = $input_data['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_settings':
        getGameSettings($db);
        break;
    case 'update_settings':
        updateGameSettings($db);
        break;
    case 'check_wl_eligibility':
        checkWLEligibility($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getGameSettings($db) {
    try {
        // Get current game settings
        $stmt = $db->prepare("SELECT * FROM tbl_game_settings WHERE id = 1");
        $result = $stmt->execute();
        $settings = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$settings) {
            // Create default settings if none exist
            $stmt = $db->prepare("INSERT INTO tbl_game_settings 
                                    (id, tetris_wl_enabled, tetris_wl_threshold, tetris_wl_role_id, tetris_wl_bonus,
                                     snake_wl_enabled, snake_wl_threshold, snake_wl_role_id, snake_wl_bonus,
                                     created_at, updated_at) 
                                  VALUES (1, 0, 4000, '', 1000, 0, 4000, '', 1000, datetime('now'), datetime('now'))");
            $stmt->execute();
            
            $settings = [
                'tetris_wl_enabled' => 0,
                'tetris_wl_threshold' => 4000,
                'tetris_wl_role_id' => '',
                'tetris_wl_bonus' => 1000,
                'snake_wl_enabled' => 0,
                'snake_wl_threshold' => 4000,
                'snake_wl_role_id' => '',
                'snake_wl_bonus' => 1000
            ];
        }
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get game settings: ' . $e->getMessage()]);
    }
}

function updateGameSettings($db) {
    global $input_data;
    
    try {
        $tetris_wl_enabled = $input_data['tetris_wl_enabled'] ?? 0;
        $tetris_wl_threshold = $input_data['tetris_wl_threshold'] ?? 4000;
        $tetris_wl_role_id = $input_data['tetris_wl_role_id'] ?? '';
        $tetris_wl_bonus = $input_data['tetris_wl_bonus'] ?? 1000;
        $snake_wl_enabled = $input_data['snake_wl_enabled'] ?? 0;
        $snake_wl_threshold = $input_data['snake_wl_threshold'] ?? 4000;
        $snake_wl_role_id = $input_data['snake_wl_role_id'] ?? '';
        $snake_wl_bonus = $input_data['snake_wl_bonus'] ?? 1000;
        
        // Update or insert game settings
        $stmt = $db->prepare("INSERT OR REPLACE INTO tbl_game_settings 
                                (id, tetris_wl_enabled, tetris_wl_threshold, tetris_wl_role_id, tetris_wl_bonus,
                                 snake_wl_enabled, snake_wl_threshold, snake_wl_role_id, snake_wl_bonus,
                                 updated_at) 
                              VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
        $stmt->bindValue(1, $tetris_wl_enabled, SQLITE3_INTEGER);
        $stmt->bindValue(2, $tetris_wl_threshold, SQLITE3_INTEGER);
        $stmt->bindValue(3, $tetris_wl_role_id, SQLITE3_TEXT);
        $stmt->bindValue(4, $tetris_wl_bonus, SQLITE3_INTEGER);
        $stmt->bindValue(5, $snake_wl_enabled, SQLITE3_INTEGER);
        $stmt->bindValue(6, $snake_wl_threshold, SQLITE3_INTEGER);
        $stmt->bindValue(7, $snake_wl_role_id, SQLITE3_TEXT);
        $stmt->bindValue(8, $snake_wl_bonus, SQLITE3_INTEGER);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Game settings updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update game settings: ' . $e->getMessage()]);
    }
}

function checkWLEligibility($db) {
    global $input_data;
    
    try {
        $user_id = $input_data['user_id'] ?? '';
        $game = $input_data['game'] ?? '';
        $score = $input_data['score'] ?? 0;
        
        if (!$user_id || !$game || $score <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        // Get game settings
        $stmt = $db->prepare("SELECT * FROM tbl_game_settings WHERE id = 1");
        $result = $stmt->execute();
        $settings = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$settings) {
            echo json_encode([
                'success' => true,
                'wl_eligible' => false,
                'message' => 'No WL settings configured'
            ]);
            return;
        }
        
        // Check if WL is enabled for this game
        $wl_enabled = false;
        $wl_threshold = 0;
        $wl_role_id = '';
        $wl_bonus = 0;
        
        if ($game === 'tetris' && $settings['tetris_wl_enabled']) {
            $wl_enabled = true;
            $wl_threshold = $settings['tetris_wl_threshold'];
            $wl_role_id = $settings['tetris_wl_role_id'];
            $wl_bonus = $settings['tetris_wl_bonus'];
        } elseif ($game === 'snake' && $settings['snake_wl_enabled']) {
            $wl_enabled = true;
            $wl_threshold = $settings['snake_wl_threshold'];
            $wl_role_id = $settings['snake_wl_role_id'];
            $wl_bonus = $settings['snake_wl_bonus'];
        }
        
        if (!$wl_enabled || !$wl_role_id) {
            echo json_encode([
                'success' => true,
                'wl_eligible' => false,
                'message' => 'WL not enabled for this game'
            ]);
            return;
        }
        
        // Check if score meets threshold
        if ($score < $wl_threshold) {
            echo json_encode([
                'success' => true,
                'wl_eligible' => false,
                'message' => "Score $score is below WL threshold $wl_threshold"
            ]);
            return;
        }
        
        // Check if user already has WL role for this game
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_wl_role_grants 
                              WHERE user_id = ? AND game = ? AND role_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
        $stmt->bindValue(2, $game, SQLITE3_TEXT);
        $stmt->bindValue(3, $wl_role_id, SQLITE3_TEXT);
        $result = $stmt->execute();
        $existing = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($existing['count'] > 0) {
            echo json_encode([
                'success' => true,
                'wl_eligible' => false,
                'message' => 'User already has WL role for this game'
            ]);
            return;
        }
        
        // User is eligible for WL role
        echo json_encode([
            'success' => true,
            'wl_eligible' => true,
            'message' => "User eligible for WL role! Score: $score, Threshold: $wl_threshold",
            'role_id' => $wl_role_id,
            'game' => $game,
            'score' => $score,
            'bonus_points' => $wl_bonus
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to check WL eligibility: ' . $e->getMessage()]);
    }
}
?> 