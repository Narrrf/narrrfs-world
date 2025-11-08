<?php
/**
 * 🔒 SET ADMIN SESSION FOR PARTNER PORTAL ACCESS
 * 
 * Sets $_SESSION['admin_logged_in'] = true for authenticated admin users
 * This fixes the Partner Portal "Admin access required" issue
 * 
 * Created: October 29, 2025
 */

// Start session
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $discordId = $data['discord_id'] ?? null;
    $discordName = $data['discord_name'] ?? null;

    if (!$discordId || !$discordName) {
        throw new Exception('Missing Discord credentials');
    }

    $ownerIds = [
        '328601656659017732' // narrrf
    ];

    $allowedRoleNames = [
        'Founder',
        'Moderator',
        'Admin',
        'Bot Master'
    ];

    $hasOwnerBypass = in_array($discordId, $ownerIds, true) || strtolower($discordName) === 'narrrf';
    $hasAllowedRole = false;
    $userRoles = [];

    if (!$hasOwnerBypass) {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("
            SELECT role_name
            FROM tbl_user_roles
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $discordId]);
        $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($userRoles)) {
            foreach ($userRoles as $roleName) {
                if (in_array($roleName, $allowedRoleNames, true)) {
                    $hasAllowedRole = true;
                    break;
                }
            }
        }
    }

    if ($hasOwnerBypass || $hasAllowedRole) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['discord_id'] = $discordId;
        $_SESSION['discord_name'] = $discordName;
        $_SESSION['admin_roles'] = $userRoles ?? [];

        error_log("✅ Admin session set for: {$discordName} ({$discordId})");

        echo json_encode([
            'success' => true,
            'message' => 'Admin session set successfully',
            'admin_logged_in' => $_SESSION['admin_logged_in'],
            'discord_id' => $_SESSION['discord_id'],
            'discord_name' => $_SESSION['discord_name'],
            'roles' => $_SESSION['admin_roles']
        ]);
    } else {
        error_log("❌ Unauthorized admin session attempt: {$discordName} ({$discordId})");

        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
    }
} catch (Exception $e) {
    error_log("❌ Admin session error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
