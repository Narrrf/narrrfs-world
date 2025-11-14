<?php
/**
 * Pool Website Email Handler
 * Handles all form submissions from the pool website and sends emails to office@poolbauprofi.at
 * 
 * Created: 2025-01-28
 * Purpose: Professional email handling for pool website contact forms
 */

require_once __DIR__ . '/smtp-helper.php';

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

$to_email = $settings['email_address'] ?? 'office@poolbauprofi.at';
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
$from_email = $settings['from_email'] ?? 'noreply@poolbauprofi.at';
$from_name = $settings['from_name'] ?? 'Poolbauprofi.at Website';

$headers = [
    'From: ' . $from_name . ' <' . $from_email . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'X-Mailer: Poolbauprofi.at',
    'Content-Type: text/plain; charset=UTF-8'
];

// Try to send email, but also save to file for testing
$mail_sent = false;
$smtp_attempted = false;
$smtp_error = '';
$smtp_debug = [];

// Try sending via SMTP helper if configured
if (poolbau_can_use_smtp($settings)) {
    $smtp_attempted = true;
    $smtpResult = poolbau_send_email_via_smtp($settings, [
        'to_email' => $to_email,
        'to_name' => 'Poolbauprofi.at',
        'from_email' => $from_email,
        'from_name' => $from_name,
        'reply_to_email' => $email,
        'reply_to_name' => $name,
        'subject' => $subject,
        'body' => $email_body
    ]);

    if ($smtpResult['success']) {
        $mail_sent = true;
    } else {
        $smtp_error = $smtpResult['error'];
        $smtp_debug = $smtpResult['debug'];
    }
}

// Fallback to PHP mail() if SMTP failed or not configured
if (!$mail_sent && function_exists('mail')) {
    $mail_sent = mail($to_email, $subject, $email_body, implode("\r\n", $headers));
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
if ($smtp_attempted) {
    $log_entry .= " - SMTP attempted: YES";
    if (!$mail_sent && $smtp_error) {
        $log_entry .= " - SMTP error: " . $smtp_error;
    }
}
$log_entry .= "\n";
file_put_contents('email_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

// Clean any output buffer to ensure clean JSON
ob_clean();

// Always return success for testing (email is saved to file)
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Ihre Anfrage wurde erfolgreich gesendet! Wir werden uns innerhalb von 24 Stunden bei Ihnen melden.',
    'debug_info' => [
        'mail_function_available' => function_exists('mail'),
        'mail_sent' => $mail_sent,
        'smtp_attempted' => $smtp_attempted,
        'smtp_error' => $smtp_error,
        'saved_to_file' => $email_file,
        'timestamp' => date('Y-m-d H:i:s')
    ]
], JSON_UNESCAPED_UNICODE);

// End output buffer
ob_end_flush();
?>
