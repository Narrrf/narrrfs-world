<?php
/**
 * Pool Website Admin - Get Statistics
 * Returns website statistics for the admin dashboard
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
    // Get email statistics
    $email_stats = getEmailStats();
    
    // Get website statistics
    $website_stats = getWebsiteStats();
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'email_stats' => $email_stats,
        'website_stats' => $website_stats,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading statistics: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getEmailStats() {
    $stats = [
        'total' => 0,
        'today' => 0,
        'week' => 0,
        'month' => 0
    ];
    
    // Check if email log exists
    $email_log_file = '../api/email_log.txt';
    if (file_exists($email_log_file)) {
        $lines = file($email_log_file, FILE_IGNORE_NEW_LINES);
        $stats['total'] = count($lines);
        
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $month_ago = date('Y-m-d', strtotime('-30 days'));
        
        foreach ($lines as $line) {
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                $line_date = $matches[1];
                
                if ($line_date === $today) {
                    $stats['today']++;
                }
                
                if ($line_date >= $week_ago) {
                    $stats['week']++;
                }
                
                if ($line_date >= $month_ago) {
                    $stats['month']++;
                }
            }
        }
    }
    
    return $stats;
}

function getWebsiteStats() {
    $stats = [
        'pages' => 5,
        'forms' => 4,
        'photos' => 0,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Count photos in assets directory
    $assets_dir = '../assets/';
    if (is_dir($assets_dir)) {
        $photo_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $files = scandir($assets_dir);
        
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $photo_extensions)) {
                $stats['photos']++;
            }
        }
    }
    
    return $stats;
}
?>
