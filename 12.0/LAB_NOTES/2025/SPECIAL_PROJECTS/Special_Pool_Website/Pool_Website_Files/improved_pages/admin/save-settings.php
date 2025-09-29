<?php
/**
 * Pool Website Admin - Save Settings
 * Saves website and email settings from admin dashboard
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $settings_file = 'settings.json';
    $current_settings = [];
    
    // Load existing settings if file exists
    if (file_exists($settings_file)) {
        $current_settings = json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Update settings
    if (isset($input['email_address'])) {
        $current_settings['email_address'] = $input['email_address'];
        
        // Update the email address in send-email.php
        updateEmailInSendEmail($input['email_address']);
    }
    
    // SMTP Settings
    if (isset($input['smtp_host'])) {
        $current_settings['smtp_host'] = $input['smtp_host'];
    }
    
    if (isset($input['smtp_port'])) {
        $current_settings['smtp_port'] = $input['smtp_port'];
    }
    
    if (isset($input['smtp_username'])) {
        $current_settings['smtp_username'] = $input['smtp_username'];
    }
    
    if (isset($input['smtp_password'])) {
        $current_settings['smtp_password'] = $input['smtp_password'];
    }
    
    if (isset($input['smtp_encryption'])) {
        $current_settings['smtp_encryption'] = $input['smtp_encryption'];
    }
    
    if (isset($input['from_email'])) {
        $current_settings['from_email'] = $input['from_email'];
    }
    
    if (isset($input['from_name'])) {
        $current_settings['from_name'] = $input['from_name'];
    }
    
    if (isset($input['company_name'])) {
        $current_settings['company_name'] = $input['company_name'];
    }
    
    if (isset($input['phone_number'])) {
        $current_settings['phone_number'] = $input['phone_number'];
    }
    
    if (isset($input['slider_speed'])) {
        $current_settings['slider_speed'] = intval($input['slider_speed']);
        
        // Update slider speed in index.html
        updateSliderSpeed($input['slider_speed']);
    }
    
    if (isset($input['bubble_effect'])) {
        $current_settings['bubble_effect'] = $input['bubble_effect'];
    }
    
    if (isset($input['bubble_color'])) {
        $current_settings['bubble_color'] = $input['bubble_color'];
    }
    
    if (isset($input['bubble_opacity'])) {
        $current_settings['bubble_opacity'] = $input['bubble_opacity'];
    }
    
    if (isset($input['bubble_speed'])) {
        $current_settings['bubble_speed'] = $input['bubble_speed'];
    }
    
    if (isset($input['bubble_pages'])) {
        $current_settings['bubble_pages'] = $input['bubble_pages'];
    }
    
    // Save settings to file
    $current_settings['last_updated'] = date('Y-m-d H:i:s');
    $current_settings['updated_by'] = $_SESSION['admin_username'];
    
    file_put_contents($settings_file, json_encode($current_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log the settings update
    $log_entry = date('Y-m-d H:i:s') . " - Settings updated by " . $_SESSION['admin_username'] . ": " . json_encode($input) . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully',
        'settings' => $current_settings
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving settings: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function updateEmailInSendEmail($new_email) {
    $send_email_file = '../api/send-email.php';
    
    if (file_exists($send_email_file)) {
        $content = file_get_contents($send_email_file);
        
        // Replace the email address in the PHP file
        $content = preg_replace(
            '/\$to_email\s*=\s*"[^"]*";/',
            '$to_email = "' . $new_email . '";',
            $content
        );
        
        file_put_contents($send_email_file, $content);
    }
}

function updateSliderSpeed($speed) {
    $index_file = '../index.html';
    
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
        
        // Replace slider speed in JavaScript
        $content = preg_replace(
            '/setInterval\(function\(\)\s*\{[^}]*\},[^)]*\);/',
            'setInterval(function() { changeSlide(1); }, ' . ($speed * 1000) . ');',
            $content
        );
        
        file_put_contents($index_file, $content);
    }
}
?>
