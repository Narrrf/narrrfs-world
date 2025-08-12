<?php
// Suppress any PHP warnings or errors that might corrupt the output
error_reporting(0);
ini_set('display_errors', 0);

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
    // Use the centralized database configuration to get the correct source path
    require_once '../config/database.php';
    $sourceDbPath = getDatabasePath();
    
    // Destination: Production database in /data/ (for production) or local backup (for local)
    $is_local = PHP_OS_FAMILY === 'Windows' || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false;
    
    // Force production mode if we're on the live server
    if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
        $is_local = false;
        error_log("Force production mode detected - live database exists at /var/www/html/db/");
    }
    
    if ($is_local) {
        // Local development - backup to local backup directory
        $targetDbPath = dirname($sourceDbPath) . '/backup_' . date('Y-m-d_H-i-s') . '.sqlite';
        error_log("Local backup mode - target: " . $targetDbPath);
    } else {
        // Production - backup to /data/
        $targetDbPath = '/data/narrrf_world.sqlite';
        error_log("Production backup mode - target: " . $targetDbPath);
    }
    
    // Log the paths being used for debugging
    error_log("Backup API: Copying FROM: " . $sourceDbPath . " TO: " . $targetDbPath);
    
    // Check if source exists
    if (!file_exists($sourceDbPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Database not found at: ' . $sourceDbPath]);
        exit;
    }
    
    // Check if we can read the source
    if (!is_readable($sourceDbPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Cannot read source database: ' . $sourceDbPath]);
        exit;
    }
    
    // Check if destination directory is writable
    $targetDir = dirname($targetDbPath);
    if (!is_dir($targetDir)) {
        // Try to create the directory
        if (!mkdir($targetDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Cannot create destination directory: ' . $targetDir]);
            exit;
        }
    }
    
    if (!is_writable($targetDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Destination directory not writable: ' . $targetDir]);
        exit;
    }
    
    // Get source file info
    $sourceSize = filesize($sourceDbPath);
    error_log("Backup API: Source file size: " . $sourceSize . " bytes");
    
    // Try multiple backup methods
    $backupSuccess = false;
    $backupMethod = '';
    $backupError = '';
    
    // Method 1: Direct copy
    if (copy($sourceDbPath, $targetDbPath)) {
        $targetSize = filesize($targetDbPath);
        if ($sourceSize === $targetSize) {
            $backupSuccess = true;
            $backupMethod = 'Direct copy';
        }
    } else {
        $backupError = 'Direct copy failed: ' . (error_get_last()['message'] ?? 'Unknown error');
    }
    
    // Method 2: If direct copy fails, try using shell command
    if (!$backupSuccess) {
        $shellCommand = "cp '$sourceDbPath' '$targetDbPath'";
        $output = [];
        $returnCode = 0;
        
        exec($shellCommand . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($targetDbPath)) {
            $targetSize = filesize($targetDbPath);
            if ($sourceSize === $targetSize) {
                $backupSuccess = true;
                $backupMethod = 'Shell command';
            }
        } else {
            $backupError .= ' | Shell command failed: ' . implode(' ', $output);
        }
    }
    
    // Method 3: If both fail, try using file_get_contents and file_put_contents
    if (!$backupSuccess) {
        $content = file_get_contents($sourceDbPath);
        if ($content !== false) {
            if (file_put_contents($targetDbPath, $content) !== false) {
                $targetSize = filesize($targetDbPath);
                if ($sourceSize === $targetSize) {
                    $backupSuccess = true;
                    $backupMethod = 'File contents copy';
                }
            } else {
                $backupError .= ' | File contents copy failed';
            }
        } else {
            $backupError .= ' | Cannot read source file';
        }
    }
    
    if ($backupSuccess) {
        $targetSize = filesize($targetDbPath);
        $fileSizeFormatted = formatBytes($targetSize);
        
        // Log successful backup
        error_log("Backup API: Successfully copied live DB to production using $backupMethod. Size: " . $targetSize . " bytes");
        
        echo json_encode([
            'success' => true,
            'message' => 'Live database successfully backed up to production',
            'backup_method' => $backupMethod,
            'source_path' => $sourceDbPath,
            'target_path' => $targetDbPath,
            'source_size' => formatBytes($sourceSize),
            'target_size' => $fileSizeFormatted,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'All backup methods failed: ' . $backupError,
            'source_path' => $sourceDbPath,
            'target_path' => $targetDbPath,
            'source_size' => formatBytes($sourceSize)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Backup API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database backup failed: ' . $e->getMessage()]);
}

// Add a simple test function for debugging
if (isset($_GET['test']) && $_GET['test'] === 'true') {
    require_once '../config/database.php';
    $sourceDbPath = getDatabasePath();
    $is_local = PHP_OS_FAMILY === 'Windows' || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false;
    
    echo json_encode([
        'test' => true,
        'source_path' => $sourceDbPath,
        'source_exists' => file_exists($sourceDbPath),
        'source_readable' => is_readable($sourceDbPath),
        'source_size' => file_exists($sourceDbPath) ? filesize($sourceDbPath) : 'N/A',
        'is_local' => $is_local,
        'target_dir_exists' => $is_local ? is_dir(dirname($sourceDbPath)) : is_dir('/data'),
        'target_dir_writable' => $is_local ? is_writable(dirname($sourceDbPath)) : is_writable('/data'),
        'current_user' => get_current_user(),
        'php_user' => function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? 'Unknown') : 'Unknown',
        'web_server_user' => $_SERVER['USER'] ?? 'Unknown'
    ]);
    exit;
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

function cleanupOldBackups($backupDir, $keepCount) {
    $files = glob($backupDir . '/narrrf_world_backup_*.sqlite');
    
    if (count($files) > $keepCount) {
        // Sort files by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Remove old files
        $filesToRemove = array_slice($files, $keepCount);
        foreach ($filesToRemove as $file) {
            unlink($file);
        }
    }
}
?>
