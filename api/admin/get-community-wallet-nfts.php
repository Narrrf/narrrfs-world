<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Community wallet address
$communityWallet = '62DpHkt3h7r6CJECJtRQUoMnm3SJxUTzF5kNUGJs2325';

try {
    // Get Helius API key - using the same pattern as the working NFT verification
    $heliusApiKey = getenv('HELIUS_API_KEY') ?: $_ENV['HELIUS_API_KEY'] ?? $_SERVER['HELIUS_API_KEY'] ?? '';
    
    if (!$heliusApiKey || $heliusApiKey === 'your_helius_api_key_here' || $heliusApiKey === '') {
        // Return a graceful error instead of 500, matching the working pattern
        error_log("Helius API key not configured for community wallet - Key length: " . strlen($heliusApiKey));
        echo json_encode([
            'success' => false,
            'error' => 'Community wallet service temporarily unavailable',
            'details' => 'Helius API key not configured. Please contact support to enable wallet data.',
            'wallet' => $communityWallet,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    // Get SOL balance using RPC endpoint
    $rpcUrl = "https://mainnet.helius-rpc.com/?api-key=$heliusApiKey";
    $balancePayload = [
        "jsonrpc" => "2.0",
        "id" => 1,
        "method" => "getBalance",
        "params" => [$communityWallet]
    ];
    
    // Log the balance request for debugging
    error_log("Community wallet balance request - URL: $rpcUrl");
    
    $ch = curl_init($rpcUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Narrrfs-World-Community-Funds/1.0'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($balancePayload));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $balanceResponse = curl_exec($ch);
    $balanceHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $balanceCurlError = curl_error($ch);
    curl_close($ch);
    
    // Log the balance response for debugging
    error_log("Community wallet balance response - HTTP Code: $balanceHttpCode, Response: " . substr($balanceResponse, 0, 500));
    
    if ($balanceCurlError) {
        error_log("Community wallet balance CURL error: " . $balanceCurlError);
        throw new Exception('CURL error getting balance: ' . $balanceCurlError);
    }
    
    if ($balanceHttpCode !== 200) {
        error_log("Community wallet balance RPC error: HTTP $balanceHttpCode");
        throw new Exception('RPC error getting balance: HTTP ' . $balanceHttpCode);
    }
    
    if (empty($balanceResponse)) {
        throw new Exception('Empty response from balance RPC');
    }
    
    $balanceData = json_decode($balanceResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response for balance: ' . json_last_error_msg());
    }
    
    if (isset($balanceData['error'])) {
        error_log("Community wallet balance RPC error: " . json_encode($balanceData['error']));
        throw new Exception('RPC error getting balance: ' . json_encode($balanceData['error']));
    }
    
    // Convert lamports to SOL (1 SOL = 1,000,000,000 lamports)
    $balanceLamports = $balanceData['result']['value'] ?? 0;
    $balanceSol = $balanceLamports / 1000000000;
    
    error_log("Community wallet balance: $balanceSol SOL ($balanceLamports lamports)");
    
    // If balance is 0 or very low, log a warning
    if ($balanceSol < 0.001) {
        error_log("Warning: Community wallet has very low balance: $balanceSol SOL");
    }
    
    // Get NFTs using Enhanced API
    $nftsUrl = "https://api.helius.xyz/v0/addresses/$communityWallet/nfts?api-key=$heliusApiKey";
    
    // Log the NFTs request for debugging
    error_log("Community wallet NFTs request - URL: $nftsUrl");
    
    $ch = curl_init($nftsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Narrrfs-World-Community-Funds/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    
    $nftsResponse = curl_exec($ch);
    $nftsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $nftsCurlError = curl_error($ch);
    curl_close($ch);
    
    // Log the NFTs response for debugging
    error_log("Community wallet NFTs response - HTTP Code: $nftsHttpCode, Response: " . substr($nftsResponse, 0, 500));
    
    if ($nftsCurlError) {
        error_log("Community wallet NFTs CURL error: " . $nftsCurlError);
        throw new Exception('CURL error getting NFTs: ' . $nftsCurlError);
    }
    
    if ($nftsHttpCode !== 200) {
        error_log("Community wallet NFTs API error: HTTP $nftsHttpCode");
        throw new Exception('API error getting NFTs: HTTP ' . $nftsHttpCode);
    }
    
    if (empty($nftsResponse)) {
        throw new Exception('Empty response from NFTs API');
    }
    
    $nftsData = json_decode($nftsResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response for NFTs: ' . json_last_error_msg());
    }
    
    // Check if the API returned an error
    if (isset($nftsData['error'])) {
        error_log("Community wallet NFTs API returned error: " . json_encode($nftsData['error']));
        throw new Exception('NFTs API error: ' . json_encode($nftsData['error']));
    }
    
    // If Enhanced API fails, try RPC endpoint as fallback
    if ($nftsHttpCode !== 200) {
        error_log("Enhanced API failed (HTTP $nftsHttpCode), trying RPC endpoint...");
        
        // Fallback to RPC endpoint for NFTs
        $rpcUrl = "https://mainnet.helius-rpc.com/?api-key=$heliusApiKey";
        $rpcPayload = [
            "jsonrpc" => "2.0",
            "id" => 1,
            "method" => "getTokenAccountsByOwner",
            "params" => [
                $communityWallet,
                [
                    "programId" => "TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA"
                ],
                [
                    "encoding" => "jsonParsed"
                ]
            ]
        ];
        
        $ch = curl_init($rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Narrrfs-World-Community-Funds/1.0'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rpcPayload));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $nftsResponse = curl_exec($ch);
        $nftsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $nftsCurlError = curl_error($ch);
        curl_close($ch);
        
        if ($nftsCurlError) {
            throw new Exception('RPC fallback CURL error: ' . $nftsCurlError);
        }
        
        if ($nftsHttpCode !== 200) {
            throw new Exception('Both Enhanced API and RPC failed. Enhanced API: ' . $nftsHttpCode . ', RPC: ' . $nftsHttpCode);
        }
        
        $nftsData = json_decode($nftsResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from RPC: ' . json_last_error_msg());
        }
        
        if (isset($nftsData['error'])) {
            throw new Exception('RPC error: ' . json_encode($nftsData['error']));
        }
        
        error_log("Using RPC fallback for NFTs");
    }
    
    // Process NFTs to get collection information
    $nfts = [];
    $collections = [];
    $totalNfts = 0;
    
    if (is_array($nftsData)) {
        foreach ($nftsData as $nft) {
            $totalNfts++;
            
            // Extract collection information
            $collectionName = 'Unknown Collection';
            $collectionAddress = '';
            
            if (isset($nft['collection'])) {
                $collectionName = $nft['collection']['name'] ?? 'Unknown Collection';
                $collectionAddress = $nft['collection']['key'] ?? '';
            } elseif (isset($nft['grouping']) && is_array($nft['grouping'])) {
                foreach ($nft['grouping'] as $group) {
                    if (isset($group['groupKey']) && $group['groupKey'] === 'collection') {
                        $collectionName = $group['groupValue'] ?? 'Unknown Collection';
                        break;
                    }
                }
            }
            
            // Track collections
            if (!isset($collections[$collectionName])) {
                $collections[$collectionName] = [
                    'name' => $collectionName,
                    'address' => $collectionAddress,
                    'count' => 0,
                    'estimated_value' => 0
                ];
            }
            $collections[$collectionName]['count']++;
            
            // Add NFT to list with essential info
            $nfts[] = [
                'mint' => $nft['mint'] ?? '',
                'name' => $nft['name'] ?? 'Unknown NFT',
                'symbol' => $nft['symbol'] ?? '',
                'collection' => $collectionName,
                'collection_address' => $collectionAddress,
                'image' => $nft['image'] ?? '',
                'attributes' => $nft['attributes'] ?? [],
                'created_at' => $nft['createdAt'] ?? '',
                'updated_at' => $nft['updatedAt'] ?? ''
            ];
        }
    }
    
    // Sort collections by count (highest first)
    uasort($collections, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Sort NFTs by collection name for better organization
    usort($nfts, function($a, $b) {
        return strcmp($a['collection'], $b['collection']);
    });
    
    // Log final results for debugging
    error_log("Community wallet processing complete - Total NFTs: $totalNfts, Collections: " . count($collections));
    
    echo json_encode([
        'success' => true,
        'wallet' => $communityWallet,
        'balance' => [
            'sol' => $balanceSol,
            'lamports' => $balanceLamports
        ],
        'nfts' => [
            'total' => $totalNfts,
            'list' => $nfts,
            'collections' => array_values($collections)
        ],
        'timestamp' => date('c'),
        'method' => 'helius_enhanced_api'
    ]);
    
} catch (Exception $e) {
    error_log("Community wallet API error: " . $e->getMessage());
    
    // Return a graceful error response instead of 500, matching the working pattern
    echo json_encode([
        'success' => false,
        'error' => 'Community wallet service temporarily unavailable',
        'details' => $e->getMessage(),
        'wallet' => $communityWallet,
        'timestamp' => date('c')
    ]);
}
?>
