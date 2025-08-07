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
    // Try multiple ways to read the environment variable
    $heliusApiKey = getenv('HELIUS_API_KEY') ?: $_ENV['HELIUS_API_KEY'] ?? $_SERVER['HELIUS_API_KEY'] ?? '';
    
    if (!$heliusApiKey || $heliusApiKey === 'your_helius_api_key_here' || $heliusApiKey === '') {
        // Return a graceful error instead of 500
        error_log("Helius API key not configured for NFT verification - Key length: " . strlen($heliusApiKey));
        echo json_encode([
            'success' => false,
            'error' => 'NFT verification service temporarily unavailable',
            'details' => 'Helius API key not configured. Please contact support to enable NFT verification.',
            'count' => 0,
            'has_assets' => false,
            'method' => 'fallback',
            'wallet' => $wallet,
            'collection' => $collection
        ]);
        exit;
    }
    
    if (!empty($collection)) {
        // Use the correct Helius API endpoint for searching assets
        // Based on Helius documentation: https://docs.helius.xyz/reference/search-assets
        $url = "https://api.helius.xyz/v0/addresses/$wallet/nfts?api-key=$heliusApiKey";
        
        // Log the request for debugging
        error_log("Helius API request - URL: $url");
        
        // Make GET request for NFTs
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
        
        // Log the response for debugging
        error_log("Helius API response - HTTP Code: $httpCode, Response: " . substr($response, 0, 500));
        
        if ($curlError) {
            error_log("Helius API CURL error: " . $curlError);
            throw new Exception('CURL error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            // Log the actual response for debugging
            error_log("Helius API error response (HTTP $httpCode): " . $response);
            throw new Exception('Helius API HTTP error: ' . $httpCode . ' - Response: ' . substr($response, 0, 200));
        }
        
        if (empty($response)) {
            throw new Exception('Empty response from Helius API');
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Helius API: ' . json_last_error_msg());
        }
        
        // Check if the API returned an error
        if (isset($data['error'])) {
            error_log("Helius API returned error: " . json_encode($data['error']));
            throw new Exception('Helius API error: ' . json_encode($data['error']));
        }
        
        // Filter for the specific collection
        $nfts = [];
        if (is_array($data)) {
            foreach ($data as $nft) {
                // Check if NFT belongs to the specified collection
                // Try different possible field names for collection
                $nftCollection = $nft['collection'] ?? $nft['collectionAddress'] ?? $nft['grouping'] ?? '';
                
                // If grouping is an array, check for collection key
                if (is_array($nftCollection)) {
                    foreach ($nftCollection as $group) {
                        if (isset($group['groupKey']) && $group['groupKey'] === 'collection') {
                            $nftCollection = $group['groupValue'] ?? '';
                            break;
                        }
                    }
                }
                
                if ($nftCollection === $collection) {
                    $nfts[] = $nft;
                }
            }
        }
        
        $count = count($nfts);
        error_log("Helius API found $count NFTs for wallet: $wallet in collection: $collection");
        
        // Return minimal data - just what we need for role assignment
        echo json_encode([
            'success' => true,
            'nfts' => $nfts,
            'count' => $count,
            'wallet' => $wallet,
            'collection' => $collection,
            'method' => 'helius_getNFTs',
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