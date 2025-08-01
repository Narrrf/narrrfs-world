<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection - using same path as Discord bot
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
    case 'get_claims':
        getQuestClaims($db);
        break;
    case 'approve_claim':
        approveQuestClaim($db);
        break;
    case 'reject_claim':
        rejectQuestClaim($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getQuestClaims($db) {
    try {
        $status_filter = $_GET['status'] ?? 'all';
        
        if ($status_filter !== 'all') {
            $sql = "SELECT 
                        qc.claim_id,
                        qc.quest_id,
                        qc.user_id,
                        qc.proof,
                        qc.claimed_at,
                        qc.status,
                        qc.reviewed_at,
                        q.description as quest_title,
                        q.reward as quest_reward,
                        q.role_id as quest_role_id,
                        u.username
                    FROM tbl_quest_claims qc
                    LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
                    LEFT JOIN tbl_users u ON qc.user_id = u.discord_id
                    WHERE qc.status = ?
                    ORDER BY qc.claimed_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $status_filter, SQLITE3_TEXT);
        } else {
            $sql = "SELECT 
                        qc.claim_id,
                        qc.quest_id,
                        qc.user_id,
                        qc.proof,
                        qc.claimed_at,
                        qc.status,
                        qc.reviewed_at,
                        q.description as quest_title,
                        q.reward as quest_reward,
                        q.role_id as quest_role_id,
                        u.username
                    FROM tbl_quest_claims qc
                    LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
                    LEFT JOIN tbl_users u ON qc.user_id = u.discord_id
                    ORDER BY qc.claimed_at DESC";
            $stmt = $db->prepare($sql);
        }
        
        $result = $stmt->execute();
        $claims = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $claims[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'claims' => $claims,
            'total' => count($claims)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get quest claims: ' . $e->getMessage()]);
    }
}

function approveQuestClaim($db) {
    global $input_data;
    
    try {
        $claim_id = $input_data['claim_id'] ?? null;
        $admin_id = $input_data['admin_id'] ?? null;
        
        if (!$claim_id || !$admin_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing claim_id or admin_id']);
            return;
        }
        
        // Get claim details
        $stmt = $db->prepare("SELECT 
                                qc.*, 
                                q.description as quest_title, 
                                q.reward as quest_reward,
                                q.role_id as quest_role_id
                              FROM tbl_quest_claims qc
                              LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
                              WHERE qc.claim_id = ?");
        $stmt->bindValue(1, $claim_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $claim = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$claim) {
            http_response_code(404);
            echo json_encode(['error' => 'Claim not found']);
            return;
        }
        
        if ($claim['status'] !== 'pending') {
            http_response_code(400);
            echo json_encode(['error' => 'Claim is not pending']);
            return;
        }
        
        // Update claim status
        $stmt = $db->prepare("UPDATE tbl_quest_claims SET 
                                status = 'approved', 
                                reviewed_at = datetime('now') 
                              WHERE claim_id = ?");
        $stmt->bindValue(1, $claim_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Add points to user
        if ($claim['quest_reward'] > 0) {
            // Insert into score adjustments (for admin tracking)
            $stmt = $db->prepare("INSERT INTO tbl_score_adjustments 
                                    (user_id, admin_id, amount, action, reason, quest_id) 
                                  VALUES (?, ?, ?, 'add', 'quest_completion', ?)");
            $stmt->bindValue(1, $claim['user_id'], SQLITE3_TEXT);
            $stmt->bindValue(2, $admin_id, SQLITE3_TEXT);
            $stmt->bindValue(3, $claim['quest_reward'], SQLITE3_INTEGER);
            $stmt->bindValue(4, $claim['quest_id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            // Insert into user scores (for profile calculation)
            $stmt = $db->prepare("INSERT INTO tbl_user_scores 
                                    (user_id, score, game, source, timestamp) 
                                  VALUES (?, ?, 'discord', 'quest_completion', datetime('now'))");
            $stmt->bindValue(1, $claim['user_id'], SQLITE3_TEXT);
            $stmt->bindValue(2, $claim['quest_reward'], SQLITE3_INTEGER);
            $stmt->execute();
        }
        
        // Grant Discord role if specified
        $role_granted = false;
        $role_error = '';
        if ($claim['quest_role_id']) {
            error_log("Attempting to grant role: User {$claim['user_id']}, Role {$claim['quest_role_id']}");
            
            // First try the Discord API
            $role_granted = grantDiscordRole($claim['user_id'], $claim['quest_role_id']);
            
            if (!$role_granted) {
                // Fallback: Log the role grant attempt for manual processing
                error_log("Discord API failed, logging role grant for manual processing: User {$claim['user_id']}, Role {$claim['quest_role_id']}");
                $role_error = 'Discord API failed - role needs manual assignment';
                
                // Insert into a manual role grants table for tracking
                try {
                    $stmt = $db->prepare("INSERT INTO tbl_manual_role_grants 
                                            (user_id, role_id, quest_id, claim_id, requested_at, status) 
                                          VALUES (?, ?, ?, ?, datetime('now'), 'pending')");
                    $stmt->bindValue(1, $claim['user_id'], SQLITE3_TEXT);
                    $stmt->bindValue(2, $claim['quest_role_id'], SQLITE3_TEXT);
                    $stmt->bindValue(3, $claim['quest_id'], SQLITE3_INTEGER);
                    $stmt->bindValue(4, $claim_id, SQLITE3_INTEGER);
                    $stmt->execute();
                    error_log("Manual role grant logged for user {$claim['user_id']}, role {$claim['quest_role_id']}");
                } catch (Exception $e) {
                    error_log("Failed to log manual role grant: " . $e->getMessage());
                }
            }
            
            error_log("Role grant result: " . ($role_granted ? 'SUCCESS' : 'FAILED') . " for User {$claim['user_id']}, Role {$claim['quest_role_id']}");
        } else {
            error_log("No role_id specified for quest {$claim['quest_id']}");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Quest claim approved successfully',
            'points_added' => $claim['quest_reward'],
            'role_granted' => $role_granted,
            'role_id' => $claim['quest_role_id'],
            'role_error' => $role_error
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to approve claim: ' . $e->getMessage()]);
    }
}

function rejectQuestClaim($db) {
    global $input_data;
    
    try {
        $claim_id = $input_data['claim_id'] ?? null;
        $admin_id = $input_data['admin_id'] ?? null;
        $reason = $input_data['reason'] ?? 'Rejected by admin';
        
        if (!$claim_id || !$admin_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing claim_id or admin_id']);
            return;
        }
        
        // Update claim status
        $stmt = $db->prepare("UPDATE tbl_quest_claims SET 
                                status = 'rejected', 
                                reviewed_at = datetime('now') 
                              WHERE claim_id = ?");
        $stmt->bindValue(1, $claim_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Quest claim rejected successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reject claim: ' . $e->getMessage()]);
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
                error_log("Role granted successfully: User $user_id, Role $role_id");
                return true;
            } else {
                error_log("Role grant failed - API returned success=false: " . json_encode($result));
                return false;
            }
        } else {
            error_log("Role grant failed - HTTP $http_code: $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Role grant error: " . $e->getMessage());
        return false;
    }
}
?>