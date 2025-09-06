<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Admin authentication check
require_once '../auth/auth.php';
if (!checkAdminAuthentication()) {
    exit; 
}

// Database configuration - Environment aware
$dbPath = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
    ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local development
    : (file_exists(__DIR__ . '/../../db/narrrf_world.sqlite') 
        ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local fallback
        : '/data/narrrf_world.sqlite');              // Render production

try {
    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'verify_wallet':
                $userId = $input['user_id'] ?? '';
                $wallet = $input['wallet'] ?? '';
                $collection = $input['collection'] ?? '';
                $nftCount = $input['nft_count'] ?? 0;
                
                // Insert or update verification
                $stmt = $db->prepare("
                    INSERT OR REPLACE INTO tbl_holder_verifications 
                    (user_id, username, wallet, collection, nft_count, role_granted, verified_at, created_at)
                    VALUES (?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))
                ");
                
                // Get username from users table
                $userStmt = $db->prepare("SELECT username FROM tbl_users WHERE discord_id = ?");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                $username = $user ? $user['username'] : 'Unknown';
                
                $stmt->execute([$userId, $username, $wallet, $collection, $nftCount]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Wallet verification updated successfully'
                ]);
                break;
                
            case 'revoke_verification':
                $userId = $input['user_id'] ?? '';
                $collection = $input['collection'] ?? '';
                
                $stmt = $db->prepare("
                    UPDATE tbl_holder_verifications 
                    SET role_granted = 0, verified_at = datetime('now')
                    WHERE user_id = ? AND collection = ?
                ");
                $stmt->execute([$userId, $collection]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Verification revoked successfully'
                ]);
                break;
                
            case 'sync_discord_role':
                $userId = $input['user_id'] ?? '';
                $roleId = $input['role_id'] ?? '';
                $roleName = $input['role_name'] ?? '';
                $action_type = $input['type'] ?? 'grant'; // grant or revoke
                
                if ($action_type === 'grant') {
                    // Grant role
                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO tbl_role_grants 
                        (user_id, username, role_id, role_name, granted_at, reason, granted_by)
                        VALUES (?, ?, ?, ?, datetime('now'), 'Admin verification sync', 'admin')
                    ");
                    
                    // Get username
                    $userStmt = $db->prepare("SELECT username FROM tbl_users WHERE discord_id = ?");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                    $username = $user ? $user['username'] : 'Unknown';
                    
                    $stmt->execute([$userId, $username, $roleId, $roleName]);
                } else {
                    // Revoke role
                    $stmt = $db->prepare("
                        UPDATE tbl_role_grants 
                        SET revoked_at = datetime('now'), revoked_by = 'admin'
                        WHERE user_id = ? AND role_id = ? AND revoked_at IS NULL
                    ");
                    $stmt->execute([$userId, $roleId]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "Role {$action_type}ed successfully"
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid action'
                ]);
        }
    } else {
        // GET request - return current verification status
        $userId = $_GET['user_id'] ?? '';
        
        if ($userId) {
            // Get user verification status
            $stmt = $db->prepare("
                SELECT hv.*, u.username, u.avatar_url
                FROM tbl_holder_verifications hv
                LEFT JOIN tbl_users u ON hv.user_id = u.discord_id
                WHERE hv.user_id = ?
                ORDER BY hv.verified_at DESC
            ");
            $stmt->execute([$userId]);
            $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get user roles
            $roleStmt = $db->prepare("
                SELECT role_id, role_name, granted_at, revoked_at
                FROM tbl_role_grants
                WHERE user_id = ?
                ORDER BY granted_at DESC
            ");
            $roleStmt->execute([$userId]);
            $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'user_id' => $userId,
                'verifications' => $verifications,
                'roles' => $roles
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User ID required'
            ]);
        }
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?>
