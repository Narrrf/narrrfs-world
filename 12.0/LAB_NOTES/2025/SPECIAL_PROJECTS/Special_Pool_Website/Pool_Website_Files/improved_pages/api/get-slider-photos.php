<?php
/**
 * Pool Website - Get Slider Photos
 * Returns photos for the homepage slider
 * 
 * Created: September 29, 2025
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

try {
    $sliderPhotos = getSliderPhotos();
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'photos' => $sliderPhotos,
        'count' => count($sliderPhotos),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading slider photos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getSliderPhotos() {
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
            $file_date = file_exists($file_path) ? filemtime($file_path) : time();
            
            $photos[] = [
                'filename' => $file,
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'extension' => $extension,
                'date' => date('Y-m-d H:i:s', $file_date),
                'is_pool_photo' => strpos(strtolower($file), 'pool') !== false
            ];
        }
    }
    
    // Sort by date (newest first) and prioritize pool photos
    usort($photos, function($a, $b) {
        // First sort by whether it's a pool photo
        if ($a['is_pool_photo'] && !$b['is_pool_photo']) return -1;
        if (!$a['is_pool_photo'] && $b['is_pool_photo']) return 1;
        
        // Then by date (newest first)
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $photos;
}
?>
