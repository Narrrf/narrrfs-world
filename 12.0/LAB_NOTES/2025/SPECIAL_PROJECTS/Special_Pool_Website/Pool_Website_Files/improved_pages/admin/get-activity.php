<?php
/**
 * Pool Website Admin - Get Recent Activity
 * Returns recent admin activities and website changes
 * 
 * Created: September 28, 2025
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    $activities = getRecentActivity();
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading activity: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getRecentActivity() {
    $activities = [];
    
    // Get admin login/logout activities
    $admin_log_file = '../api/admin_log.txt';
    if (file_exists($admin_log_file)) {
        $lines = file($admin_log_file, FILE_IGNORE_NEW_LINES);
        $recent_lines = array_slice(array_reverse($lines), 0, 10);
        
        foreach ($recent_lines as $line) {
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - (.+)/', $line, $matches)) {
                $time = $matches[1];
                $message = $matches[2];
                
                $activities[] = [
                    'time' => $time,
                    'message' => $message,
                    'type' => 'admin'
                ];
            }
        }
    }
    
    // Get email submissions
    $email_log_file = '../api/email_log.txt';
    if (file_exists($email_log_file)) {
        $lines = file($email_log_file, FILE_IGNORE_NEW_LINES);
        $recent_lines = array_slice(array_reverse($lines), 0, 5);
        
        foreach ($recent_lines as $line) {
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - (.+)/', $line, $matches)) {
                $time = $matches[1];
                $message = $matches[2];
                
                $activities[] = [
                    'time' => $time,
                    'message' => 'E-Mail erhalten: ' . $message,
                    'type' => 'email'
                ];
            }
        }
    }
    
    // Sort by time (newest first)
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    // Return only the 10 most recent activities
    return array_slice($activities, 0, 10);
}
?>
