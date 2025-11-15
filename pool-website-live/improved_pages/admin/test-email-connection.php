<?php
/**
 * Pool Website Admin - Test Email Connection
 * Tests SMTP connection and sends a test email
 * 
 * Created: September 29, 2025
 */

require_once __DIR__ . '/../api/smtp-helper.php';

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
    $required_fields = ['smtp_host', 'smtp_username'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Extract SMTP settings
    $smtp_host = $input['smtp_host'];
    $smtp_port = $input['smtp_port'] ?? 587;
    $smtp_username = $input['smtp_username'];
    $smtp_password = $input['smtp_password'] ?? '';
    $smtp_encryption = $input['smtp_encryption'] ?? 'tls';
    $from_email = $input['from_email'] ?? $smtp_username;
    $from_name = $input['from_name'] ?? 'Poolbauprofi.at Test';
    $to_email = $input['email_address'] ?? 'office@poolbauprofi.at';

    if (empty($smtp_password)) {
        $envPassword = getenv('POOLBAU_SMTP_PASSWORD');
        if ($envPassword !== false && $envPassword !== '') {
            $smtp_password = $envPassword;
        } else {
            throw new Exception('Es wurde kein SMTP-Passwort angegeben. Bitte tragen Sie es ein oder konfigurieren Sie die Render-Variable POOLBAU_SMTP_PASSWORD.');
        }
    }
    
    $smtpResult = poolbau_send_email_via_smtp([
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_username' => $smtp_username,
        'smtp_password' => $smtp_password,
        'smtp_encryption' => $smtp_encryption,
        'smtp_timeout' => 30,
        'from_email' => $from_email,
        'from_name' => $from_name
    ], [
        'to_email' => $to_email,
        'to_name' => 'Poolbauprofi.at',
        'from_email' => $from_email,
        'from_name' => $from_name,
        'reply_to_email' => $from_email,
        'reply_to_name' => $from_name,
        'subject' => 'Poolbauprofi.at - E-Mail-Test',
        'body' => "Dies ist eine Test-E-Mail von Ihrem Poolbauprofi.at Admin-System.\n\n" .
            "E-Mail-Konfiguration:\n" .
            "- SMTP-Server: {$smtp_host}\n" .
            "- Port: {$smtp_port}\n" .
            "- Verschlüsselung: {$smtp_encryption}\n" .
            "- Absender: {$from_email}\n" .
            "- Test-Zeit: " . date('Y-m-d H:i:s') . "\n\n" .
            "Wenn Sie diese E-Mail erhalten, ist Ihre SMTP-Konfiguration korrekt!\n\n" .
            "Mit freundlichen Grüßen,\nIhr Poolbauprofi.at System"
    ]);

    if ($smtpResult['success']) {
        ob_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email connection test successful! Test email sent.',
            'details' => 'SMTP delivery completed successfully.'
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    } else {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $smtpResult['error']
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
$log_entry = date('Y-m-d H:i:s') . " - Email connection test by " . $_SESSION['admin_username'] . "\n";
file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

?>
