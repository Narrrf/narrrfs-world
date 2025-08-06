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
    $reason = $input['reason'] ?? 'Manual role grant via admin interface';

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

    // Get user info
    $userStmt = $db->prepare("SELECT discord_name FROM tbl_users WHERE discord_id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found in database'
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

    // Use the existing Discord role granting API
    $discordApiUrl = 'https://narrrfs.world/api/discord/grant-role.php';
    $data = [
        'action' => 'add_role',
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
    $roleGranted = ($httpCode === 200 && isset($result['success']) && $result['success']);

    // Log the role grant attempt
    $logStmt = $db->prepare("
        INSERT INTO tbl_role_grants (
            user_id, username, role_id, role_name, granted_at, reason, granted_by
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
    ");
    
    $logStmt->execute([
        $userId,
        $user['discord_name'],
        $roleId,
        $roleName,
        $reason,
        'admin_interface'
    ]);

    if ($roleGranted) {
        echo json_encode([
            'success' => true,
            'message' => "Role granted successfully to {$user['discord_name']}",
            'user_id' => $userId,
            'role_id' => $roleId,
            'role_name' => $roleName
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to grant role via Discord API',
            'discord_response' => $result,
            'http_code' => $httpCode,
            'note' => 'Role grant attempt has been logged for manual processing'
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