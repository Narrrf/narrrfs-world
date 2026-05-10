<?php
/**
 * Minimal email API helper (Resend only)
 * Neutral starter structure for customer projects.
 */

function send_email_via_resend($apiKey, $toEmail, $fromEmail, $fromName, $subject, $body, $replyTo = '') {
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Missing RESEND API key'];
    }

    $url = 'https://api.resend.com/emails';
    $data = [
        'from' => "{$fromName} <{$fromEmail}>",
        'to' => [$toEmail],
        'subject' => $subject,
        'text' => $body
    ];

    if (!empty($replyTo)) {
        $data['reply_to'] = [$replyTo];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'error' => ''];
    }

    return [
        'success' => false,
        'error' => 'Resend API error: ' . ($error ?: 'HTTP ' . $httpCode . ' - ' . $response)
    ];
}
