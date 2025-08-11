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
    // Always download from the LIVE database in /var/www/html/db/
    $liveDbPath = '/var/www/html/db/narrrf_world.sqlite';
    
    // Log the path being used for debugging
    error_log("Download API: Downloading from LIVE database: " . $liveDbPath);
    
    if (!file_exists($liveDbPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Live database not found at: ' . $liveDbPath]);
        exit;
    }
    
    // Get file size for verification
    $fileSize = filesize($liveDbPath);
    error_log("Download API: Live database file size: " . $fileSize . " bytes");
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="narrrf_world_live_database_' . date('Y-m-d') . '.sqlite"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output the LIVE database file
    readfile($liveDbPath);
    exit;
    
} catch (Exception $e) {
    error_log("Download API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Live database download failed: ' . $e->getMessage()]);
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
