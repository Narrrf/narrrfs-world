<?php
/**
 * Pool Website Admin - Get Photos
 * Returns list of photos for the admin dashboard
 * 
 * Created: September 28, 2025
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    $photos = getPhotosList();
    $count = count($photos);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'photos' => $photos,
        'count' => $count,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading photos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getPhotosList() {
    $photos = [];
    $assets_dir = '../slider-photos/';
    
    if (!is_dir($assets_dir)) {
        return $photos;
    }
    
    $photo_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files = scandir($assets_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Skip logo files
        if (strpos(strtolower($file), 'logo') !== false) {
            continue;
        }
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, $photo_extensions)) {
            $file_path = $assets_dir . $file;
            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
            $file_date = file_exists($file_path) ? filemtime($file_path) : time();
            
            $photos[] = [
                'filename' => $file,
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'extension' => $extension,
                'size' => $file_size,
                'size_formatted' => formatFileSize($file_size),
                'date' => date('Y-m-d H:i:s', $file_date),
                'is_pool_photo' => strpos(strtolower($file), 'pool') !== false
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($photos, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $photos;
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
