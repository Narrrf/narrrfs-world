<?php
/**
 * Pool Website Admin - Delete Photo
 * Handles photo deletion from the admin dashboard
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
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only DELETE requests are accepted.'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    // Get filename from query parameter
    $filename = $_GET['filename'] ?? null;
    
    if (empty($filename)) {
        throw new Exception('No filename provided');
    }
    
    // Validate filename (security check)
    $filename = basename($filename); // Remove any path traversal attempts
    
    $file_path = '../slider-photos/' . $filename;
    
    // Check if file exists
    if (!file_exists($file_path)) {
        throw new Exception('File not found');
    }
    
    // Delete the file
    if (unlink($file_path)) {
        // Log successful deletion
        $log_entry = date('Y-m-d H:i:s') . " - Photo deleted: " . $filename . " by " . ($_SESSION['admin_username'] ?? 'unknown') . "\n";
        file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
        
        ob_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Photo deleted successfully',
            'filename' => $filename
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        
    } else {
        throw new Exception('Failed to delete file');
    }
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting photo: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
