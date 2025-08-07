<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Test Helius API key and connectivity
try {
    $heliusApiKey = getenv('HELIUS_API_KEY') ?: $_ENV['HELIUS_API_KEY'] ?? $_SERVER['HELIUS_API_KEY'] ?? '';
    
    $response = [
        'success' => false,
        'api_key_status' => 'unknown',
        'api_key_length' => 0,
        'api_key_preview' => '',
        'test_results' => [],
        'environment_info' => []
    ];
    
    // Check if API key is configured
    if (!$heliusApiKey || $heliusApiKey === 'your_helius_api_key_here' || $heliusApiKey === '') {
        $response['api_key_status'] = 'not_configured';
        $response['error'] = 'Helius API key not configured';
        $response['environment_info']['getenv_result'] = $heliusApiKey ? 'found' : 'not_found';
        $response['environment_info']['key_length'] = strlen($heliusApiKey);
    } else {
        $response['api_key_status'] = 'configured';
        $response['api_key_length'] = strlen($heliusApiKey);
        $response['api_key_preview'] = substr($heliusApiKey, 0, 8) . '...';
        
        // Test API connectivity with a simple request
        $testWallet = '11111111111111111111111111111112'; // Test wallet address
        
        // Test 1: Enhanced Solana API endpoint
        $apiUrl = "https://api.helius.xyz/v0/addresses/$testWallet/nfts?api-key=$heliusApiKey";
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Narrrfs-World-NFT-Verifier/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $apiResponse = curl_exec($ch);
        $apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $apiCurlError = curl_error($ch);
        curl_close($ch);
        
        // Test 2: RPC endpoint (for comparison)
        $rpcUrl = "https://mainnet.helius-rpc.com/?api-key=$heliusApiKey";
        $rpcPayload = json_encode([
            "jsonrpc" => "2.0",
            "id" => 1,
            "method" => "getBalance",
            "params" => [$testWallet]
        ]);
        
        $ch = curl_init($rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Narrrfs-World-NFT-Verifier/1.0'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rpcPayload);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $rpcResponse = curl_exec($ch);
        $rpcHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $rpcCurlError = curl_error($ch);
        curl_close($ch);
        
        $response['test_results']['api_test'] = [
            'http_code' => $apiHttpCode,
            'curl_error' => $apiCurlError ?: 'none',
            'response_preview' => substr($apiResponse, 0, 200),
            'url_tested' => $apiUrl
        ];
        
        $response['test_results']['rpc_test'] = [
            'http_code' => $rpcHttpCode,
            'curl_error' => $rpcCurlError ?: 'none',
            'response_preview' => substr($rpcResponse, 0, 200),
            'url_tested' => $rpcUrl
        ];
        
        if ($apiHttpCode === 200) {
            $response['success'] = true;
            $response['test_results']['status'] = 'api_working';
        } else {
            $response['test_results']['status'] = 'api_error';
            $response['error'] = "API returned HTTP $apiHttpCode";
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
