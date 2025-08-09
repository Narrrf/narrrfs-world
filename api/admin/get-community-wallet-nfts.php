<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Community wallet address
$communityWallet = '62DpHkt3h7r6CJECjtRQUoMnm3SJxUTzF5kNUGjs2325';

try {
    // Get Helius API key
    $heliusApiKey = getenv('HELIUS_API_KEY') ?: $_ENV['HELIUS_API_KEY'] ?? $_SERVER['HELIUS_API_KEY'] ?? '';
    
    if (!$heliusApiKey || $heliusApiKey === 'your_helius_api_key_here' || $heliusApiKey === '') {
        throw new Exception('Helius API key not configured');
    }
    
    // Get SOL balance using RPC endpoint
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $balanceResponse = curl_exec($ch);
    $balanceHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $balanceCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($balanceCurlError) {
        throw new Exception('CURL error getting balance: ' . $balanceCurlError);
    }
    
    if ($balanceHttpCode !== 200) {
        throw new Exception('RPC error getting balance: HTTP ' . $balanceHttpCode);
    }
    
    $balanceData = json_decode($balanceResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response for balance: ' . json_last_error_msg());
    }
    
    if (isset($balanceData['error'])) {
        throw new Exception('RPC error getting balance: ' . json_encode($balanceData['error']));
    }
    
    // Convert lamports to SOL (1 SOL = 1,000,000,000 lamports)
    $balanceLamports = $balanceData['result']['value'] ?? 0;
    $balanceSol = $balanceLamports / 1000000000;
    
    // Get NFTs using Enhanced API
    $nftsUrl = "https://api.helius.xyz/v0/addresses/$communityWallet/nfts?api-key=$heliusApiKey";
    
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
    
    if ($nftsCurlError) {
        throw new Exception('CURL error getting NFTs: ' . $nftsCurlError);
    }
    
    if ($nftsHttpCode !== 200) {
        throw new Exception('API error getting NFTs: HTTP ' . $nftsHttpCode);
    }
    
    $nftsData = json_decode($nftsResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response for NFTs: ' . json_last_error_msg());
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'wallet' => $communityWallet,
        'timestamp' => date('c')
    ]);
}
?>
