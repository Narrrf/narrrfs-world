<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Use the same database connection as the bot - Render production path
$db_path = '/var/www/html/db/narrrf_world.sqlite';
if (!file_exists($db_path)) {
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Could not connect to database']);
    exit;
}

// Discord bot configuration - same as bot
$MOD_ROLE_ID = '1332049628300054679';
$BOT_TOKEN = $_ENV['DISCORD_BOT_SECRET'] ?? 'your-bot-token';

// Function to verify Discord permissions - same as bot
function verifyDiscordPermissions($discordId) {
    global $MOD_ROLE_ID;
    
    // Admin users - same as in bot
    $adminUsers = [
        '328601656659017732', // narrrf
        '1260317833532014805',
        '987492370616561714'
    ];
    
    return in_array($discordId, $adminUsers);
}

// Function to get user's total score - same as bot
function getUserTotalScore($db, $userId) {
    $stmt = $db->prepare('SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ? (int)$row['total'] : 0;
}

// Function to check if user exists in scores table
function userExistsInScores($db, $userId) {
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_user_scores WHERE user_id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row && $row['count'] > 0;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? $input['action'] ?? '';
    
    switch ($action) {
        case 'addpoints':
            // Verify admin permissions
            $adminId = $input['admin_id'] ?? '';
            if (!verifyDiscordPermissions($adminId)) {
                throw new Exception('Insufficient permissions');
            }
            
            $userId = $input['user_id'] ?? '';
            $amount = intval($input['amount'] ?? 0);
            $reason = $input['reason'] ?? 'Manual admin adjustment';
            
            if (empty($userId) || $amount <= 0) {
                throw new Exception('Invalid parameters');
            }
            
            // EXACT SAME LOGIC AS BOT:
            // 1. Try to update existing user score
            $stmt = $db->prepare('UPDATE tbl_user_scores SET score = score + ? WHERE user_id = ?');
            $stmt->bindValue(1, $amount, SQLITE3_INTEGER);
            $stmt->bindValue(2, $userId, SQLITE3_TEXT);
            $stmt->execute();
            $affectedRows = $db->changes();
            
            // 2. If no rows affected, user doesn't exist, so insert new record
            if ($affectedRows === 0) {
                $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $amount, SQLITE3_INTEGER);
                $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
                $stmt->bindValue(4, 'addpoints', SQLITE3_TEXT);
                $stmt->execute();
            } else {
                // 3. If user exists, also insert a new record for tracking (same as bot logic)
                $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $amount, SQLITE3_INTEGER);
                $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
                $stmt->bindValue(4, 'addpoints', SQLITE3_TEXT);
                $stmt->execute();
            }
            
            // 3. Get updated balance
            $newBalance = getUserTotalScore($db, $userId);
            
            // 4. Log the adjustment
            $stmt = $db->prepare('INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $adminId, SQLITE3_TEXT);
            $stmt->bindValue(3, $amount, SQLITE3_INTEGER);
            $stmt->bindValue(4, 'add', SQLITE3_TEXT);
            $stmt->bindValue(5, $reason, SQLITE3_TEXT);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => "Added {$amount} \$DSPOINC to user {$userId}",
                'new_balance' => $newBalance,
                'reason' => $reason
            ]);
            break;
            
        case 'setpoints':
            // Verify admin permissions
            $adminId = $input['admin_id'] ?? '';
            if (!verifyDiscordPermissions($adminId)) {
                throw new Exception('Insufficient permissions');
            }
            
            $userId = $input['user_id'] ?? '';
            $newAmount = intval($input['amount'] ?? 0);
            $reason = $input['reason'] ?? 'Manual set by admin';
            
            if (empty($userId) || $newAmount < 0) {
                throw new Exception('Invalid parameters');
            }
            
            // Get current amount
            $oldAmount = getUserTotalScore($db, $userId);
            $delta = $newAmount - $oldAmount;
            
            // EXACT SAME LOGIC AS BOT:
            // 1. Try to update existing user score
            $stmt = $db->prepare('UPDATE tbl_user_scores SET score = ? WHERE user_id = ?');
            $stmt->bindValue(1, $newAmount, SQLITE3_INTEGER);
            $stmt->bindValue(2, $userId, SQLITE3_TEXT);
            $stmt->execute();
            $affectedRows = $db->changes();
            
            // 2. If no rows affected, user doesn't exist, so insert new record
            if ($affectedRows === 0) {
                $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $newAmount, SQLITE3_INTEGER);
                $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
                $stmt->bindValue(4, 'setpoints', SQLITE3_TEXT);
                $stmt->execute();
            } else {
                // 3. If user exists, also insert a new record for tracking (same as bot logic)
                $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $delta, SQLITE3_INTEGER); // Use delta for proper tracking
                $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
                $stmt->bindValue(4, 'setpoints', SQLITE3_TEXT);
                $stmt->execute();
            }
            
            // 3. Log the adjustment
            $stmt = $db->prepare('INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $adminId, SQLITE3_TEXT);
            $stmt->bindValue(3, $delta, SQLITE3_INTEGER);
            $stmt->bindValue(4, 'set', SQLITE3_TEXT);
            $stmt->bindValue(5, $reason, SQLITE3_TEXT);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => "Set user {$userId}'s \$DSPOINC to {$newAmount} (was {$oldAmount})",
                'new_balance' => $newAmount,
                'old_balance' => $oldAmount,
                'reason' => $reason
            ]);
            break;
            
        case 'removepoints':
            // Verify admin permissions
            $adminId = $input['admin_id'] ?? '';
            if (!verifyDiscordPermissions($adminId)) {
                throw new Exception('Insufficient permissions');
            }
            
            $userId = $input['user_id'] ?? '';
            $amount = intval($input['amount'] ?? 0);
            $reason = $input['reason'] ?? 'Manual admin adjustment';
            
            if (empty($userId) || $amount <= 0) {
                throw new Exception('Invalid parameters');
            }
            
            // EXACT SAME LOGIC AS BOT:
            // 1. Try to update existing user score
            $stmt = $db->prepare('UPDATE tbl_user_scores SET score = score - ? WHERE user_id = ?');
            $stmt->bindValue(1, $amount, SQLITE3_INTEGER);
            $stmt->bindValue(2, $userId, SQLITE3_TEXT);
            $stmt->execute();
            $affectedRows = $db->changes();
            
            // 2. If no rows affected, user doesn't exist, so insert new record with negative score
            if ($affectedRows === 0) {
                $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, -$amount, SQLITE3_INTEGER);
                $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
                $stmt->bindValue(4, 'removepoints', SQLITE3_TEXT);
                $stmt->execute();
            } else {
                // 3. If user exists, also insert a new record for tracking (same as bot logic)
                $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, -$amount, SQLITE3_INTEGER);
                $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
                $stmt->bindValue(4, 'removepoints', SQLITE3_TEXT);
                $stmt->execute();
            }
            
            // 3. Get updated balance
            $newBalance = getUserTotalScore($db, $userId);
            
            // 4. Log the adjustment
            $stmt = $db->prepare('INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $adminId, SQLITE3_TEXT);
            $stmt->bindValue(3, -$amount, SQLITE3_INTEGER);
            $stmt->bindValue(4, 'remove', SQLITE3_TEXT);
            $stmt->bindValue(5, $reason, SQLITE3_TEXT);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => "Removed {$amount} \$DSPOINC from user {$userId}",
                'new_balance' => $newBalance,
                'reason' => $reason
            ]);
            break;
            
        case 'getbalance':
            $userId = $input['user_id'] ?? '';
            if (empty($userId)) {
                throw new Exception('User ID required');
            }
            
            $balance = getUserTotalScore($db, $userId);
            
            echo json_encode([
                'success' => true,
                'balance' => $balance,
                'user_id' => $userId
            ]);
            break;
            
        case 'gethistory':
            $userId = $input['user_id'] ?? '';
            $limit = intval($input['limit'] ?? 20);
            
            if (empty($userId)) {
                throw new Exception('User ID required');
            }
            
            $stmt = $db->prepare('
                SELECT amount, action, reason, admin_id, timestamp
                FROM tbl_score_adjustments
                WHERE user_id = ?
                ORDER BY timestamp DESC
                LIMIT ?
            ');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            $history = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $history[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'history' => $history,
                'user_id' => $userId
            ]);
            break;
            
        case 'searchuser':
            $searchTerm = $input['search'] ?? '';
            if (empty($searchTerm)) {
                throw new Exception('Search term required');
            }
            
            // Search by Discord ID or username - using same logic as bot
            $stmt = $db->prepare('
                SELECT us.user_id, SUM(us.score) as total_score, u.username, u.discord_id
                FROM tbl_user_scores us
                LEFT JOIN tbl_users u ON us.user_id = u.discord_id
                WHERE us.user_id LIKE ? OR u.username LIKE ?
                GROUP BY us.user_id
                ORDER BY total_score DESC
                LIMIT 10
            ');
            $searchPattern = "%{$searchTerm}%";
            $stmt->bindValue(1, $searchPattern, SQLITE3_TEXT);
            $stmt->bindValue(2, $searchPattern, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $users = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $users[] = [
                    'user_id' => $row['user_id'],
                    'score' => (int)$row['total_score'],
                    'username' => $row['username'],
                    'discord_id' => $row['discord_id']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$db->close();
?> 