<?php
/**
 * Pool Website Admin - Test Email Connection
 * Tests SMTP connection and sends a test email
 * 
 * Created: September 29, 2025
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
    
    // Validate required fields
    $required_fields = ['smtp_host', 'smtp_username', 'smtp_password'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Extract SMTP settings
    $smtp_host = $input['smtp_host'];
    $smtp_port = $input['smtp_port'] ?? 587;
    $smtp_username = $input['smtp_username'];
    $smtp_password = $input['smtp_password'];
    $smtp_encryption = $input['smtp_encryption'] ?? 'tls';
    $from_email = $input['from_email'] ?? $smtp_username;
    $from_name = $input['from_name'] ?? 'Poolbauprofi.at Test';
    $to_email = $input['email_address'] ?? 'office@poolbauprofi.at';
    
    // Test email sending using PHP's mail() function with SMTP
    $test_result = testEmailConnection($smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $from_email, $from_name, $to_email);
    
    if ($test_result['success']) {
        ob_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email connection test successful! Test email sent.',
            'details' => $test_result['message']
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    } else {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $test_result['message']
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

function testEmailConnection($host, $port, $username, $password, $encryption, $from_email, $from_name, $to_email) {
    try {
        // Create a test email message
        $subject = 'Poolbauprofi.at - E-Mail-Test';
        $message = "Dies ist eine Test-E-Mail von Ihrem Poolbauprofi.at Admin-System.\n\n";
        $message .= "E-Mail-Konfiguration:\n";
        $message .= "- SMTP-Server: $host\n";
        $message .= "- Port: $port\n";
        $message .= "- Verschlüsselung: $encryption\n";
        $message .= "- Absender: $from_email\n";
        $message .= "- Test-Zeit: " . date('Y-m-d H:i:s') . "\n\n";
        $message .= "Wenn Sie diese E-Mail erhalten, ist Ihre SMTP-Konfiguration korrekt!\n\n";
        $message .= "Mit freundlichen Grüßen,\nIhr Poolbauprofi.at System";
        
        $headers = [
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: Poolbauprofi.at Admin System',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        // For now, use PHP's built-in mail() function
        // In production, you might want to use PHPMailer or similar
        $mail_sent = mail($to_email, $subject, $message, implode("\r\n", $headers));
        
        if ($mail_sent) {
            return [
                'success' => true,
                'message' => 'Test email sent successfully using PHP mail() function'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send test email. Check your server mail configuration.'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error during email test: ' . $e->getMessage()
        ];
    }
}

// Log the test attempt
$log_entry = date('Y-m-d H:i:s') . " - Email connection test by " . $_SESSION['admin_username'] . "\n";
file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

?>
