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
    // Validate wallet address format (basic check)
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet)) {
        throw new Exception('Invalid wallet address format');
    }

    // Prepare the RPC request payload
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

    // Make the request to Solana RPC
    $ch = curl_init("https://api.mainnet-beta.solana.com");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('CURL error: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('HTTP error: ' . $httpCode);
    }

    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Solana RPC');
    }

    if (isset($data['error'])) {
        throw new Exception('Solana RPC error: ' . ($data['error']['message'] ?? 'Unknown error'));
    }

    // Return the NFT data
    echo json_encode([
        'success' => true,
        'nfts' => $data['result']['value'] ?? [],
        'count' => count($data['result']['value'] ?? [])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 