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

    // Role IDs from Discord server
    $vipRoleId = '1332016526848692345';  // VIP Holder Role
    $genesisRoleId = '1402668301414563971';  // Genesis Genetic Role

    // Get all users with Discord roles
    $stmt = $db->prepare("
        SELECT 
            ur.user_id,
            ur.role_id,
            ur.role_name,
            ur.granted_at,
            ur.revoked_at,
            u.username,
            u.avatar_url,
            hv.wallet,
            hv.collection,
            hv.nft_count,
            hv.role_granted,
            hv.verified_at
        FROM tbl_role_grants ur
        LEFT JOIN tbl_users u ON ur.user_id = u.discord_id
        LEFT JOIN tbl_holder_verifications hv ON ur.user_id = hv.user_id
        WHERE ur.role_id IN (?, ?) AND ur.revoked_at IS NULL
        ORDER BY ur.granted_at DESC
    ");
    
    $stmt->execute([$vipRoleId, $genesisRoleId]);
    $roleMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by role
    $vipMembers = [];
    $genesisMembers = [];
    
    foreach ($roleMembers as $member) {
        if ($member['role_id'] === $vipRoleId) {
            $vipMembers[] = $member;
        } elseif ($member['role_id'] === $genesisRoleId) {
            $genesisMembers[] = $member;
        }
    }

    // Get verification statistics
    $stats = [];
    
    // Total VIP members
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_role_grants WHERE role_id = ? AND revoked_at IS NULL");
    $stmt->execute([$vipRoleId]);
    $stats['vip_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total Genesis members
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_role_grants WHERE role_id = ? AND revoked_at IS NULL");
    $stmt->execute([$genesisRoleId]);
    $stats['genesis_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total verified wallets
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM tbl_holder_verifications WHERE role_granted = 1");
    $stmt->execute();
    $stats['verified_wallets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Members with wallets
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ur.user_id) as count 
        FROM tbl_role_grants ur
        INNER JOIN tbl_holder_verifications hv ON ur.user_id = hv.user_id
        WHERE ur.role_id IN (?, ?) AND ur.revoked_at IS NULL AND hv.wallet IS NOT NULL
    ");
    $stmt->execute([$vipRoleId, $genesisRoleId]);
    $stats['members_with_wallets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'roles' => [
            'vip' => [
                'role_id' => $vipRoleId,
                'role_name' => 'VIP Holder',
                'members' => $vipMembers,
                'count' => count($vipMembers)
            ],
            'genesis' => [
                'role_id' => $genesisRoleId,
                'role_name' => 'Genesis Genetic',
                'members' => $genesisMembers,
                'count' => count($genesisMembers)
            ]
        ]
    ]);

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
