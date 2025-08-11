<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../auth/validate-token.php';

// Validate admin token
$token = getBearerToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

$admin = validateAdminToken($token);
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
    exit;
}

try {
    // Always use the correct production database path
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
    
    // Log the path being used for debugging
    error_log("Download API: Attempting to download from: " . $dbPath);
    
    if (!file_exists($dbPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Database file not found at: ' . $dbPath]);
        exit;
    }
    
    // Get file size for verification
    $fileSize = filesize($dbPath);
    error_log("Download API: Database file size: " . $fileSize . " bytes");
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="narrrf_world_database_' . date('Y-m-d') . '.sqlite"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output the database file
    readfile($dbPath);
    exit;
    
} catch (Exception $e) {
    error_log("Download API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database download failed: ' . $e->getMessage()]);
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>
