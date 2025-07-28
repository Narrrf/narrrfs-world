<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// === DATABASE PERSISTENCE API ===
// This endpoint triggers automatic database backup to persistent storage
// Call this after any database modifications to ensure data persistence

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is admin (you can add your admin check logic here)
// For now, we'll allow any POST request to trigger backup

try {
    // Execute the backup script
    $backup_script = '/var/www/html/scripts/db-backup.sh';
    
    if (!file_exists($backup_script)) {
        throw new Exception('Backup script not found');
    }
    
    // Make script executable
    chmod($backup_script, 0755);
    
    // Execute backup script
    $output = [];
    $return_code = 0;
    
    exec("$backup_script 2>&1", $output, $return_code);
    
    if ($return_code !== 0) {
        throw new Exception('Backup script failed: ' . implode("\n", $output));
    }
    
    // Execute cleanup script to manage storage
    $cleanup_script = '/var/www/html/scripts/db-cleanup.sh';
    
    if (file_exists($cleanup_script)) {
        chmod($cleanup_script, 0755);
        exec("$cleanup_script 2>&1", $cleanup_output, $cleanup_code);
        
        if ($cleanup_code !== 0) {
            // Log cleanup failure but don't fail the backup
            error_log('Database cleanup failed: ' . implode("\n", $cleanup_output));
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Database backup completed successfully',
        'output' => $output,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database backup failed',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 