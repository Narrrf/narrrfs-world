<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the requested API endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Extract the API path from the URL
// URL format: /narrrfs-world/public/api-proxy.php/admin/get-all-games-stats
if (count($path_parts) >= 4 && $path_parts[3] === 'admin') {
    $api_file = $path_parts[3] . '/' . $path_parts[4] . '.php';
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid API path']);
    exit;
}

// Map to the actual API file
$api_path = '../api/' . $api_file;

if (!file_exists($api_path)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'API endpoint not found: ' . $api_file]);
    exit;
}

// Include and execute the API file
try {
    ob_start();
    include $api_path;
    $output = ob_get_clean();
    
    // Check if the output is valid JSON
    $json_data = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo $output;
    } else {
        // If not valid JSON, return error
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'API returned invalid JSON: ' . json_last_error_msg(),
            'raw_output' => substr($output, 0, 200)
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'API execution error: ' . $e->getMessage()
    ]);
}
?>
