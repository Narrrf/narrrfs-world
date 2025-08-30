<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Simple database upload without authentication for local development
// This endpoint is designed for local testing and development use

try {
    // Check if file was uploaded
    if (!isset($_FILES['database']) || $_FILES['database']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No database file uploaded';
        if (isset($_FILES['database'])) {
            switch ($_FILES['database']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg = 'File exceeds upload_max_filesize in php.ini';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = 'File exceeds MAX_FILE_SIZE in HTML form';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = 'No file was uploaded';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = 'Missing temporary folder';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = 'Failed to write file to disk';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMsg = 'A PHP extension stopped the file upload';
                    break;
                default:
                    $errorMsg = 'Unknown upload error: ' . $_FILES['database']['error'];
            }
        }
        
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }
    
    $uploadedFile = $_FILES['database'];
    
    // Log upload attempt for debugging
    error_log("Database upload attempt: " . $uploadedFile['name'] . " (Size: " . $uploadedFile['size'] . " bytes)");
    
    // Validate file type - accept SQLite files
    $allowedExtensions = ['sqlite', 'db', 'sqlite3'];
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only SQLite database files (.sqlite, .db, .sqlite3) are allowed.']);
        exit;
    }
    
    // Validate file size (max 100MB)
    $maxSize = 100 * 1024 * 1024; // 100MB
    if ($uploadedFile['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 100MB.']);
        exit;
    }
    
    // Set target database path - use production path for live server
    $isProduction = !(PHP_OS_FAMILY === 'Windows' || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false);
    
    error_log('Environment detection - PHP_OS_FAMILY: ' . PHP_OS_FAMILY);
    error_log('Environment detection - DOCUMENT_ROOT: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET'));
    error_log('Environment detection - isProduction: ' . ($isProduction ? 'YES' : 'NO'));
    
    if ($isProduction) {
        // Production - upload to both production and /data for persistence
        $targetPath = '/var/www/html/db/narrrf_world.sqlite';
        $dataPath = '/data/narrrf_world.sqlite';
        $backupDir = '/data/backups';
        error_log('Production paths - target: ' . $targetPath . ', data: ' . $dataPath . ', backup: ' . $backupDir);
    } else {
        // Local development
        $targetPath = __DIR__ . '/../../db/narrrf_world.sqlite';
        $dataPath = null;
        $backupDir = __DIR__ . '/../../db/backups';
        error_log('Local paths - target: ' . $targetPath . ', backup: ' . $backupDir);
    }
    
    $targetDir = dirname($targetPath);
    
    // Ensure the db directory exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Create backup of current database if it exists
    $backupPath = null;
    if (file_exists($targetPath)) {
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFilename = "narrrf_world_backup_{$timestamp}.sqlite";
        $backupPath = $backupDir . '/' . $backupFilename;
        
        if (!copy($targetPath, $backupPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to create backup of current database']);
            exit;
        }
    }
    
    // Move uploaded file to target location
    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        // Set proper permissions
        chmod($targetPath, 0644);
        
        // Verify the uploaded file is a valid SQLite database
        try {
            $pdo = new PDO("sqlite:$targetPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test a simple query
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                // Restore backup if uploaded file is invalid
                if ($backupPath && file_exists($backupPath)) {
                    copy($backupPath, $targetPath);
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Uploaded file is not a valid SQLite database']);
                exit;
            }
            
            // Success! Now copy to /data for persistence if on production
            $dataCopySuccess = false;
            if ($isProduction && $dataPath) {
                if (copy($targetPath, $dataPath)) {
                    chmod($dataPath, 0644);
                    $dataCopySuccess = true;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => $isProduction ? 'Database uploaded successfully to production and /data!' : 'Database uploaded successfully!',
                'tables_count' => count($tables),
                'tables' => $tables,
                'backup_created' => $backupPath ? file_exists($backupPath) : false,
                'backup_path' => $backupPath,
                'target_path' => $targetPath,
                'data_path' => $dataPath,
                'data_copy_success' => $dataCopySuccess,
                'file_size' => filesize($targetPath),
                'upload_info' => [
                    'original_name' => $uploadedFile['name'],
                    'uploaded_size' => $uploadedFile['size'],
                    'mime_type' => $uploadedFile['type']
                ]
            ]);
            
        } catch (PDOException $e) {
            // Restore backup if database is corrupted
            if ($backupPath && file_exists($backupPath)) {
                copy($backupPath, $targetPath);
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Uploaded file is corrupted or not a valid SQLite database: ' . $e->getMessage()]);
        }
        
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file to target location']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database upload failed: ' . $e->getMessage()]);
}
?>
