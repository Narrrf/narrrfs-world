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
        // Return graceful fallback data instead of error
        error_log("Helius API key not configured for community wallet - Key length: " . strlen($heliusApiKey));
        echo json_encode([
            'success' => true,
            'wallet' => $communityWallet,
            'balance' => [
                'sol' => 0.0,
                'lamports' => 0,
                'status' => 'api_key_missing'
            ],
            'nfts' => [
                'total' => 0,
                'list' => [],
                'collections' => [],
                'status' => 'api_key_missing'
            ],
            'timestamp' => date('c'),
            'method' => 'fallback_no_api_key',
            'note' => 'Wallet data temporarily unavailable - API key not configured'
        ]);
        exit;
    }
    
    // Initialize fallback data
    $balanceSol = 0.0;
    $balanceLamports = 0;
    $nfts = [];
    $collections = [];
    $totalNfts = 0;
    $apiMethod = 'unknown';
    
    // Try to get SOL balance using RPC endpoint
    try {
        $rpcUrl = "https://mainnet.helius-rpc.com/?api-key=$heliusApiKey";
        $balancePayload = [
            "jsonrpc" => "2.0",
            "id" => 1,
            "method" => "getBalance",
            "params" => [$communityWallet]
        ];
        
        $ch = curl_init($rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Narrrfs-World-Community-Funds/1.0'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($balancePayload));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $balanceResponse = curl_exec($ch);
        $balanceHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $balanceCurlError = curl_error($ch);
        curl_close($ch);
        
        if (!$balanceCurlError && $balanceHttpCode === 200 && !empty($balanceResponse)) {
            $balanceData = json_decode($balanceResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && !isset($balanceData['error'])) {
                $balanceLamports = $balanceData['result']['value'] ?? 0;
                $balanceSol = $balanceLamports / 1000000000;
                $apiMethod = 'helius_rpc_balance';
                error_log("Community wallet balance: $balanceSol SOL ($balanceLamports lamports)");
            }
        }
    } catch (Exception $e) {
        error_log("Balance fetch error (non-critical): " . $e->getMessage());
        // Continue with fallback data
    }
    
    // Try to get NFTs using Enhanced API
    try {
        $nftsUrl = "https://api.helius.xyz/v0/addresses/$communityWallet/nfts?api-key=$heliusApiKey";
        
        $ch = curl_init($nftsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Narrrfs-World-Community-Funds/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        $nftsResponse = curl_exec($ch);
        $nftsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $nftsCurlError = curl_error($ch);
        curl_close($ch);
        
        if (!$nftsCurlError && $nftsHttpCode === 200 && !empty($nftsResponse)) {
            $nftsData = json_decode($nftsResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && !isset($nftsData['error']) && is_array($nftsData)) {
                // Process NFTs successfully
                foreach ($nftsData as $nft) {
                    $totalNfts++;
                    
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
                    
                    if (!isset($collections[$collectionName])) {
                        $collections[$collectionName] = [
                            'name' => $collectionName,
                            'address' => $collectionAddress,
                            'count' => 0,
                            'estimated_value' => 0
                        ];
                    }
                    $collections[$collectionName]['count']++;
                    
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
                
                $apiMethod = 'helius_enhanced_api';
                error_log("Community wallet NFTs loaded: $totalNfts NFTs, " . count($collections) . " collections");
            }
        }
    } catch (Exception $e) {
        error_log("NFTs fetch error (non-critical): " . $e->getMessage());
        // Continue with fallback data
    }
    
    // Sort collections by count (highest first)
    uasort($collections, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Sort NFTs by collection name for better organization
    usort($nfts, function($a, $b) {
        return strcmp($a['collection'], $b['collection']);
    });
    
    // Always return success with available data
    echo json_encode([
        'success' => true,
        'wallet' => $communityWallet,
        'balance' => [
            'sol' => $balanceSol,
            'lamports' => $balanceLamports,
            'status' => $balanceSol > 0 ? 'active' : 'no_balance'
        ],
        'nfts' => [
            'total' => $totalNfts,
            'list' => $nfts,
            'collections' => array_values($collections),
            'status' => $totalNfts > 0 ? 'active' : 'no_nfts'
        ],
        'timestamp' => date('c'),
        'method' => $apiMethod,
        'note' => $totalNfts === 0 && $balanceSol === 0 ? 'Wallet appears empty or API temporarily unavailable' : 'Data loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Community wallet API critical error: " . $e->getMessage());
    
    // Return fallback data instead of error
    echo json_encode([
        'success' => true,
        'wallet' => $communityWallet,
        'balance' => [
            'sol' => 0.0,
            'lamports' => 0,
            'status' => 'error_fallback'
        ],
        'nfts' => [
            'total' => 0,
            'list' => [],
            'collections' => [],
            'status' => 'error_fallback'
        ],
        'timestamp' => date('c'),
        'method' => 'error_fallback',
        'note' => 'Wallet data temporarily unavailable due to technical issues'
    ]);
}
?>
