<?php

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
    
    // Determine upload directory based on request
    $upload_type = isset($_POST['upload_type']) ? $_POST['upload_type'] : 'slider';
    $upload_dir = ($upload_type === 'project') ? '../assets/' : '../slider-photos/';
    
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
            
            // Validate file size (max 12MB)
$max_file_size = 12 * 1024 * 1024;

if ($file_size > $max_file_size) {
    $errors[] = "File '$file_name' is too large. Maximum size is 12MB.";
    continue;
}
            
            // Generate unique filename
            // Generate unique Samuzi filename
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$prefix = ($upload_type === 'project') ? 'samuzi_project_' : 'samuzi_slider_';
$new_filename = $prefix . date('Ymd_His') . '_' . $i . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file first
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Resize image for optimal web performance
                $resize_result = resizeImageForWeb($upload_path, $upload_path, 1920, 1080, 85);
                
                if ($resize_result['success']) {
                    $uploaded_files[] = [
                        'original_name' => $file_name,
                        'new_name' => $new_filename,
                        'size' => $resize_result['new_size'],
                        'path' => $upload_path,
                        'original_size' => $file_size,
                        'resized' => true
                    ];
                    
                    // Log successful upload with resize info
                    $log_entry = date('Y-m-d H:i:s') . " - Photo uploaded and resized: " . $new_filename . " (original: " . $file_name . ", " . formatBytes($file_size) . " -> " . formatBytes($resize_result['new_size']) . ") by " . $_SESSION['admin_username'] . "\n";
                    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
                } else {
                    // Keep original if resize fails
                    $uploaded_files[] = [
                        'original_name' => $file_name,
                        'new_name' => $new_filename,
                        'size' => $file_size,
                        'path' => $upload_path,
                        'resized' => false
                    ];
                    
                    // Log upload without resize
                    $log_entry = date('Y-m-d H:i:s') . " - Photo uploaded (resize failed): " . $new_filename . " (original: " . $file_name . ") by " . $_SESSION['admin_username'] . "\n";
                    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
                }
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

/**
 * Resize image for optimal web performance
 * @param string $source Source image path
 * @param string $destination Destination image path
 * @param int $max_width Maximum width
 * @param int $max_height Maximum height
 * @param int $quality JPEG quality (1-100)
 * @return array Result with success status and new file size
 */
function resizeImageForWeb($source, $destination, $max_width = 1920, $max_height = 1080, $quality = 85) {
    try {
        // Get image info
        $image_info = getimagesize($source);
        if (!$image_info) {
            return ['success' => false, 'message' => 'Invalid image file'];
        }
        
        $original_width = $image_info[0];
        $original_height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        
        // Don't upscale images
        if ($ratio >= 1) {
            return ['success' => true, 'new_size' => filesize($source), 'message' => 'Image already optimal size'];
        }
        
        $new_width = intval($original_width * $ratio);
        $new_height = intval($original_height * $ratio);
        
        // Create image resource based on type
        switch ($mime_type) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $source_image = imagecreatefromwebp($source);
                break;
            default:
                return ['success' => false, 'message' => 'Unsupported image type'];
        }
        
        if (!$source_image) {
            return ['success' => false, 'message' => 'Failed to create image resource'];
        }
        
        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Resize image
        imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
        
        // Save resized image
        $saved = false;
        switch ($mime_type) {
            case 'image/jpeg':
                $saved = imagejpeg($new_image, $destination, $quality);
                break;
            case 'image/png':
                $saved = imagepng($new_image, $destination, 9);
                break;
            case 'image/gif':
                $saved = imagegif($new_image, $destination);
                break;
            case 'image/webp':
                $saved = imagewebp($new_image, $destination, $quality);
                break;
        }
        
        // Clean up memory
        imagedestroy($source_image);
        imagedestroy($new_image);
        
        if ($saved) {
            $new_size = filesize($destination);
            return [
                'success' => true,
                'new_size' => $new_size,
                'message' => "Resized from {$original_width}x{$original_height} to {$new_width}x{$new_height}"
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to save resized image'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Resize error: ' . $e->getMessage()];
    }
}

/**
 * Format bytes to human readable format
 * @param int $bytes Number of bytes
 * @return string Formatted string
 */
function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
