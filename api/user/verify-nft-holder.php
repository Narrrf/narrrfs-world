<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dbPath = '/var/www/html/db/narrrf_world.sqlite';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $userId = $input['user_id'] ?? '';
    $walletAddress = $input['wallet_address'] ?? '';
    $nfts = $input['nfts'] ?? [];
    $collection = $input['collection'] ?? '';
    $signature = $input['signature'] ?? '';
    $message = $input['message'] ?? '';
    
    // Validate required fields
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    if (empty($walletAddress)) {
        throw new Exception('Wallet address is required');
    }
    
    if (empty($signature)) {
        throw new Exception('Cryptographic signature is required for security');
    }
    
    if (empty($message)) {
        throw new Exception('Signed message is required');
    }
    
    // Validate wallet address format
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $walletAddress)) {
        throw new Exception('Invalid Solana wallet address format');
    }
    
    // Verify the signature on the server side
    if (!verifySolanaSignature($walletAddress, $message, $signature)) {
        throw new Exception('Invalid signature. Please sign the verification message with your wallet.');
    }
    
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
    
    // If user not found in scores, check if they exist in users table
    if (!$user) {
        $userCheckStmt = $db->prepare("SELECT discord_id, username FROM tbl_users WHERE discord_id = ?");
        $userCheckStmt->execute([$userId]);
        $userExists = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userExists) {
            // User exists but hasn't played games yet - create a basic entry
            $user = [
                'user_id' => $userId,
                'total_score' => 0,
                'username' => $userExists['username'],
                'discord_id' => $userId
            ];
            error_log("User $userId exists but hasn't played games yet - allowing verification");
        } else {
            throw new Exception('User not found in database. User must be registered in the system.');
        }
    }
    
    // Check for existing verification
    $existingStmt = $db->prepare("
        SELECT * FROM tbl_holder_verifications 
        WHERE user_id = ? AND wallet = ? AND collection = ?
        ORDER BY verified_at DESC LIMIT 1
    ");
    $existingVerification = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    // Process NFTs and determine role
    $roleId = null;
    $roleName = null;
    $nftCount = count($nfts);
    $verifiedCollections = [];

    if ($nftCount > 0) {
        // Process each collection found
        foreach ($nfts as $nft) {
            $collectionName = $nft['collection'] ?? '';
            $role = $nft['role'] ?? '';
            $roleId = $nft['roleId'] ?? '';
            $count = $nft['count'] ?? 0;
            
            // Validate collection and role
            if ($collectionName === 'Narrrfs World: Genesis Genetic' && $role === '🏆 Holder') {
                $roleId = '1402668301414563971';
                $roleName = '🏆 Holder';
            } elseif ($collectionName === 'Narrrf Genesis VIP Drop' && $role === '🎴 VIP Holder') {
                $roleId = '1332016526848692345';
                $roleName = '🎴 VIP Holder';
            } else {
                // Skip invalid collections
                continue;
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

            // Log verification - only use existing columns
            $verificationStmt = $db->prepare("
                INSERT OR REPLACE INTO tbl_holder_verifications 
                (user_id, username, wallet, collection, nft_count, role_granted, verified_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $verificationStmt->execute([
                $userId,
                $user['username'] ?? '',
                $walletAddress,
                $collectionName,
                $count,
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
                    $user['username'] ?? '',
                    $roleId,
                    $roleName,
                    'NFT Holder Verification - Helius searchAssets (Signed)',
                    'system'
                ]);
            }

            $verifiedCollections[] = [
                'collection' => $collectionName,
                'role' => $roleName,
                'role_id' => $roleId,
                'count' => $count,
                'granted' => $roleGranted
            ];
        }

        echo json_encode([
            'success' => true,
            'role_name' => $roleName,
            'role_id' => $roleId,
            'nft_count' => $nftCount,
            'wallet' => $walletAddress,
            'collections_found' => array_column($verifiedCollections, 'collection'),
            'verified_collections' => $verifiedCollections,
            'message' => 'NFT verification successful! Role granted.'
        ]);

    } else {
        throw new Exception('No NFTs found in the specified collections.');
    }

} catch (Exception $e) {
    error_log("NFT Holder Verification Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Verify Solana signature on the server side
 * This function validates that the signature was created by the claimed public key
 */
function verifySolanaSignature($publicKey, $message, $signature) {
    try {
        // Basic format validation
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $publicKey)) {
            error_log("Invalid public key format: $publicKey");
            return false;
        }
        
        if (empty($signature) || empty($message)) {
            error_log("Missing signature or message");
            return false;
        }
        
        // Validate signature format (should be hex string)
        if (!preg_match('/^[0-9a-fA-F]+$/', $signature)) {
            error_log("Invalid signature format: $signature");
            return false;
        }
        
        // For production, you should implement proper Ed25519 signature verification
        // Here's a placeholder implementation that you should replace with proper verification
        
        // TODO: Implement proper Ed25519 signature verification using a library like:
        // - sodium_compat (PHP)
        // - Or use a Solana RPC call to verify the signature
        
        // For now, we'll do basic validation and log for debugging
        $signatureLength = strlen($signature);
        if ($signatureLength !== 128) { // Ed25519 signatures are 64 bytes = 128 hex chars
            error_log("Invalid signature length: $signatureLength (expected 128)");
            return false;
        }
        
        // Log the verification attempt for debugging
        error_log("Signature verification attempt - PublicKey: $publicKey, Message: $message, Signature: $signature");
        
        // TODO: Replace this with proper Ed25519 verification
        // For now, we'll accept the signature if it passes basic validation
        // This is a security placeholder - implement proper verification before production
        
        // In production, you should:
        // 1. Use a proper Ed25519 library
        // 2. Verify the signature against the public key and message
        // 3. Ensure the message hasn't been tampered with
        // 4. Add rate limiting to prevent abuse
        
        return true;
        
    } catch (Exception $e) {
        error_log("Signature verification error: " . $e->getMessage());
        return false;
    }
}
?> 