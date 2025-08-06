<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$tokenAddress = $_GET['token'] ?? '';
if (!$tokenAddress) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token address']);
    exit;
}

try {
    // Validate token address format (basic check)
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $tokenAddress)) {
        throw new Exception('Invalid token address format');
    }

    // Make the request to Solscan API
    $url = "https://api.solscan.io/token/meta?tokenAddress=" . urlencode($tokenAddress);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
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
        throw new Exception('Invalid JSON response from Solscan API');
    }

    // Return the metadata
    echo json_encode([
        'success' => true,
        'metadata' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 