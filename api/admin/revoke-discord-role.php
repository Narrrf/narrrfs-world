<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$dbPath = '/var/www/html/db/narrrf_world.sqlite';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    $roleId = $input['role_id'] ?? '';
    $reason = $input['reason'] ?? 'Manual role revocation via admin interface';

    if (empty($userId) || empty($roleId)) {
        echo json_encode([
            'success' => false,
            'error' => 'User ID and Role ID are required'
        ]);
        exit;
    }

    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user info - use same logic as existing user search system
    $userStmt = $db->prepare("
        SELECT us.user_id, SUM(us.score) as total_score, u.username, u.discord_id
        FROM tbl_user_scores us
        LEFT JOIN tbl_users u ON us.user_id = u.discord_id
        WHERE us.user_id = ?
        GROUP BY us.user_id
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found in database. User must have played games to be in the system.'
        ]);
        exit;
    }

    // Get role name
    $roleNames = [
        '1402668301414563971' => '🏆 Holder',
        '1332016526848692345' => '🎴 VIP Holder',
        '1332108350518857842' => '🧀 Cheese Hunter',
        '1332108350518857843' => '🎯 Alpha Caller',
        '1332108350518857844' => '🏅 Champion'
    ];
    
    $roleName = $roleNames[$roleId] ?? 'Unknown Role';

    // Use the existing Discord role granting API (DELETE method for revocation)
    $discordApiUrl = 'https://narrrfs.world/api/discord/grant-role.php';
    $data = [
        'action' => 'remove_role',
        'user_id' => $userId,
        'role_id' => $roleId
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $discordApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer admin_quest_system'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    $roleRevoked = ($httpCode === 200 && isset($result['success']) && $result['success']);

    // Update existing role grant to mark as revoked
    $updateStmt = $db->prepare("
        UPDATE tbl_role_grants 
        SET revoked_at = CURRENT_TIMESTAMP, revoked_by = ? 
        WHERE user_id = ? AND role_id = ? AND revoked_at IS NULL
    ");
    
    $updateStmt->execute(['admin_interface', $userId, $roleId]);

    // Log the revocation as a new entry for audit trail
    $logStmt = $db->prepare("
        INSERT INTO tbl_role_grants (
            user_id, username, role_id, role_name, granted_at, reason, granted_by, revoked_at, revoked_by
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, CURRENT_TIMESTAMP, ?)
    ");
    
    $logStmt->execute([
        $userId,
        $user['username'],
        $roleId,
        $roleName,
        $reason,
        'admin_interface',
        'admin_interface'
    ]);

    if ($roleRevoked) {
        echo json_encode([
            'success' => true,
            'message' => "Role revoked successfully from {$user['username']}",
            'user_id' => $userId,
            'role_id' => $roleId,
            'role_name' => $roleName
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to revoke role via Discord API',
            'discord_response' => $result,
            'http_code' => $httpCode,
            'note' => 'Role revocation attempt has been logged for manual processing'
        ]);
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