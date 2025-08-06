<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$wallet = $_GET['wallet'] ?? '';
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

    // Prepare the RPC request payload for getTokenAccountsByOwner
    $payload = [
        "jsonrpc" => "2.0",
        "id" => 1,
        "method" => "getTokenAccountsByOwner",
        "params" => [
            $wallet,
            [
                "programId" => "TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA"
            ],
            [
                "encoding" => "jsonParsed"
            ]
        ]
    ];

    // Make the request to Solana RPC with enhanced error handling
    $ch = curl_init("https://api.mainnet-beta.solana.com");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Narrrfs-World-NFT-Verifier/1.0'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
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
        throw new Exception('Solana RPC HTTP error: ' . $httpCode);
    }

    if (empty($response)) {
        throw new Exception('Empty response from Solana RPC');
    }

    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Solana RPC: ' . json_last_error_msg());
    }

    if (isset($data['error'])) {
        $errorMessage = $data['error']['message'] ?? 'Unknown Solana RPC error';
        $errorCode = $data['error']['code'] ?? 'UNKNOWN';
        throw new Exception("Solana RPC error (Code: $errorCode): $errorMessage");
    }

    // Validate the response structure
    if (!isset($data['result']) || !isset($data['result']['value'])) {
        throw new Exception('Invalid response structure from Solana RPC');
    }

    $nfts = $data['result']['value'] ?? [];
    $count = count($nfts);

    // Log successful retrieval for debugging
    error_log("Successfully retrieved $count NFTs for wallet: $wallet");

    // Return the NFT data with enhanced information
    echo json_encode([
        'success' => true,
        'nfts' => $nfts,
        'count' => $count,
        'wallet' => $wallet,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("Solana NFT retrieval error for wallet $wallet: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'wallet' => $wallet,
        'timestamp' => date('c')
    ]);
}
?> 