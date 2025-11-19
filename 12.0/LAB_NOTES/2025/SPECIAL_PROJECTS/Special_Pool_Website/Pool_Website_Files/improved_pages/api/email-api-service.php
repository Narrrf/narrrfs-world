<?php
/**
 * Email API Service - No SMTP Required!
 * 
 * Uses free email API services (SendGrid, Mailgun, Resend) instead of SMTP
 * No SMTP credentials needed - just an API key!
 * 
 * Created: 2025-11-19
 * Purpose: Send emails without SMTP setup
 */

/**
 * Send email via SendGrid API (Free: 100 emails/day)
 * 
 * @param string $apiKey SendGrid API key
 * @param string $toEmail Recipient email
 * @param string $fromEmail Sender email
 * @param string $fromName Sender name
 * @param string $subject Email subject
 * @param string $body Email body
 * @param string $replyTo Reply-to email (optional)
 * @return array ['success' => bool, 'error' => string]
 */
function send_email_via_sendgrid($apiKey, $toEmail, $fromEmail, $fromName, $subject, $body, $replyTo = '') {
    $url = 'https://api.sendgrid.com/v3/mail/send';
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $toEmail]],
                'subject' => $subject
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $body
            ]
        ]
    ];
    
    if (!empty($replyTo)) {
        $data['reply_to'] = ['email' => $replyTo];
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
    } else {
        return [
            'success' => false,
            'error' => 'SendGrid API error: ' . ($error ?: 'HTTP ' . $httpCode . ' - ' . $response)
        ];
    }
}

/**
 * Send email via Mailgun API (Free: 5,000 emails/month)
 * 
 * @param string $apiKey Mailgun API key
 * @param string $domain Mailgun domain
 * @param string $toEmail Recipient email
 * @param string $fromEmail Sender email
 * @param string $fromName Sender name
 * @param string $subject Email subject
 * @param string $body Email body
 * @param string $replyTo Reply-to email (optional)
 * @return array ['success' => bool, 'error' => string]
 */
function send_email_via_mailgun($apiKey, $domain, $toEmail, $fromEmail, $fromName, $subject, $body, $replyTo = '') {
    $url = "https://api.mailgun.net/v3/{$domain}/messages";
    
    $data = [
        'from' => "{$fromName} <{$fromEmail}>",
        'to' => $toEmail,
        'subject' => $subject,
        'text' => $body
    ];
    
    if (!empty($replyTo)) {
        $data['h:Reply-To'] = $replyTo;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, "api:{$apiKey}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'error' => ''];
    } else {
        return [
            'success' => false,
            'error' => 'Mailgun API error: ' . ($error ?: 'HTTP ' . $httpCode . ' - ' . $response)
        ];
    }
}

/**
 * Send email via Resend API (Free: 3,000 emails/month)
 * 
 * @param string $apiKey Resend API key
 * @param string $toEmail Recipient email
 * @param string $fromEmail Sender email
 * @param string $fromName Sender name
 * @param string $subject Email subject
 * @param string $body Email body
 * @param string $replyTo Reply-to email (optional)
 * @return array ['success' => bool, 'error' => string]
 */
function send_email_via_resend($apiKey, $toEmail, $fromEmail, $fromName, $subject, $body, $replyTo = '') {
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
    } else {
        return [
            'success' => false,
            'error' => 'Resend API error: ' . ($error ?: 'HTTP ' . $httpCode . ' - ' . $response)
        ];
    }
}

/**
 * Send email using the best available service
 * 
 * @param array $settings Email settings from settings.json
 * @param array $emailData Email data (to, from, subject, body, reply_to)
 * @return array ['success' => bool, 'error' => string, 'service' => string]
 */
function send_email_via_api($settings, $emailData) {
    $toEmail = $emailData['to_email'] ?? '';
    $fromEmail = $emailData['from_email'] ?? '';
    $fromName = $emailData['from_name'] ?? '';
    $subject = $emailData['subject'] ?? '';
    $body = $emailData['body'] ?? '';
    $replyTo = $emailData['reply_to_email'] ?? '';
    
    // Try SendGrid first (if configured)
    if (!empty($settings['sendgrid_api_key'])) {
        $result = send_email_via_sendgrid(
            $settings['sendgrid_api_key'],
            $toEmail,
            $fromEmail,
            $fromName,
            $subject,
            $body,
            $replyTo
        );
        if ($result['success']) {
            return array_merge($result, ['service' => 'SendGrid']);
        }
    }
    
    // Try Mailgun (if configured)
    if (!empty($settings['mailgun_api_key']) && !empty($settings['mailgun_domain'])) {
        $result = send_email_via_mailgun(
            $settings['mailgun_api_key'],
            $settings['mailgun_domain'],
            $toEmail,
            $fromEmail,
            $fromName,
            $subject,
            $body,
            $replyTo
        );
        if ($result['success']) {
            return array_merge($result, ['service' => 'Mailgun']);
        }
    }
    
    // Try Resend (if configured)
    if (!empty($settings['resend_api_key'])) {
        $result = send_email_via_resend(
            $settings['resend_api_key'],
            $toEmail,
            $fromEmail,
            $fromName,
            $subject,
            $body,
            $replyTo
        );
        if ($result['success']) {
            return array_merge($result, ['service' => 'Resend']);
        }
    }
    
    return [
        'success' => false,
        'error' => 'No email API service configured. Please set up SendGrid, Mailgun, or Resend.',
        'service' => 'none'
    ];
}
?>

