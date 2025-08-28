<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Test database path detection
    $db_path = getDatabasePath();
    
    // Test if file exists
    $file_exists = file_exists($db_path);
    $file_size = $file_exists ? filesize($db_path) : 0;
    $file_readable = $file_exists ? is_readable($db_path) : false;
    $file_writable = $file_exists ? is_writable($db_path) : false;
    
    // Test database connection
    $pdo = getDatabaseConnection();
    
    // Test basic query
    $testQuery = $pdo->query("SELECT COUNT(*) as total FROM sqlite_master WHERE type='table'");
    $tableCount = $testQuery->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Test specific table
    $userQuery = $pdo->query("SELECT COUNT(*) as count FROM tbl_users");
    $userCount = $userQuery->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection test completed successfully',
        'data' => [
            'database_path' => $db_path,
            'file_exists' => $file_exists,
            'file_size' => $file_size,
            'file_readable' => $file_readable,
            'file_writable' => $file_writable,
            'connection_successful' => true,
            'total_tables' => $tableCount,
            'user_count' => $userCount,
            'test_timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection test failed: ' . $e->getMessage(),
        'data' => [
            'database_path' => getDatabasePath(),
            'file_exists' => file_exists(getDatabasePath()),
            'file_size' => file_exists(getDatabasePath()) ? filesize(getDatabasePath()) : 0,
            'test_timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ]);
}
?>
