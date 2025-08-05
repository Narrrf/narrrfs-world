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
$input_data = json_decode(file_get_contents('php://input'), true);

$action = $input_data['action'] ?? '';

switch ($action) {
    case 'grant_wl_role':
        grantWLRole($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function grantWLRole($db) {
    global $input_data;
    
    try {
        $user_id = $input_data['user_id'] ?? '';
        $game = $input_data['game'] ?? '';
        $score = $input_data['score'] ?? 0;
        $role_id = $input_data['role_id'] ?? '';
        
        if (!$user_id || !$game || $score <= 0 || !$role_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        error_log("grantWLRole called: User $user_id, Game $game, Score $score, Role $role_id");
        
        // Check if user already has this WL role for this game
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_wl_role_grants 
                              WHERE user_id = ? AND game = ? AND role_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
        $stmt->bindValue(2, $game, SQLITE3_TEXT);
        $stmt->bindValue(3, $role_id, SQLITE3_TEXT);
        $result = $stmt->execute();
        $existing = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($existing['count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'User already has WL role for this game',
                'already_granted' => true
            ]);
            return;
        }
        
        // Grant the Discord role
        $role_granted = grantDiscordRole($user_id, $role_id);
        
        // Log the WL role grant
        $stmt = $db->prepare("INSERT INTO tbl_wl_role_grants 
                                (user_id, game, score, role_id, granted_at, discord_success) 
                              VALUES (?, ?, ?, ?, datetime('now'), ?)");
        $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
        $stmt->bindValue(2, $game, SQLITE3_TEXT);
        $stmt->bindValue(3, $score, SQLITE3_INTEGER);
        $stmt->bindValue(4, $role_id, SQLITE3_TEXT);
        $stmt->bindValue(5, $role_granted ? 1 : 0, SQLITE3_INTEGER);
        $stmt->execute();
        
        $grant_id = $db->lastInsertRowID();
        
                       // Get the configured bonus points for this game
               $stmt = $db->prepare("SELECT * FROM tbl_game_settings WHERE id = 1");
               $result = $stmt->execute();
               $settings = $result->fetchArray(SQLITE3_ASSOC);
               
               $wl_bonus = 1000; // Default bonus
               if ($settings) {
                   if ($game === 'tetris' && $settings['tetris_wl_enabled']) {
                       $wl_bonus = $settings['tetris_wl_bonus'];
                   } elseif ($game === 'snake' && $settings['snake_wl_enabled']) {
                       $wl_bonus = $settings['snake_wl_bonus'];
                   }
               }
               
               // Add points bonus for WL achievement
               $stmt = $db->prepare("INSERT INTO tbl_score_adjustments 
                                       (user_id, admin_id, amount, action, reason) 
                                     VALUES (?, 'SYSTEM', ?, 'add', 'wl_achievement_bonus')");
               $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
               $stmt->bindValue(2, $wl_bonus, SQLITE3_INTEGER);
               $stmt->execute();
        
        // Add to user scores
        $stmt = $db->prepare("INSERT INTO tbl_user_scores 
                                (user_id, score, source, timestamp) 
                              VALUES (?, ?, 'wl_achievement_bonus', datetime('now'))");
        $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
        $stmt->bindValue(2, $wl_bonus, SQLITE3_INTEGER);
        $stmt->execute();
        
        error_log("WL role grant logged: User $user_id, Game $game, Score $score, Role $role_id, Discord success: " . ($role_granted ? 'YES' : 'NO'));
        
        echo json_encode([
            'success' => true,
            'message' => 'WL role granted successfully!',
            'role_granted' => $role_granted,
            'grant_id' => $grant_id,
            'bonus_points' => $wl_bonus,
            'details' => [
                'user_id' => $user_id,
                'game' => $game,
                'score' => $score,
                'role_id' => $role_id,
                'discord_success' => $role_granted
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("WL role grant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to grant WL role: ' . $e->getMessage()]);
    }
}

function grantDiscordRole($user_id, $role_id) {
    try {
        error_log("grantDiscordRole called: User $user_id, Role $role_id");
        
        // Use the Discord bot's API to grant the role
        $bot_api_url = 'https://narrrfs.world/api/discord/grant-role.php';
        
        $data = [
            'user_id' => $user_id,
            'role_id' => $role_id,
            'action' => 'add_role'
        ];
        
        error_log("Making API call to: $bot_api_url with data: " . json_encode($data));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bot_api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . (getenv('DISCORD_BOT_SECRET') ?: 'admin_quest_system')
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        error_log("Discord API response: HTTP $http_code, Response: $response, Curl error: $curl_error");
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['success']) && $result['success']) {
                error_log("WL role granted successfully: User $user_id, Role $role_id");
                return true;
            } else {
                error_log("WL role grant failed - API returned success=false: " . json_encode($result));
                return false;
            }
        } else {
            error_log("WL role grant failed - HTTP $http_code: $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("WL role grant error: " . $e->getMessage());
        return false;
    }
}
?> 