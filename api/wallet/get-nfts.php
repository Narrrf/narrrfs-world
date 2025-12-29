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
    
    // LOCAL DEVELOPMENT: Check for local config file (only on localhost)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
    
    if (empty($heliusApiKey) && $isLocalhost) {
        // Try api/config/helius-api-key.php (relative to this file)
        $localConfigPath = __DIR__ . '/../config/helius-api-key.php';
        if (file_exists($localConfigPath)) {
            include $localConfigPath;
            if (isset($HELIUS_API_KEY) && $HELIUS_API_KEY !== 'YOUR_HELIUS_API_KEY_HERE' && !empty($HELIUS_API_KEY)) {
                $heliusApiKey = $HELIUS_API_KEY;
                error_log("✅ [get-nfts.php] Helius API key loaded from local config file");
            }
        }
    }
    
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
    
    // Helper function to check if NFT name matches Narrrf collections
    $isNarrrfNFT = function($name) {
        if (empty($name)) return false;
        $nameLower = strtolower($name);
        return strpos($nameLower, 'narrrf') !== false || strpos($nameLower, 'narrrfs') !== false;
    };
    
    if (!empty($collection)) {
        // Try Enhanced Solana API first with collection filtering, fallback to name filtering if needed
        // Enhanced API supports collection filtering via query parameter
        $url = "https://api.helius.xyz/v0/addresses/$wallet/nfts?api-key=$heliusApiKey&collection=$collection";
        
        // Log the request for debugging
        error_log("Helius Enhanced API request (with collection filter) - URL: " . str_replace($heliusApiKey, '***', $url));
        
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
        error_log("Helius Enhanced API response (with collection filter) - HTTP Code: $httpCode, Response length: " . strlen($response));
        
        $enhancedApiWorked = false;
        $nfts = [];
        
        if ($httpCode === 200 && !$curlError) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                error_log("Helius Enhanced API success (with collection filter) - Found " . count($data) . " NFTs");
                
                // Process Enhanced API response
                foreach ($data as $nft) {
                    // Enhanced API already filters by collection, but verify anyway
                    $nftCollection = $nft['collection'] ?? $nft['collectionAddress'] ?? $nft['grouping'] ?? '';
                    $nftName = $nft['content']['metadata']['name'] ?? $nft['title'] ?? 'Unnamed NFT';
                    
                    // Check if collection matches OR name contains "Narrrf"
                    $matchesCollection = empty($collection) || $nftCollection === $collection || 
                        (isset($nft['grouping']) && is_array($nft['grouping']) && 
                         isset($nft['grouping'][0]['group_value']) && 
                         $nft['grouping'][0]['group_value'] === $collection);
                    
                    $matchesName = $isNarrrfNFT($nftName);
                    
                    if ($matchesCollection || $matchesName) {
                        $formattedNft = [
                            'mint' => $nft['mint'] ?? $nft['id'] ?? '',
                            'name' => $nftName,
                            'description' => $nft['content']['metadata']['description'] ?? '',
                            'image' => $nft['content']['files'][0]['uri'] ?? $nft['content']['files'][0]['cdn_uri'] ?? '',
                            'attributes' => $nft['content']['metadata']['attributes'] ?? [],
                            'metadataUri' => $nft['content']['json_uri'] ?? '',
                            'collection' => $nftCollection ?: $collection,
                            'collectionAddress' => $collection
                        ];
                        
                        $nfts[] = $formattedNft;
                    }
                }
                
                error_log("Helius Enhanced API - Returning " . count($nfts) . " NFTs after collection/name filtering");
                $enhancedApiWorked = true;
            }
        }
        
        // If Enhanced API with collection filter returned 0 NFTs, try without collection filter but with name filter
        if (!$enhancedApiWorked || count($nfts) === 0) {
            error_log("Enhanced API with collection filter returned 0 NFTs, trying without collection filter (name-based filtering)...");
            
            // Try Enhanced API without collection filter - fetch all NFTs and filter by name
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
            
            if ($httpCode === 200 && !$curlError) {
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    error_log("Helius Enhanced API (without collection filter) - Found " . count($data) . " total NFTs, filtering by name 'Narrrf'...");
                    
                    // Filter by name containing "Narrrf" or "Narrrfs"
                    $nfts = [];
                    foreach ($data as $nft) {
                        $nftName = $nft['content']['metadata']['name'] ?? $nft['title'] ?? 'Unnamed NFT';
                        
                        if ($isNarrrfNFT($nftName)) {
                            $nftCollection = $nft['collection'] ?? $nft['collectionAddress'] ?? $nft['grouping'] ?? '';
                            
                            $formattedNft = [
                                'mint' => $nft['mint'] ?? $nft['id'] ?? '',
                                'name' => $nftName,
                                'description' => $nft['content']['metadata']['description'] ?? '',
                                'image' => $nft['content']['files'][0]['uri'] ?? $nft['content']['files'][0]['cdn_uri'] ?? '',
                                'attributes' => $nft['content']['metadata']['attributes'] ?? [],
                                'metadataUri' => $nft['content']['json_uri'] ?? '',
                                'collection' => $nftCollection ?: $collection,
                                'collectionAddress' => $collection
                            ];
                            
                            $nfts[] = $formattedNft;
                        }
                    }
                    
                    error_log("Helius Enhanced API (name filter) - Returning " . count($nfts) . " NFTs matching 'Narrrf' name");
                    
                    if (count($nfts) > 0) {
                        echo json_encode([
                            'success' => true,
                            'nfts' => $nfts,
                            'count' => count($nfts),
                            'wallet' => $wallet,
                            'collection' => $collection,
                            'method' => 'helius_enhanced_api_name_filter',
                            'has_assets' => true,
                            'timestamp' => date('c')
                        ]);
                        exit;
                    }
                }
            }
        } else {
            // Enhanced API with collection filter worked and returned NFTs
            echo json_encode([
                'success' => true,
                'nfts' => $nfts,
                'count' => count($nfts),
                'wallet' => $wallet,
                'collection' => $collection,
                'method' => 'helius_enhanced_api',
                'has_assets' => count($nfts) > 0,
                'timestamp' => date('c')
            ]);
            exit;
        }
        
        // If Enhanced API fails completely, try getAssetsByOwner with pagination (like working project)
        if ($httpCode !== 200 || $curlError) {
            error_log("Enhanced API failed (HTTP $httpCode), trying getAssetsByOwner with pagination...");
            
            // Use getAssetsByOwner method (like the working project) - this gets ALL NFTs with pagination
            $rpcUrl = "https://mainnet.helius-rpc.com/?api-key=$heliusApiKey";
            $allAssets = [];
            $page = 1;
            $limit = 1000;
            $hasMore = true;
            
            while ($hasMore) {
                $rpcPayload = [
                    "jsonrpc" => "2.0",
                    "id" => "narrrfs-nft-fetch",
                    "method" => "getAssetsByOwner",
                    "params" => [
                        "ownerAddress" => $wallet,
                        "page" => $page,
                        "limit" => $limit,
                        "displayOptions" => [
                            "showFungible" => false,
                            "showNativeBalance" => false
                        ]
                    ]
                ];
                
                $ch = curl_init($rpcUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'User-Agent: Narrrfs-World-NFT-Verifier/1.0'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rpcPayload));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    error_log("RPC getAssetsByOwner CURL error on page $page: " . $curlError);
                    break;
                }
                
                if ($httpCode !== 200) {
                    error_log("RPC getAssetsByOwner HTTP error on page $page: $httpCode");
                    break;
                }
                
                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Invalid JSON response from getAssetsByOwner on page $page: " . json_last_error_msg());
                    break;
                }
                
                if (isset($data['error'])) {
                    error_log("RPC getAssetsByOwner error on page $page: " . json_encode($data['error']));
                    break;
                }
                
                $items = $data['result']['items'] ?? [];
                error_log("Helius getAssetsByOwner page $page: fetched " . count($items) . " NFTs (total so far: " . (count($allAssets) + count($items)) . ")");
                
                $allAssets = array_merge($allAssets, $items);
                
                // Continue to next page if we got a full page (1000 items)
                $hasMore = (count($items) === $limit);
                if ($hasMore) {
                    $page++;
                    // Small delay between pages to avoid rate limiting
                    usleep(200000); // 200ms delay
                }
            }
            
            error_log("Helius getAssetsByOwner total: " . count($allAssets) . " NFTs fetched across $page pages");
            
            // Process all assets and filter by collection or name
            $nfts = [];
            foreach ($allAssets as $index => $asset) {
                // Extract collection from grouping field (like working project)
                $grouping = $asset['grouping'] ?? [];
                $collectionKey = null;
                
                // Find collection in grouping array
                foreach ($grouping as $group) {
                    if (isset($group['group_key']) && $group['group_key'] === 'collection') {
                        $collectionKey = $group['group_value'] ?? null;
                        break;
                    }
                }
                
                // Fallback: try other collection locations
                if (empty($collectionKey)) {
                    $collectionKey = $asset['collection'] ?? 
                                    $asset['collectionAddress'] ?? 
                                    null;
                }
                
                // Extract NFT data
                $mint = $asset['id'] ?? $asset['mint'] ?? '';
                $name = $asset['content']['metadata']['name'] ?? 
                       $asset['content']['metadata']['title'] ?? 
                       $asset['title'] ?? 
                       'Unnamed NFT';
                
                $description = $asset['content']['metadata']['description'] ?? '';
                $imageUrl = $asset['content']['links']['image'] ?? 
                           $asset['content']['files'][0]['uri'] ?? 
                           $asset['content']['files'][0]['cdn_uri'] ?? 
                           '';
                $attributes = $asset['content']['metadata']['attributes'] ?? [];
                $metadataUri = $asset['content']['json_uri'] ?? '';
                
                // DEBUG: Log first 5 NFTs
                if ($index < 5) {
                    error_log("DEBUG NFT #$index (getAssetsByOwner):");
                    error_log("  - Name: $name");
                    error_log("  - Collection Key (from grouping): " . ($collectionKey ?? 'NULL'));
                    error_log("  - Requested Collection: $collection");
                    error_log("  - Matches Name Filter: " . ($isNarrrfNFT($name) ? 'YES' : 'NO'));
                    if (empty($collectionKey) && $index === 0) {
                        error_log("  - Full grouping structure: " . json_encode($grouping));
                    }
                }
                
                // Filter by collection OR name containing "Narrrf"
                $matchesCollection = empty($collection) || ($collectionKey && strtolower($collectionKey) === strtolower($collection));
                $matchesName = $isNarrrfNFT($name);
                
                if (!empty($collection)) {
                    if (!$matchesCollection && !$matchesName) {
                        // Only log first 3 mismatches to avoid spam
                        if ($index < 3) {
                            error_log("DEBUG: Skipping NFT #$index: $name (collection mismatch and name doesn't contain 'Narrrf'. Expected: $collection, Found: " . ($collectionKey ?? 'NULL') . ")");
                        }
                        continue; // Skip this NFT - doesn't belong to requested collection and name doesn't match
                    }
                    
                    // Log if matched by name instead of collection
                    if (!$matchesCollection && $matchesName && $index < 3) {
                        error_log("DEBUG: NFT #$index matched by name (contains 'Narrrf'): $name");
                    }
                }
                
                $formattedNft = [
                    'mint' => $mint,
                    'name' => $name,
                    'description' => $description,
                    'image' => $imageUrl,
                    'attributes' => $attributes,
                    'metadataUri' => $metadataUri,
                    'collection' => $collectionKey ?: $collection,
                    'collectionAddress' => $collection
                ];
                
                $nfts[] = $formattedNft;
            }
            
            error_log("Helius getAssetsByOwner - Returning " . count($nfts) . " NFTs (ALL NFTs - NO FILTERING for debugging)");
            
            // DEBUG: Add debug info to response
            $debugInfo = [
                'total_assets_fetched' => count($allAssets),
                'pages_fetched' => $page,
                'nfts_with_metadata' => count($nfts)
            ];
            
            echo json_encode([
                'success' => true,
                'nfts' => $nfts,
                'count' => count($nfts),
                'wallet' => $wallet,
                'collection' => $collection,
                'method' => 'helius_getassetsbyowner_pagination',
                'has_assets' => count($nfts) > 0,
                'timestamp' => date('c'),
                'debug' => $debugInfo
            ]);
            return;
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
                
                // DEBUG: Log the NFT data to see what we're getting
                error_log("DEBUG NFT: " . json_encode([
                    'mint' => $nft['mint'] ?? 'unknown',
                    'collection' => $nftCollection,
                    'grouping' => $nft['grouping'] ?? 'none',
                    'collectionAddress' => $nft['collectionAddress'] ?? 'none'
                ]));
                
                // If grouping is an array, check for collection key
                if (is_array($nftCollection)) {
                    foreach ($nftCollection as $group) {
                        if (isset($group['groupKey']) && $group['groupKey'] === 'collection') {
                            $nftCollection = $group['groupValue'] ?? '';
                            break;
                        }
                    }
                }
                
                // DEBUG: Log the final collection check
                error_log("DEBUG Collection Check: Expected '$collection', Found '$nftCollection', Match: " . ($nftCollection === $collection ? 'YES' : 'NO'));
                
                if ($nftCollection === $collection) {
                    // Extract and format NFT data for display
                    $formattedNft = [
                        'mint' => $nft['mint'] ?? '',
                        'name' => $nft['content']['metadata']['name'] ?? $nft['name'] ?? 'Unnamed NFT',
                        'description' => $nft['content']['metadata']['description'] ?? $nft['description'] ?? '',
                        // Extract image URL from multiple possible locations
                        'image' => $nft['content']['links']['image'] ?? 
                                  $nft['content']['files'][0]['uri'] ?? 
                                  $nft['content']['files'][0]['cdn_uri'] ?? 
                                  $nft['image'] ?? 
                                  '',
                        // Extract attributes/traits
                        'attributes' => $nft['content']['metadata']['attributes'] ?? 
                                      $nft['attributes'] ?? 
                                      $nft['content']['metadata']['properties']['attributes'] ?? 
                                      [],
                        // Keep original data for reference
                        'content' => $nft['content'] ?? null,
                        'collection' => $nftCollection,
                        'collectionAddress' => $collection
                    ];
                    $nfts[] = $formattedNft;
                }
            }
        }
        
        $count = count($nfts);
        error_log("Helius API found $count NFTs for wallet: $wallet in collection: $collection");
        
        // DEBUG: Log all NFTs found for this wallet (first 5 for debugging)
        if ($count > 0) {
            error_log("DEBUG: First 5 NFTs found for wallet $wallet:");
            for ($i = 0; $i < min(5, $count); $i++) {
                $nft = $nfts[$i];
                error_log("DEBUG NFT $i: " . json_encode([
                    'mint' => $nft['mint'] ?? 'unknown',
                    'name' => $nft['name'] ?? 'unknown',
                    'collection' => $nft['collection'] ?? 'none',
                    'collectionAddress' => $nft['collectionAddress'] ?? 'none',
                    'grouping' => $nft['grouping'] ?? 'none'
                ]));
            }
        }
        
        // Return minimal data - just what we need for role assignment
        echo json_encode([
            'success' => true,
            'nfts' => $nfts,
            'count' => $count,
            'wallet' => $wallet,
            'collection' => $collection,
            'method' => 'helius_enhanced_api',
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