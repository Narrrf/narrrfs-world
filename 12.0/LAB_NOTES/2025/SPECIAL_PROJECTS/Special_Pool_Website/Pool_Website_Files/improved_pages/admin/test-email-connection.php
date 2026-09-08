<?php
/**
 * Pool Website Admin - Test Email Connection
 * Tests Resend.com API service only (SMTP removed)
 * 
 * Created: September 29, 2025
 * Updated: November 19, 2025 - Resend.com API only (no SMTP)
 */

require_once __DIR__ . '/../api/smtp-helper.php';
require_once __DIR__ . '/../api/email-api-service.php';

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
    // Load settings from settings.json (includes Resend API key from env var or hardcoded)
    $settings = poolbau_get_email_settings();
    
    // Get JSON input (email settings from form)
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = [];
    }
    
    // Merge input with saved settings (use saved settings for API key, input for email addresses)
    $testSettings = array_merge($settings, $input);
    
    // Extract email settings
    $from_email = $testSettings['from_email'] ?? $testSettings['sender_email'] ?? 'onboarding@resend.dev';
    $from_name = $testSettings['from_name'] ?? 'Poolbauprofi.at Test';
    $to_email = $testSettings['email_address'] ?? 'office@poolbauprofi.at';
    
    $testSubject = 'Poolbauprofi.at - E-Mail-Test';
    $testBody = "Dies ist eine Test-E-Mail von Ihrem Poolbauprofi.at Admin-System.\n\n" .
        "Test-Zeit: " . date('Y-m-d H:i:s') . "\n\n" .
        "Wenn Sie diese E-Mail erhalten, ist Ihre Resend.com API-Konfiguration korrekt!\n\n" .
        "Mit freundlichen Grüßen,\nIhr Poolbauprofi.at System";
    
    $emailSent = false;
    $serviceUsed = '';
    $errorMessage = '';
    
    // ONLY USE RESEND API (SMTP removed)
    if (!empty($testSettings['resend_api_key'])) {
        $apiResult = send_email_via_resend(
            $testSettings['resend_api_key'],
            $to_email,
            $from_email,
            $from_name,
            $testSubject,
            $testBody,
            $from_email // reply-to
        );
        
        if ($apiResult['success']) {
            $emailSent = true;
            $serviceUsed = 'Resend';
        } else {
            $errorMessage = $apiResult['error'] ?? 'Resend API error';
        }
    } else {
        $errorMessage = 'Resend API key not configured. Please set RESEND_API_KEY environment variable (Render) or check local hardcoded value.';
    }
    
    if ($emailSent) {
        ob_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email connection test successful! Test email sent via Resend.com API.',
            'details' => 'Test email delivered successfully using Resend.com.',
            'service' => 'Resend'
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    } else {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email test failed: ' . $errorMessage,
            'details' => $errorMessage,
            'debug' => [
                'resend_api_key_configured' => !empty($testSettings['resend_api_key']),
                'resend_api_key_length' => !empty($testSettings['resend_api_key']) ? strlen($testSettings['resend_api_key']) : 0,
                'from_email' => $from_email,
                'to_email' => $to_email
            ]
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error testing email connection: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

// Log the test attempt
$log_entry = date('Y-m-d H:i:s') . " - Email connection test by " . ($_SESSION['admin_username'] ?? 'unknown') . " - Result: " . ($emailSent ?? false ? 'SUCCESS' : 'FAILED') . " - Service: Resend.com API\n";
file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

?>
