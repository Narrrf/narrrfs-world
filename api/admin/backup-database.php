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
    $isDryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === 'true';
    
    if ($isTest) {
        // Test mode - comprehensive database verification
        $dbPath = getDatabasePath();
        $dbDir = dirname($dbPath);
        $dataDir = '/data';
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup endpoint test successful',
            'test_mode' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'source_path' => $dbPath,
            'source_exists' => file_exists($dbPath),
            'source_readable' => is_readable($dbPath),
            'source_size' => file_exists($dbPath) ? filesize($dbPath) : 0,
            'source_size_formatted' => file_exists($dbPath) ? formatBytes(filesize($dbPath)) : '0 B',
            'production_mode' => is_dir('/data') ? 'Production (Render)' : 'Local/Development',
            'target_dir_exists' => is_dir($dataDir),
            'target_dir_writable' => is_dir($dataDir) && is_writable($dataDir),
            'current_user' => get_current_user(),
            'php_user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown',
            'web_server_user' => $_SERVER['USER'] ?? 'www-data',
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown'
            ]
        ]);
        exit();
    }
    
    if ($isDryRun) {
        // Dry run mode - test backup process without creating files
        $dbPath = getDatabasePath();
        $backupDir = dirname($dbPath);
        $backupName = 'narrrf_world_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
        $targetPath = $backupDir . '/' . $backupName;
        $productionBackupPath = '/data/' . $backupName;
        
        $sourceSize = file_exists($dbPath) ? filesize($dbPath) : 0;
        $sourceSizeFormatted = formatBytes($sourceSize);
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup dry run completed',
            'dry_run_mode' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'source_path' => $dbPath,
            'source_size' => $sourceSize,
            'source_size_formatted' => $sourceSizeFormatted,
            'target_path' => $targetPath,
            'production_backup_path' => $productionBackupPath,
            'backup_dir_writable' => is_writable($backupDir),
            'data_dir_exists' => is_dir('/data'),
            'data_dir_writable' => is_dir('/data') && is_writable('/data'),
            'estimated_backup_size' => $sourceSize,
            'estimated_backup_size_formatted' => $sourceSizeFormatted,
            'backup_methods_available' => [
                'file_copy' => is_writable($backupDir),
                'sqlite_backup' => class_exists('SQLite3'),
                'sqlite_dump' => function_exists('exec')
            ]
        ]);
        exit();
    }
    
    if (isset($_GET['test_copy']) && $_GET['test_copy'] === 'true') {
        // Test copy mode - test actual file copy to /data/ directory
        $dbPath = getDatabasePath();
        $testFileName = 'test_copy_' . date('Y-m-d_H-i-s') . '.sqlite';
        $testTargetPath = '/data/' . $testFileName;
        
        $sourceSize = file_exists($dbPath) ? filesize($dbPath) : 0;
        $sourceSizeFormatted = formatBytes($sourceSize);
        
        $copySuccess = false;
        $copyError = '';
        $targetSize = 0;
        
        try {
            if (is_dir('/data') && is_writable('/data')) {
                if (copy($dbPath, $testTargetPath)) {
                    $copySuccess = true;
                    $targetSize = file_exists($testTargetPath) ? filesize($testTargetPath) : 0;
                    
                    // Clean up test file
                    if (file_exists($testTargetPath)) {
                        unlink($testTargetPath);
                    }
                } else {
                    $copyError = 'Copy operation failed';
                }
            } else {
                $copyError = '/data directory not accessible or not writable';
            }
        } catch (Exception $e) {
            $copyError = 'Copy exception: ' . $e->getMessage();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Copy test completed',
            'test_copy_mode' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'source_path' => $dbPath,
            'source_size' => $sourceSize,
            'source_size_formatted' => $sourceSizeFormatted,
            'target_path' => $testTargetPath,
            'copy_success' => $copySuccess,
            'copy_error' => $copyError,
            'target_size' => $targetSize,
            'target_size_formatted' => formatBytes($targetSize),
            'size_match' => $sourceSize === $targetSize,
            'data_dir_status' => [
                'exists' => is_dir('/data'),
                'writable' => is_dir('/data') && is_writable('/data'),
                'permissions' => is_dir('/data') ? substr(sprintf('%o', fileperms('/data')), -4) : 'N/A'
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
    
    // Get source database info before backup
    $sourceSize = file_exists($backupPath) ? filesize($backupPath) : 0;
    $sourceSizeFormatted = formatBytes($sourceSize);
    
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
    $productionBackupSize = 0;
    if ($productionBackupPath) {
        try {
            if (copy($targetPath, $productionBackupPath)) {
                $productionBackupSuccess = true;
                $productionBackupSize = file_exists($productionBackupPath) ? filesize($productionBackupPath) : 0;
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
        'target_path' => $productionBackupPath ?: $targetPath,
        // Enhanced response with source and target sizes
        'source_size' => $sourceSize,
        'source_size_formatted' => $sourceSizeFormatted,
        'target_size' => $productionBackupSuccess ? $productionBackupSize : $backupSize,
        'target_size_formatted' => $productionBackupSuccess ? formatBytes($productionBackupSize) : $backupSizeFormatted,
        'source_path' => $backupPath,
        'backup_verification' => [
            'source_exists' => file_exists($backupPath),
            'source_readable' => is_readable($backupPath),
            'target_exists' => file_exists($productionBackupPath ?: $targetPath),
            'target_readable' => is_readable($productionBackupPath ?: $targetPath),
            'size_match' => $sourceSize === ($productionBackupSuccess ? $productionBackupSize : $backupSize)
        ]
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
