<?php
// Suppress any PHP warnings or errors that might corrupt the output
error_reporting(0);
ini_set('display_errors', 0);

// Don't set JSON headers initially - we'll set them only if there's an error
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config/database.php';

try {
    // ALWAYS use the LIVE production database path for downloads
    $liveDbPath = '/var/www/html/db/narrrf_world.sqlite';
    
    // Log the path being used for debugging
    error_log("Download API: ALWAYS downloading from LIVE production database: " . $liveDbPath);
    error_log("Download API: File exists: " . (file_exists($liveDbPath) ? 'YES' : 'NO'));
    error_log("Download API: File readable: " . (is_readable($liveDbPath) ? 'YES' : 'NO'));
    
    // Verify this is the actual live database
    if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
        $liveSize = filesize('/var/www/html/db/narrrf_world.sqlite');
        error_log("Download API: LIVE production database confirmed at /var/www/html/db/, size: " . $liveSize . " bytes");
    } else {
        error_log("Download API: WARNING - Live database not found at /var/www/html/db/");
        
        // Fallback to /data if live DB doesn't exist (should not happen in production)
        if (file_exists('/data/narrrf_world.sqlite')) {
            $liveDbPath = '/data/narrrf_world.sqlite';
            $fallbackSize = filesize($liveDbPath);
            error_log("Download API: Using fallback database at /data/, size: " . $fallbackSize . " bytes");
        }
    }
    
    if (!file_exists($liveDbPath)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Database not found at: ' . $liveDbPath]);
        exit;
    }
    
    // Get file size for verification
    $fileSize = filesize($liveDbPath);
    error_log("Download API: Live database file size: " . $fileSize . " bytes");
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="narrrfs_world_LIVE_PRODUCTION_' . date('Y-m-d') . '.sqlite"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output the LIVE database file
    readfile($liveDbPath);
    exit;
    
} catch (Exception $e) {
    error_log("Download API Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Live database download failed: ' . $e->getMessage()]);
}

?>
