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

    // Discord API configuration
    $botToken = getenv('DISCORD_BOT_SECRET');
    $guildId = getenv('DISCORD_GUILD') ?: '1332015322546311218';
    
    // Role IDs from Discord server
    $vipRoleId = '1332016526848692345';  // VIP Holder Role
    $genesisRoleId = '1402668301414563971';  // Genesis Genetic Role (Holder)

    if (!$botToken) {
        throw new Exception('Discord bot token not configured');
    }

    // Function to fetch Discord role members
    function fetchDiscordRoleMembers($botToken, $guildId, $roleId) {
        $url = "https://discord.com/api/v10/guilds/{$guildId}/members?limit=1000";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $botToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Discord API error: HTTP {$httpCode}");
        }
        
        $members = json_decode($response, true);
        if (!$members) {
            throw new Exception('Invalid Discord API response');
        }
        
        // Filter members who have the specific role
        $roleMembers = [];
        foreach ($members as $member) {
            if (isset($member['roles']) && in_array($roleId, $member['roles'])) {
                $roleMembers[] = [
                    'user_id' => $member['user']['id'],
                    'username' => $member['user']['username'],
                    'discriminator' => $member['user']['discriminator'],
                    'avatar_url' => $member['user']['avatar'] ? 
                        "https://cdn.discordapp.com/avatars/{$member['user']['id']}/{$member['user']['avatar']}.png" : 
                        "https://cdn.discordapp.com/embed/avatars/" . ($member['user']['discriminator'] % 5) . ".png",
                    'joined_at' => $member['joined_at']
                ];
            }
        }
        
        return $roleMembers;
    }

    // Fetch VIP members from Discord
    $vipMembers = fetchDiscordRoleMembers($botToken, $guildId, $vipRoleId);
    
    // Fetch Genesis (Holder) members from Discord
    $genesisMembers = fetchDiscordRoleMembers($botToken, $guildId, $genesisRoleId);

    // Get verification data from database
    $stmt = $db->prepare("
        SELECT 
            hv.user_id,
            hv.username,
            hv.wallet,
            hv.collection,
            hv.nft_count,
            hv.role_granted,
            hv.verified_at
        FROM tbl_holder_verifications hv
        ORDER BY hv.verified_at DESC
    ");
    $stmt->execute();
    $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge Discord data with verification data
    function mergeDiscordWithVerification($discordMembers, $verifications, $collection) {
        $merged = [];
        
        foreach ($discordMembers as $member) {
            $memberData = $member;
            $memberData['wallet'] = null;
            $memberData['nft_count'] = 0;
            $memberData['verified'] = false;
            $memberData['collection'] = $collection;
            
            // Find verification data for this user
            foreach ($verifications as $verification) {
                if ($verification['user_id'] === $member['user_id'] && 
                    $verification['collection'] === $collection) {
                    $memberData['wallet'] = $verification['wallet'];
                    $memberData['nft_count'] = $verification['nft_count'];
                    $memberData['verified'] = $verification['role_granted'] == 1;
                    break;
                }
            }
            
            $merged[] = $memberData;
        }
        
        return $merged;
    }

    // Merge VIP members with verification data
    $vipMembersWithVerification = mergeDiscordWithVerification($vipMembers, $verifications, 'Narrrf Genesis VIP Drop');
    
    // Merge Genesis members with verification data
    $genesisMembersWithVerification = mergeDiscordWithVerification($genesisMembers, $verifications, 'Narrrfs World: Genesis Genetic');

    // Calculate statistics
    $stats = [
        'vip_members' => count($vipMembers),
        'genesis_members' => count($genesisMembers),
        'verified_wallets' => count(array_filter($verifications, function($v) { return $v['role_granted'] == 1; })),
        'members_with_wallets' => count(array_filter($verifications, function($v) { return !empty($v['wallet']); }))
    ];

    echo json_encode([
        'success' => true,
        'source' => 'discord_api',
        'last_updated' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'roles' => [
            'vip' => [
                'role_id' => $vipRoleId,
                'role_name' => 'VIP Holder',
                'members' => $vipMembersWithVerification,
                'count' => count($vipMembersWithVerification)
            ],
            'genesis' => [
                'role_id' => $genesisRoleId,
                'role_name' => 'Genesis Genetic',
                'members' => $genesisMembersWithVerification,
                'count' => count($genesisMembersWithVerification)
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch Discord role data: ' . $e->getMessage(),
        'source' => 'discord_api_error'
    ]);
}
?>
