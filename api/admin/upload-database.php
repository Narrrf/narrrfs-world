<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
    // Check if file was uploaded
    if (!isset($_FILES['database']) || $_FILES['database']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No database file uploaded or upload error']);
        exit;
    }
    
    $uploadedFile = $_FILES['database'];
    
    // Validate file type
    $allowedTypes = ['application/octet-stream', 'application/x-sqlite3', 'application/vnd.sqlite3'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $uploadedFile['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($mimeType, $allowedTypes) && !preg_match('/\.(sqlite|db)$/i', $uploadedFile['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only SQLite database files are allowed.']);
        exit;
    }
    
    // Validate file size (max 100MB)
    $maxSize = 100 * 1024 * 1024; // 100MB
    if ($uploadedFile['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 100MB.']);
        exit;
    }
    
    // Get the correct database path based on environment
    $currentDbPath = getDatabasePath();
    
    // Create backup of current database before replacing
    $backupDir = '/data/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Generate backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backupFilename = "narrrf_world_before_upload_{$timestamp}.sqlite";
    $backupPath = $backupDir . '/' . $backupFilename;
    
    if (file_exists($currentDbPath)) {
        if (!copy($currentDbPath, $backupPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to create backup of current database']);
            exit;
        }
    }
    
    // Set target paths - upload to both production and /data for persistence
    $productionPath = '/var/www/html/db/narrrf_world.sqlite';
    $dataPath = '/data/narrrf_world.sqlite';
    
    // Ensure the production db directory exists
    $productionDir = dirname($productionPath);
    if (!is_dir($productionDir)) {
        mkdir($productionDir, 0755, true);
    }
    
    // First, upload to production path
    if (move_uploaded_file($uploadedFile['tmp_name'], $productionPath)) {
        // Set proper permissions
        chmod($productionPath, 0644);
        
        // Verify the uploaded file is a valid SQLite database
        try {
            $pdo = new PDO("sqlite:$productionPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test a simple query
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                // Restore backup if uploaded file is invalid
                if (file_exists($backupPath)) {
                    copy($backupPath, $productionPath);
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Uploaded file is not a valid SQLite database']);
                exit;
            }
            
            // Now copy to /data for persistence
            if (copy($productionPath, $dataPath)) {
                chmod($dataPath, 0644);
                echo json_encode([
                    'success' => true,
                    'message' => 'Database uploaded successfully to production and /data',
                    'tables_count' => count($tables),
                    'backup_created' => file_exists($backupPath),
                    'backup_path' => $backupPath,
                    'production_path' => $productionPath,
                    'data_path' => $dataPath
                ]);
            } else {
                // Production upload succeeded but /data copy failed
                echo json_encode([
                    'success' => true,
                    'message' => 'Database uploaded to production but failed to copy to /data',
                    'tables_count' => count($tables),
                    'backup_created' => file_exists($backupPath),
                    'backup_path' => $backupPath,
                    'production_path' => $productionPath,
                    'warning' => 'Failed to copy to /data for persistence'
                ]);
            }
            
        } catch (PDOException $e) {
            // Restore backup if database is corrupted
            if (file_exists($backupPath)) {
                copy($backupPath, $productionPath);
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Uploaded file is corrupted or not a valid SQLite database']);
        }
        
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file to production']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database upload failed: ' . $e->getMessage()]);
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
?>
