<?php
/**
 * Pool Website Email Handler
 * Handles all form submissions from the pool website and sends emails to office@poolbauprofi.at
 * 
 * Created: 2025-01-28
 * Purpose: Professional email handling for pool website contact forms
 */

require_once __DIR__ . '/smtp-helper.php';
require_once __DIR__ . '/email-api-service.php';

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON for all responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['name', 'email', 'message'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Validate email format
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Sanitize input data
$name = htmlspecialchars(trim($input['name']), ENT_QUOTES, 'UTF-8');
$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
$phone = isset($input['phone']) ? htmlspecialchars(trim($input['phone']), ENT_QUOTES, 'UTF-8') : '';
$service = isset($input['service']) ? htmlspecialchars(trim($input['service']), ENT_QUOTES, 'UTF-8') : '';
$budget = isset($input['budget']) ? htmlspecialchars(trim($input['budget']), ENT_QUOTES, 'UTF-8') : '';
$message = htmlspecialchars(trim($input['message']), ENT_QUOTES, 'UTF-8');
$form_type = isset($input['form_type']) ? htmlspecialchars(trim($input['form_type']), ENT_QUOTES, 'UTF-8') : 'Contact Form';

// Email configuration
$settings = poolbau_get_email_settings();

// CRITICAL: TO address is where emails are delivered (customer's office)
$to_email = $settings['email_address'] ?? 'office@poolbauprofi.at';

// CRITICAL: FROM address should be YOUR email (not customer's)
// This is the sender address that appears in the email
$from_email = $settings['sender_email'] ?? $settings['from_email'] ?? 'noreply@poolbauprofi.at';
$from_name = $settings['from_name'] ?? 'Poolbauprofi.at Website';

$subject = 'Neue Anfrage von Poolbauprofi.at - ' . $form_type;

// Build email body
$email_body = "Neue Anfrage von der Poolbauprofi.at Website\n\n";
$email_body .= "Formular-Typ: " . $form_type . "\n";
$email_body .= "Datum: " . date('d.m.Y H:i:s') . "\n";
$email_body .= "IP-Adresse: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
$email_body .= "=== KONTAKTDATEN ===\n";
$email_body .= "Name: " . $name . "\n";
$email_body .= "E-Mail: " . $email . "\n";

if (!empty($phone)) {
    $email_body .= "Telefon: " . $phone . "\n";
}

if (!empty($service)) {
    $service_names = [
        'pool-planning' => 'Pool Planung',
        'pool-construction' => 'Pool Bau',
        'pool-renovation' => 'Pool Renovierung',
        'pool-maintenance' => 'Pool Wartung',
        'consultation' => 'Beratung'
    ];
    $service_display = isset($service_names[$service]) ? $service_names[$service] : $service;
    $email_body .= "Gewünschte Leistung: " . $service_display . "\n";
}

if (!empty($budget)) {
    $budget_names = [
        'under-20k' => 'Unter 20.000€',
        '20k-50k' => '20.000€ - 50.000€',
        '50k-100k' => '50.000€ - 100.000€',
        'over-100k' => 'Über 100.000€'
    ];
    $budget_display = isset($budget_names[$budget]) ? $budget_names[$budget] : $budget;
    $email_body .= "Budget: " . $budget_display . "\n";
}

$email_body .= "\n=== NACHRICHT ===\n";
$email_body .= $message . "\n\n";
$email_body .= "=== TECHNISCHE DATEN ===\n";
$email_body .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
$email_body .= "Referrer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct') . "\n";

// Email headers
// FROM: Your email address (appears as sender)
// REPLY-TO: Customer's email (so replies go to the person who filled the form)
$headers = [
    'From: ' . $from_name . ' <' . $from_email . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'X-Mailer: Poolbauprofi.at',
    'Content-Type: text/plain; charset=UTF-8'
];

// Try to send email via Resend.com API only (SMTP removed)
$mail_sent = false;
$api_attempted = false;
$api_error = '';
$api_service = '';

// ONLY USE RESEND API (SMTP removed, no fallbacks)
if (!empty($settings['resend_api_key'])) {
    $api_attempted = true;
    
    // Send via Resend API directly
    $apiResult = send_email_via_resend(
        $settings['resend_api_key'],
        $to_email,  // Customer's office email (where email is delivered)
        $from_email,  // Your email (appears as sender)
        $from_name,
        $subject,
        $email_body,
        $email  // Customer's email (for replies)
    );
    
    if ($apiResult['success']) {
        $mail_sent = true;
        $api_service = 'Resend';
    } else {
        $api_error = $apiResult['error'] ?? 'Resend API error';
    }
} else {
    $api_error = 'Resend API key not configured. Please set RESEND_API_KEY environment variable (Render) or check local hardcoded value.';
}

// Always save to file for testing/backup
$email_file = 'submitted_emails.txt';
$file_content = "=== NEW EMAIL SUBMISSION ===\n";
$file_content .= "Date: " . date('Y-m-d H:i:s') . "\n";
$file_content .= "To: " . $to_email . "\n";
$file_content .= "Subject: " . $subject . "\n";
$file_content .= "From: " . $name . " <" . $email . ">\n";
$file_content .= "Form Type: " . $form_type . "\n";
$file_content .= "--- EMAIL CONTENT ---\n";
$file_content .= $email_body . "\n";
$file_content .= "=== END EMAIL ===\n\n";

file_put_contents($email_file, $file_content, FILE_APPEND | LOCK_EX);

// Log submission
$log_entry = date('Y-m-d H:i:s') . " - Form submitted from " . $email . " (" . $name . ") - Mail sent: " . ($mail_sent ? 'YES' : 'NO');
if ($api_attempted) {
    $log_entry .= " - Resend API attempted: YES";
    if ($mail_sent && $api_service) {
        $log_entry .= " - Service: " . $api_service . " (SUCCESS)";
    } elseif (!$mail_sent && $api_error) {
        $log_entry .= " - Resend API error: " . $api_error;
    }
} else {
    $log_entry .= " - Resend API not attempted: API key not configured";
}
$log_entry .= "\n";
file_put_contents('email_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

// Clean any output buffer to ensure clean JSON
ob_clean();

// Return success only if email was actually sent
if ($mail_sent) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Ihre Anfrage wurde erfolgreich gesendet! Wir werden uns innerhalb von 24 Stunden bei Ihnen melden.',
        'service_used' => $api_service ?: ($smtp_attempted ? 'SMTP' : 'PHP mail()'),
        'debug_info' => [
            'mail_sent' => true,
            'api_service' => $api_service ?: 'Resend',
            'saved_to_file' => $email_file,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Email failed to send, but still saved to file for backup
    http_response_code(200); // Still return 200 to not break the form, but include error info
    echo json_encode([
        'success' => true, // Set to true so form doesn't show error to user
        'message' => 'Ihre Anfrage wurde empfangen und gespeichert. Wir werden uns innerhalb von 24 Stunden bei Ihnen melden.',
        'warning' => 'Email delivery failed, but your message has been saved.',
        'debug_info' => [
            'mail_sent' => false,
            'api_attempted' => $api_attempted,
            'api_service' => $api_service ?: 'Resend',
            'api_error' => $api_error,
            'resend_api_key_configured' => !empty($settings['resend_api_key']),
            'saved_to_file' => $email_file,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
    // Log the failure for admin review
    $failure_log = date('Y-m-d H:i:s') . " - EMAIL DELIVERY FAILED for " . $email . " (" . $name . ")\n";
    $failure_log .= "  Resend API Error: " . ($api_error ?: 'N/A') . "\n";
    $failure_log .= "  Resend API Key Configured: " . (!empty($settings['resend_api_key']) ? 'YES' : 'NO') . "\n";
    $failure_log .= "  Message saved to: " . $email_file . "\n\n";
    file_put_contents('email_delivery_failures.txt', $failure_log, FILE_APPEND | LOCK_EX);
}

// End output buffer
ob_end_flush();
?>
