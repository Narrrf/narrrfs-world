<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Check if this is a test request
    $isTest = isset($_GET['test']) && $_GET['test'] === 'true';
    
    if ($isTest) {
        // Test mode - just verify the endpoint is working
        echo json_encode([
            'success' => true,
            'message' => 'Backup endpoint test successful',
            'test_mode' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
            ]
        ]);
        exit();
    }
    
    // Live backup mode
    $db = getSQLite3Connection();
    
    // Determine backup target path
    $backupPath = getDatabasePath();
    $backupDir = dirname($backupPath);
    $backupName = 'narrrf_world_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
    $targetPath = $backupDir . '/' . $backupName;
    
    // For production, also backup to /data/ directory if accessible
    $productionBackupPath = null;
    if (is_dir('/data') && is_writable('/data')) {
        $productionBackupPath = '/data/' . $backupName;
    }
    
    // Create backup
    $backupMethod = 'copy';
    $backupSuccess = false;
    
    // Method 1: Try direct file copy
    if (copy($backupPath, $targetPath)) {
        $backupSuccess = true;
        $backupMethod = 'file_copy';
    }
    
    // Method 2: Try SQLite backup if direct copy fails
    if (!$backupSuccess) {
        try {
            $backupDb = new SQLite3($targetPath);
            $backupDb->backup($db);
            $backupDb->close();
            $backupSuccess = true;
            $backupMethod = 'sqlite_backup';
        } catch (Exception $e) {
            error_log("SQLite backup failed: " . $e->getMessage());
        }
    }
    
    // Method 3: Try using SQLite dump if other methods fail
    if (!$backupSuccess) {
        try {
            $dumpCommand = "sqlite3 " . escapeshellarg($backupPath) . " .dump > " . escapeshellarg($targetPath . '.sql');
            exec($dumpCommand, $output, $returnCode);
            if ($returnCode === 0) {
                $backupSuccess = true;
                $backupMethod = 'sqlite_dump';
                $targetPath = $targetPath . '.sql';
            }
        } catch (Exception $e) {
            error_log("SQLite dump failed: " . $e->getMessage());
        }
    }
    
    if (!$backupSuccess) {
        throw new Exception('All backup methods failed');
    }
    
    // Verify backup file exists and has content
    if (!file_exists($targetPath) || filesize($targetPath) === 0) {
        throw new Exception('Backup file verification failed');
    }
    
    // Try production backup if possible
    $productionBackupSuccess = false;
    if ($productionBackupPath) {
        try {
            if (copy($targetPath, $productionBackupPath)) {
                $productionBackupSuccess = true;
            }
        } catch (Exception $e) {
            error_log("Production backup failed: " . $e->getMessage());
        }
    }
    
    // Get backup file info
    $backupSize = filesize($targetPath);
    $backupSizeFormatted = formatBytes($backupSize);
    
    // Log successful backup
    error_log("Database backup completed successfully: $targetPath ($backupSizeFormatted)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Database backup completed successfully',
        'backup_path' => $targetPath,
        'backup_size' => $backupSize,
        'backup_size_formatted' => $backupSizeFormatted,
        'backup_method' => $backupMethod,
        'timestamp' => date('Y-m-d H:i:s'),
        'production_backup' => $productionBackupSuccess ? $productionBackupPath : null,
        'target_path' => $productionBackupPath ?: $targetPath
    ]);
    
} catch (Exception $e) {
    error_log("Database backup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Backup failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
