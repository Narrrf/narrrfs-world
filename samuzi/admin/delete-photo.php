<?php
/**
 * Samuzi Admin - Delete Slider Artwork
 * Handles slider artwork deletion from the admin dashboard.
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    $filename = $_GET['filename'] ?? '';

    if ($filename === '') {
        throw new Exception('No filename provided');
    }

    // Security: allow only plain filenames, no path traversal.
    $filename = basename($filename);

    if ($filename === '' || $filename === '.' || $filename === '..') {
        throw new Exception('Invalid filename');
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed_extensions, true)) {
        throw new Exception('Invalid file type');
    }

    $slider_dir = realpath(__DIR__ . '/../slider-photos');

    if ($slider_dir === false || !is_dir($slider_dir)) {
        throw new Exception('Slider artwork directory not found');
    }

    $file_path = realpath($slider_dir . DIRECTORY_SEPARATOR . $filename);

    // Security: ensure resolved file path stays inside slider-photos.
    if ($file_path === false || strpos($file_path, $slider_dir . DIRECTORY_SEPARATOR) !== 0) {
        throw new Exception('File not found');
    }

    if (!is_file($file_path)) {
        throw new Exception('File not found');
    }

    if (!unlink($file_path)) {
        throw new Exception('Failed to delete file');
    }

    $admin_user = $_SESSION['admin_username'] ?? 'admin';
    $log_entry = date('Y-m-d H:i:s') . " - Slider artwork deleted: " . $filename . " by " . $admin_user . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Slider artwork deleted successfully',
        'filename' => $filename
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting slider artwork: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>