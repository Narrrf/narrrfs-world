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
    // Enhanced Solana token address validation
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $tokenAddress)) {
        throw new Exception('Invalid Solana token address format. Expected base58 encoded mint address.');
    }

    // Add rate limiting - only allow 10 requests per minute per IP
    $rateLimitFile = sys_get_temp_dir() . '/nft_metadata_rate_limit_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $currentTime = time();
    $rateLimitData = [];
    
    if (file_exists($rateLimitFile)) {
        $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    
    // Clean old entries (older than 1 minute)
    $rateLimitData = array_filter($rateLimitData, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < 60;
    });
    
    // Check if we're over the limit
    if (count($rateLimitData) >= 10) {
        throw new Exception('Rate limit exceeded. Please try again in a minute.');
    }
    
    // Add current request to rate limit
    $rateLimitData[] = $currentTime;
    file_put_contents($rateLimitFile, json_encode($rateLimitData));

    // Make the request to Solscan API with enhanced headers
    $url = "https://api.solscan.io/token/meta?tokenAddress=" . urlencode($tokenAddress);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Narrrfs-World-NFT-Verifier/1.0',
        'Accept: application/json',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: no-cache',
        'X-Requested-With: XMLHttpRequest'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('CURL error: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('Solscan API HTTP error: ' . $httpCode . ' for token: ' . $tokenAddress);
    }

    if (empty($response)) {
        throw new Exception('Empty response from Solscan API');
    }

    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Solscan API: ' . json_last_error_msg());
    }

    // Check if the response has the expected structure
    if (!isset($data['data'])) {
        // If no data, return a success response with empty data
        echo json_encode([
            'success' => true,
            'metadata' => ['data' => null],
            'message' => 'No metadata found for this token',
            'token' => $tokenAddress,
            'timestamp' => date('c')
        ]);
        exit;
    }

    // Validate metadata structure
    $metadata = $data['data'];
    if (!is_array($metadata)) {
        throw new Exception('Invalid metadata structure received from Solscan API');
    }

    // Log successful metadata retrieval for debugging
    error_log("Successfully retrieved metadata for token: $tokenAddress");

    // Return the metadata with enhanced information
    echo json_encode([
        'success' => true,
        'metadata' => $data,
        'token' => $tokenAddress,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("NFT Metadata Error for token $tokenAddress: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'token' => $tokenAddress,
        'timestamp' => date('c')
    ]);
}
?> 