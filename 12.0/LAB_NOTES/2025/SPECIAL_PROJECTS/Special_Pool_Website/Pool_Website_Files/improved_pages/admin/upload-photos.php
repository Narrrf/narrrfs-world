<?php
/**
 * Pool Website Admin - Upload Photos
 * Handles photo uploads for the pool gallery and slider
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    $uploaded_files = [];
    $errors = [];
    
    // Check if photos were uploaded (handle both formats)
    $photosKey = isset($_FILES['photos']) ? 'photos' : 'photos[]';
    if (!isset($_FILES[$photosKey]) || empty($_FILES[$photosKey]['name'][0])) {
        throw new Exception('No photos uploaded');
    }
    
    // Handle both 'photos' and 'photos[]' parameter names
    $photos = isset($_FILES['photos']) ? $_FILES['photos'] : $_FILES['photos[]'];
    $upload_dir = '../slider-photos/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Process each uploaded file
    for ($i = 0; $i < count($photos['name']); $i++) {
        if ($photos['error'][$i] === UPLOAD_ERR_OK) {
            $file_name = $photos['name'][$i];
            $file_tmp = $photos['tmp_name'][$i];
            $file_size = $photos['size'][$i];
            $file_type = $photos['type'][$i];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "File '$file_name' has invalid type. Only JPG, PNG, GIF, and WebP are allowed.";
                continue;
            }
            
            // Validate file size (max 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "File '$file_name' is too large. Maximum size is 5MB.";
                continue;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'pool_' . date('Ymd_His') . '_' . $i . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $uploaded_files[] = [
                    'original_name' => $file_name,
                    'new_name' => $new_filename,
                    'size' => $file_size,
                    'path' => $upload_path
                ];
                
                // Log successful upload
                $log_entry = date('Y-m-d H:i:s') . " - Photo uploaded: " . $new_filename . " (original: " . $file_name . ") by " . $_SESSION['admin_username'] . "\n";
                file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
            } else {
                $errors[] = "Failed to upload file '$file_name'";
            }
        } else {
            $errors[] = "Error uploading file: " . $photos['name'][$i];
        }
    }
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => count($uploaded_files) > 0,
        'uploaded_files' => $uploaded_files,
        'errors' => $errors,
        'message' => count($uploaded_files) . ' photo(s) uploaded successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Upload error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
