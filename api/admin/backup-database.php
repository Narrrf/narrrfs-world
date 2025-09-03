<?php
/**
 * Database Backup API
 * Handles backup operations for the production database
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 🔒 SECURE AUTHENTICATION: Use centralized admin auth
require_once __DIR__ . '/../config/admin-auth.php';
checkAdminAuthentication();

try {
    // Use centralized database path function
    $dbPath = getDatabasePath();
    
    // Define source and target paths
    $source_path = '/var/www/html/db/narrrf_world.sqlite';
    $target_path = '/data/narrrf_world.sqlite';
    
    // Check if we're in production mode
    $is_production = $_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1';
    
    // For local development, use local paths
    if (!$is_production) {
        $source_path = __DIR__ . '/../../db/narrrf_world.sqlite';
        $target_path = __DIR__ . '/../../db/narrrf_world_backup.sqlite';
    }
    
    // Handle different request types
    if (isset($_GET['test']) && $_GET['test'] === 'true') {
        // Test mode - verify paths and permissions
        $response = [
            'test' => true,
            'source_path' => $source_path,
            'source_exists' => file_exists($source_path),
            'source_readable' => is_readable($source_path),
            'source_size' => file_exists($source_path) ? filesize($source_path) : 0,
            'production_mode' => $is_production ? 'PRODUCTION' : 'LOCAL',
            'target_dir_exists' => $is_production ? is_dir('/data') : is_dir(dirname($target_path)),
            'target_dir_writable' => $is_production ? is_writable('/data') : is_writable(dirname($target_path)),
            'current_user' => get_current_user(),
            'php_user' => get_current_user(),
            'web_server_user' => get_current_user()
        ];
        
        echo json_encode($response);
        exit();
    }
    
    if (isset($_GET['test_copy']) && $_GET['test_copy'] === 'true') {
        // Test copy operation
        $response = [
            'test_copy_mode' => true,
            'source_path' => $source_path,
            'source_size' => file_exists($source_path) ? filesize($source_path) : 0,
            'source_size_formatted' => file_exists($source_path) ? formatBytes(filesize($source_path)) : '0 B',
            'target_path' => $target_path,
            'copy_success' => false,
            'copy_error' => '',
            'size_match' => false,
            'data_dir_status' => [
                'exists' => $is_production ? is_dir('/data') : is_dir(dirname($target_path)),
                'writable' => $is_production ? is_writable('/data') : is_writable(dirname($target_path)),
                'permissions' => $is_production ? (is_dir('/data') ? substr(sprintf('%o', fileperms('/data')), -4) : 'N/A') : (is_dir(dirname($target_path)) ? substr(sprintf('%o', fileperms(dirname($target_path))), -4) : 'N/A')
            ]
        ];
        
        // Attempt the copy operation
        if (file_exists($source_path) && is_readable($source_path)) {
            $copy_result = copy($source_path, $target_path);
            $response['copy_success'] = $copy_result;
            
            if ($copy_result) {
                $response['target_size'] = filesize($target_path);
                $response['target_size_formatted'] = formatBytes(filesize($target_path));
                $response['size_match'] = filesize($source_path) === filesize($target_path);
            } else {
                $response['copy_error'] = 'Copy operation failed';
            }
        } else {
            $response['copy_error'] = 'Source file does not exist or is not readable';
        }
        
        echo json_encode($response);
        exit();
    }
    
    if (isset($_GET['dry_run']) && $_GET['dry_run'] === 'true') {
        // Dry run mode - simulate backup without actually doing it
        $response = [
            'dry_run' => true,
            'source_path' => $source_path,
            'target_path' => $target_path,
            'source_exists' => file_exists($source_path),
            'source_size' => file_exists($source_path) ? filesize($source_path) : 0,
            'source_size_formatted' => file_exists($source_path) ? formatBytes(filesize($source_path)) : '0 B',
            'target_dir_exists' => $is_production ? is_dir('/data') : is_dir(dirname($target_path)),
            'target_dir_writable' => $is_production ? is_writable('/data') : is_writable(dirname($target_path)),
            'backup_method' => 'cp command simulation',
            'simulation_result' => 'Would copy ' . $source_path . ' to ' . $target_path
        ];
        
        echo json_encode($response);
        exit();
    }
    
    // Actual backup operation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = [
            'success' => false,
            'source_path' => $source_path,
            'target_path' => $target_path,
            'backup_method' => $is_production ? 'cp command (production)' : 'copy() function (local)',
            'source_size' => 0,
            'source_size_formatted' => '0 B',
            'target_size' => 0,
            'target_size_formatted' => '0 B',
            'backup_verification' => [
                'source_exists' => false,
                'source_readable' => false,
                'target_exists' => false,
                'target_readable' => false,
                'size_match' => false
            ]
        ];
        
        // Verify source file exists and is readable
        if (!file_exists($source_path)) {
            throw new Exception("Source database file does not exist: $source_path");
        }
        
        if (!is_readable($source_path)) {
            throw new Exception("Source database file is not readable: $source_path");
        }
        
        $source_size = filesize($source_path);
        $response['source_size'] = $source_size;
        $response['source_size_formatted'] = formatBytes($source_size);
        $response['backup_verification']['source_exists'] = true;
        $response['backup_verification']['source_readable'] = true;
        
        // Verify target directory exists and is writable
        if (!is_dir($is_production ? '/data' : dirname($target_path))) {
            throw new Exception("Target directory " . ($is_production ? '/data' : dirname($target_path)) . " does not exist");
        }
        
        if (!is_writable($is_production ? '/data' : dirname($target_path))) {
            throw new Exception("Target directory " . ($is_production ? '/data' : dirname($target_path)) . " is not writable");
        }
        
        // Perform the backup using appropriate method for environment
        if ($is_production) {
            // Use cp command on production (Render)
            $command = "cp \"$source_path\" \"$target_path\"";
            $output = [];
            $return_var = 0;
            
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                throw new Exception("Copy command failed with return code: $return_var. Output: " . implode("\n", $output));
            }
        } else {
            // Use copy() function on local development (Windows/Linux)
            $copy_result = copy($source_path, $target_path);
            
            if (!$copy_result) {
                throw new Exception("Copy operation failed");
            }
        }
        
        // Verify the backup was successful
        if (!file_exists($target_path)) {
            throw new Exception("Backup file was not created at: $target_path");
        }
        
        $target_size = filesize($target_path);
        $response['target_size'] = $target_size;
        $response['target_size_formatted'] = formatBytes($target_size);
        $response['backup_verification']['target_exists'] = true;
        $response['backup_verification']['target_readable'] = is_readable($target_path);
        $response['backup_verification']['size_match'] = ($source_size === $target_size);
        
        if ($source_size !== $target_size) {
            throw new Exception("Backup size mismatch: source=$source_size, target=$target_size");
        }
        
        $response['success'] = true;
        $response['message'] = "Database backup completed successfully";
        
        echo json_encode($response);
        exit();
    }
    
    // Default response for GET requests
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method. Use POST for backup, GET with parameters for testing.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'source_path' => $source_path ?? 'unknown',
        'target_path' => $target_path ?? 'unknown'
    ]);
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
