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
    
    // Known collections mapped to their Discord roles
    $collectionsConfig = [
        'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML' => [
            'name' => 'Narrrfs World: Genesis Genetic',
            'role_name' => '🏆 Holder',
            'role_id' => '1402668301414563971'
        ],
        'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg' => [
            'name' => 'Narrrf Genesis VIP Drop',
            'role_name' => '🎴 VIP Holder',
            'role_id' => '1332016526848692345'
        ]
    ];

    // Helper maps for role name / collection name lookup
    $collectionsByRole = [];
    $collectionsByName = [];
    foreach ($collectionsConfig as $address => $info) {
        $collectionsByRole[$info['role_name']] = $address;
        $collectionsByName[strtolower($info['name'])] = $address;
    }

    // Determine which collections to verify (default: all)
    $collectionsToVerify = $collectionsConfig;
    if (!empty($collection)) {
        if (isset($collectionsConfig[$collection])) {
            $collectionsToVerify = [$collection => $collectionsConfig[$collection]];
        } elseif (isset($collectionsByRole[$collection])) {
            $addr = $collectionsByRole[$collection];
            $collectionsToVerify = [$addr => $collectionsConfig[$addr]];
        } elseif (isset($collectionsByName[strtolower($collection)])) {
            $addr = $collectionsByName[strtolower($collection)];
            $collectionsToVerify = [$addr => $collectionsConfig[$addr]];
        }
    }

    $verifiedCollections = [];

    foreach ($collectionsToVerify as $collectionAddress => $info) {
        $collectionCount = fetchCollectionNFTCount($walletAddress, $collectionAddress);

        $roleGranted = false;
        if ($collectionCount > 0) {
            $roleGranted = grantDiscordRole($userId, $user['username'] ?? '', $info['role_id'], $info['role_name']);

            logHolderVerification($db, $userId, $user['username'] ?? '', $walletAddress, $info['name'], $collectionCount, $roleGranted);

            if ($roleGranted) {
                logRoleGrant($db, $userId, $user['username'] ?? '', $info['role_id'], $info['role_name']);
            }
        } else {
            // Always log verification attempts even when no NFTs were found
            logHolderVerification($db, $userId, $user['username'] ?? '', $walletAddress, $info['name'], 0, false);
        }

        $verifiedCollections[] = [
            'collection_address' => $collectionAddress,
            'collection' => $info['name'],
            'role' => $info['role_name'],
            'role_id' => $info['role_id'],
            'count' => $collectionCount,
            'granted' => $roleGranted
        ];
    }

    echo json_encode([
        'success' => true,
        'wallet' => $walletAddress,
        'verified_collections' => $verifiedCollections,
        'message' => 'NFT verification completed.'
    ]);

} catch (Exception $e) {
    error_log("NFT Holder Verification Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Fetch the number of NFTs a wallet holds for a specific collection using Helius.
 */
function fetchCollectionNFTCount(string $walletAddress, string $collectionAddress): int
{
    try {
        $apiKey = getenv('HELIUS_API_KEY') ?: ($_ENV['HELIUS_API_KEY'] ?? $_SERVER['HELIUS_API_KEY'] ?? '');
        if (!$apiKey) {
            throw new Exception('Helius API key is not configured.');
        }

        $url = "https://api.helius.xyz/v0/addresses/{$walletAddress}/nfts?api-key={$apiKey}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Narrrfs-World-NFT-Verification/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Helius request error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception("Helius request failed with HTTP code {$httpCode}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Helius: ' . json_last_error_msg());
        }

        $count = 0;
        if (is_array($data)) {
            foreach ($data as $nft) {
                $nftCollection = $nft['collection'] ?? $nft['collectionAddress'] ?? $nft['grouping'] ?? '';

                if (is_array($nftCollection)) {
                    foreach ($nftCollection as $group) {
                        if (isset($group['groupKey'], $group['groupValue']) && $group['groupKey'] === 'collection') {
                            $nftCollection = $group['groupValue'];
                            break;
                        }
                    }
                } elseif (is_array($nftCollection) && isset($nftCollection['key'])) {
                    $nftCollection = $nftCollection['key'];
                }

                if ($nftCollection === $collectionAddress) {
                    $count++;
                }
            }
        }

        return $count;
    } catch (Exception $e) {
        error_log("Collection NFT count error ({$walletAddress}, {$collectionAddress}): " . $e->getMessage());
        return 0;
    }
}

/**
 * Grant a Discord role via internal API.
 */
function grantDiscordRole(string $userId, string $username, string $roleId, string $roleName): bool
{
    try {
        $discordApiUrl = 'https://narrrfs.world/api/discord/grant-role.php';
        $payload = [
            'action' => 'add_role',
            'user_id' => $userId,
            'role_id' => $roleId
        ];

        $ch = curl_init($discordApiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer admin_quest_system'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Discord role grant CURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception("Discord role grant failed with HTTP code {$httpCode}: {$response}");
        }

        $result = json_decode($response, true);
        $success = isset($result['success']) && $result['success'];

        if (!$success) {
            throw new Exception('Discord role grant failed: ' . $response);
        }

        return true;
    } catch (Exception $e) {
        error_log("Failed to grant Discord role {$roleName} ({$roleId}) to {$userId} ({$username}): " . $e->getMessage());
        return false;
    }
}

/**
 * Log holder verification attempts/successes.
 */
function logHolderVerification(PDO $db, string $userId, string $username, string $wallet, string $collectionName, int $count, bool $granted): void
{
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_holder_verifications
        (user_id, username, wallet, collection, nft_count, role_granted, verified_at)
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $userId,
        $username,
        $wallet,
        $collectionName,
        $count,
        $granted ? 1 : 0
    ]);
}

/**
 * Log role grants for auditing.
 */
function logRoleGrant(PDO $db, string $userId, string $username, string $roleId, string $roleName): void
{
    $stmt = $db->prepare("
        INSERT INTO tbl_role_grants 
        (user_id, username, role_id, role_name, granted_at, reason, granted_by)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $username,
        $roleId,
        $roleName,
        'NFT Holder Verification - Helius verification',
        'system'
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