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
    // Check if database file exists - use correct path for production
    $dbPath = '/data/narrrf_world.sqlite';
    if (!file_exists($dbPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Database file not found at: ' . $dbPath]);
        exit;
    }
    
    // Connect to database
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get database information
    $dbInfo = [
        'size' => formatBytes(filesize($dbPath)),
        'last_modified' => date('Y-m-d H:i:s', filemtime($dbPath)),
        'version' => $pdo->query('SELECT sqlite_version()')->fetchColumn()
    ];
    
    // Get list of tables
    $tables = [];
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tableNames as $tableName) {
        $tableInfo = [
            'name' => $tableName,
            'columns' => [],
            'row_count' => null
        ];
        
        // Get table schema
        $schemaStmt = $pdo->query("PRAGMA table_info($tableName)");
        $columns = $schemaStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            $tableInfo['columns'][] = $column['name'] . ' (' . $column['type'] . ')';
        }
        
        // Get row count (for smaller tables only to avoid performance issues)
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM $tableName");
            $tableInfo['row_count'] = $countStmt->fetchColumn();
        } catch (Exception $e) {
            $tableInfo['row_count'] = 'Error counting';
        }
        
        $tables[] = $tableInfo;
    }
    
    // Get database statistics
    $stats = [
        'total_tables' => count($tables),
        'total_size' => $dbInfo['size'],
        'database_path' => $dbPath
    ];
    
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'info' => $dbInfo,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to get database structure: ' . $e->getMessage()]);
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

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
