<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$wallet = $_GET['wallet'] ?? '';
$collection = $_GET['collection'] ?? ''; // Collection address to search for

if (!$wallet) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing wallet address']);
    exit;
}

try {
    // Enhanced wallet address validation (Solana format)
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet)) {
        throw new Exception('Invalid Solana wallet address format. Expected base58 encoded public key.');
    }

    // Use Helius API searchAssets method for efficient collection-specific queries
    $heliusApiKey = getenv('HELIUS_API_KEY');
    
    if (!$heliusApiKey || $heliusApiKey === 'your_helius_api_key_here') {
        http_response_code(500);
        echo json_encode([
            'error' => 'Helius API key not configured',
            'details' => 'Please set HELIUS_API_KEY environment variable in Render Dashboard or local .env file',
            'setup_url' => 'https://dev.helius.xyz/',
            'docs' => 'See README_ENVIRONMENT.md for setup instructions'
        ]);
        exit;
    }
    
    if (!empty($collection)) {
        // Direct collection search using searchAssets - much more efficient
        $url = "https://api.helius.xyz/v1/searchAssets?api-key=$heliusApiKey";
        
        $payload = [
            "ownerAddress" => $wallet,
            "grouping" => ["collection", "collectionKey"],
            "page" => 1,
            "limit" => 1000
        ];
        
        // Make POST request for searchAssets
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Narrrfs-World-NFT-Verifier/1.0'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('CURL error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Helius API HTTP error: ' . $httpCode);
        }
        
        if (empty($response)) {
            throw new Exception('Empty response from Helius API');
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Helius API: ' . json_last_error_msg());
        }
        
        // Filter for the specific collection - this is the key efficiency gain
        $nfts = [];
        if (isset($data['result']) && is_array($data['result'])) {
            foreach ($data['result'] as $nft) {
                // Check if NFT belongs to the specified collection
                $nftCollection = $nft['grouping'] ?? [];
                $isTargetCollection = false;
                
                foreach ($nftCollection as $group) {
                    if (isset($group['groupKey']) && $group['groupKey'] === 'collection') {
                        $collectionKey = $group['groupValue'] ?? '';
                        if ($collectionKey === $collection) {
                            $isTargetCollection = true;
                            break;
                        }
                    }
                }
                
                if ($isTargetCollection) {
                    $nfts[] = $nft;
                }
            }
        }
        
        $count = count($nfts);
        error_log("Helius searchAssets found $count NFTs for wallet: $wallet in collection: $collection");
        
        // Return minimal data - just what we need for role assignment
        echo json_encode([
            'success' => true,
            'nfts' => $nfts,
            'count' => $count,
            'wallet' => $wallet,
            'collection' => $collection,
            'method' => 'helius_searchAssets',
            'has_assets' => $count > 0,
            'timestamp' => date('c')
        ]);
        
    } else {
        // If no specific collection, return all NFTs (fallback)
        $url = "https://api.helius.xyz/v0/addresses/$wallet/nfts?api-key=$heliusApiKey";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Narrrfs-World-NFT-Verifier/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('CURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception('Helius API HTTP error: ' . $httpCode);
        }

        if (empty($response)) {
            throw new Exception('Empty response from Helius API');
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Helius API: ' . json_last_error_msg());
        }

        $nfts = $data ?? [];
        $count = count($nfts);

        error_log("Helius API found $count total NFTs for wallet: $wallet");
        
        echo json_encode([
            'success' => true,
            'nfts' => $nfts,
            'count' => $count,
            'wallet' => $wallet,
            'method' => 'helius_getNFTs',
            'timestamp' => date('c')
        ]);
    }

} catch (Exception $e) {
    error_log("Helius API error for wallet $wallet: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'wallet' => $wallet,
        'collection' => $collection,
        'timestamp' => date('c')
    ]);
}
?> 