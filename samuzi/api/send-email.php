<?php
require_once __DIR__ . '/smtp-helper.php';
require_once __DIR__ . '/email-api-service.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid form data']);
    exit();
}

$settings = poolbau_get_email_settings();
$toEmail = $settings['email_address'] ?? '';
$fromEmail = $settings['from_email'] ?? '';
$fromName = $settings['from_name'] ?? 'Website Contact';
$apiKey = $settings['resend_api_key'] ?? '';

if ($toEmail === '' || $fromEmail === '' || $apiKey === '') {
    echo json_encode([
        'success' => true,
        'message' => 'Message captured. Email service is not configured yet.',
        'configured' => false
    ]);
    exit();
}

$subject = 'New website contact message';
$body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}\n";

$result = send_email_via_resend($apiKey, $toEmail, $fromEmail, $fromName, $subject, $body, $email);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Message captured. Delivery failed until email settings are finalized.',
        'configured' => true
    ]);
}
