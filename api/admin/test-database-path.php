<?php
// Suppress any PHP warnings or errors that might corrupt the output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

try {
    $dbPath = getDatabasePath();
    $fileExists = file_exists($dbPath);
    $fileSize = $fileExists ? filesize($dbPath) : 'N/A';
    $isReadable = $fileExists ? is_readable($dbPath) : false;
    
    // Environment detection
    $is_local = PHP_OS_FAMILY === 'Windows' || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false;
    
    echo json_encode([
        'success' => true,
        'database_path' => $dbPath,
        'file_exists' => $fileExists,
        'file_size' => $fileSize,
        'is_readable' => $isReadable,
        'environment' => $is_local ? 'Local Development' : 'Production',
        'php_os' => PHP_OS_FAMILY,
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET',
        'current_user' => get_current_user(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
