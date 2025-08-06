<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    $walletAddress = $input['wallet_address'] ?? '';
    $nfts = $input['nfts'] ?? [];
    $collection = $input['collection'] ?? '';

    if (empty($userId) || empty($walletAddress)) {
        echo json_encode([
            'success' => false,
            'error' => 'User ID and wallet address are required'
        ]);
        exit;
    }

    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user info
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

    // Check if user already has a verification for this wallet
    $existingStmt = $db->prepare("
        SELECT * FROM tbl_holder_verifications 
        WHERE user_id = ? AND wallet = ? AND collection = ?
    ");
    $existingStmt->execute([$userId, $walletAddress, $collection]);
    $existingVerification = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingVerification && $existingVerification['role_granted']) {
        echo json_encode([
            'success' => false,
            'error' => 'User already verified and role granted for this wallet and collection'
        ]);
        exit;
    }

    // Process NFTs and determine role
    $roleId = null;
    $roleName = null;
    $nftCount = count($nfts);

    if ($nftCount > 0) {
        // Determine role based on collection - ONLY 2 OFFICIAL COLLECTIONS
        if ($collection === 'genesis' || $collection === 'Narrrfs World: Genesis Genetic') {
            $roleId = '1402668301414563971'; // Holder
            $roleName = '🏆 Holder';
        } elseif ($collection === 'vip' || $collection === 'Narrrf Genesis VIP Drop') {
            $roleId = '1332016526848692345'; // VIP Holder
            $roleName = '🎴 VIP Holder';
        } else {
            // Invalid collection - only support our 2 official collections
            echo json_encode([
                'success' => false,
                'error' => 'Invalid collection. Only "Narrrfs World: Genesis Genetic" and "Narrrf Genesis VIP Drop" are supported.'
            ]);
            exit;
        }

        // Store NFT ownership data
        foreach ($nfts as $nft) {
            $nftStmt = $db->prepare("
                INSERT OR REPLACE INTO tbl_nft_ownership 
                (wallet, token_id, collection, traits, rarity, mint_date, acquired_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $traits = json_encode($nft['metadata']['attributes'] ?? []);
            $rarity = $nft['metadata']['rarity'] ?? 'Unknown';
            $mintDate = $nft['metadata']['mint_date'] ?? null;
            
            $nftStmt->execute([
                $walletAddress,
                $nft['mint'],
                $collection,
                $traits,
                $rarity,
                $mintDate
            ]);
        }

        // Grant Discord role
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

        // Log verification
        $verificationStmt = $db->prepare("
            INSERT OR REPLACE INTO tbl_holder_verifications 
            (user_id, username, wallet, collection, nft_count, role_granted, verified_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $verificationStmt->execute([
            $userId,
            $user['username'],
            $walletAddress,
            $collection,
            $nftCount,
            $roleGranted ? 1 : 0
        ]);

        // Log role grant
        if ($roleGranted) {
            $roleGrantStmt = $db->prepare("
                INSERT INTO tbl_role_grants 
                (user_id, username, role_id, role_name, granted_at, reason, granted_by)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
            ");
            
            $roleGrantStmt->execute([
                $userId,
                $user['username'],
                $roleId,
                $roleName,
                'NFT Holder Verification - Web3 Wallet',
                'web3_verification'
            ]);
        }

        if ($roleGranted) {
            echo json_encode([
                'success' => true,
                'message' => "NFT verification successful! Role granted: $roleName",
                'role_name' => $roleName,
                'role_id' => $roleId,
                'nft_count' => $nftCount,
                'wallet_address' => $walletAddress,
                'collection' => $collection
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'NFT verification successful but failed to grant Discord role',
                'nft_count' => $nftCount,
                'wallet_address' => $walletAddress,
                'collection' => $collection,
                'discord_response' => $result
            ]);
        }

    } else {
        // No NFTs found
        echo json_encode([
            'success' => false,
            'error' => 'No NFTs from the specified collection found in the wallet',
            'wallet_address' => $walletAddress,
            'collection' => $collection
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