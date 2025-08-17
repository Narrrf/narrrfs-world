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
            'message' => 'Shell command test endpoint working',
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
    
    // Live test mode - test the actual cp command
    $dbPath = getDatabasePath();
    $testFileName = 'test_shell_copy_' . date('Y-m-d_H-i-s') . '.sqlite';
    $testTargetPath = '/data/' . $testFileName;
    
    $sourceSize = file_exists($dbPath) ? filesize($dbPath) : 0;
    $sourceSizeFormatted = formatBytes($sourceSize);
    
    $shellCommand = "cp " . escapeshellarg($dbPath) . " " . escapeshellarg($testTargetPath);
    
    // Test shell command execution
    $output = [];
    $returnCode = 0;
    
    $shellSuccess = false;
    $shellError = '';
    $targetSize = 0;
    
    try {
        if (is_dir('/data') && is_writable('/data')) {
            // Execute the shell command
            exec($shellCommand . " 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                $shellSuccess = true;
                $targetSize = file_exists($testTargetPath) ? filesize($testTargetPath) : 0;
                
                // Clean up test file
                if (file_exists($testTargetPath)) {
                    unlink($testTargetPath);
                }
            } else {
                $shellError = 'Shell command failed with return code: ' . $returnCode;
                if (!empty($output)) {
                    $shellError .= ' - Output: ' . implode(' ', $output);
                }
            }
        } else {
            $shellError = '/data directory not accessible or not writable';
        }
    } catch (Exception $e) {
        $shellError = 'Shell command exception: ' . $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Shell command test completed',
        'shell_test_mode' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'shell_command' => $shellCommand,
        'source_path' => $dbPath,
        'source_size' => $sourceSize,
        'source_size_formatted' => $sourceSizeFormatted,
        'target_path' => $testTargetPath,
        'shell_success' => $shellSuccess,
        'shell_error' => $shellError,
        'return_code' => $returnCode,
        'shell_output' => $output,
        'target_size' => $targetSize,
        'target_size_formatted' => formatBytes($targetSize),
        'size_match' => $sourceSize === $targetSize,
        'data_dir_status' => [
            'exists' => is_dir('/data'),
            'writable' => is_dir('/data') && is_writable('/data'),
            'permissions' => is_dir('/data') ? substr(sprintf('%o', fileperms('/data')), -4) : 'N/A'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Shell command test error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Shell command test failed: ' . $e->getMessage(),
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
