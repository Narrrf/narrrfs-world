<?php
/**
 * Poolbauprofi SMTP Helper
 * Lightweight SMTP client to send authenticated emails without external libraries.
 */

if (!function_exists('poolbau_get_email_settings')) {
    /**
     * Load email settings from admin/settings.json.
     */
    function poolbau_get_email_settings(): array
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $settingsPath = __DIR__ . '/../admin/settings.json';
        if (!file_exists($settingsPath)) {
            $cached = [];
            return $cached;
        }

        $json = file_get_contents($settingsPath);
        $data = json_decode($json, true);

        $cached = is_array($data) ? $data : [];
        return $cached;
    }
}

if (!function_exists('poolbau_can_use_smtp')) {
    /**
     * Determine if SMTP settings are complete enough for authenticated sending.
     */
    function poolbau_can_use_smtp(array $settings): bool
    {
        return !empty($settings['smtp_host'])
            && !empty($settings['smtp_username'])
            && !empty($settings['smtp_password']);
    }
}

if (!function_exists('poolbau_send_email_via_smtp')) {
    /**
     * Send email via SMTP using basic socket commands.
     *
     * @param array $config SMTP configuration (host, port, username, password, smtp_encryption, timeout)
     * @param array $mail   Mail data (to_email, to_name, from_email, from_name, reply_to_email, reply_to_name, subject, body)
     *
     * @return array ['success' => bool, 'error' => string, 'debug' => array]
     */
    function poolbau_send_email_via_smtp(array $config, array $mail): array
    {
        $host = $config['smtp_host'] ?? '';
        $port = isset($config['smtp_port']) ? (int)$config['smtp_port'] : 587;
        $username = $config['smtp_username'] ?? '';
        $password = $config['smtp_password'] ?? '';
        $encryption = strtolower($config['smtp_encryption'] ?? 'tls');
        $timeout = isset($config['smtp_timeout']) ? (int)$config['smtp_timeout'] : 30;

        if (empty($host) || empty($username) || empty($password)) {
            return [
                'success' => false,
                'error'   => 'Missing SMTP configuration (host/username/password).',
                'debug'   => []
            ];
        }

        $fromEmail = $mail['from_email'] ?? $username;
        $fromName = $mail['from_name'] ?? '';
        $toEmail = $mail['to_email'] ?? '';
        $toName = $mail['to_name'] ?? '';
        $replyEmail = $mail['reply_to_email'] ?? $fromEmail;
        $replyName = $mail['reply_to_name'] ?? $fromName;
        $subject = $mail['subject'] ?? 'Website Nachricht';
        $body = $mail['body'] ?? '';

        if (empty($toEmail)) {
            return [
                'success' => false,
                'error'   => 'Missing recipient email address.',
                'debug'   => []
            ];
        }

        $transportHost = $host;
        if (in_array($encryption, ['ssl', 'ssl/tls', 'smtps'], true)) {
            $transportHost = 'ssl://' . $host;
        } else {
            $transportHost = 'tcp://' . $host;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            $transportHost . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return [
                'success' => false,
                'error'   => "Connection failed: $errstr ($errno)",
                'debug'   => []
            ];
        }

        stream_set_timeout($socket, $timeout);

        $response = poolbau_smtp_read($socket);
        if ($response['code'] !== 220) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'SMTP greeting failed: ' . $response['message'],
                'debug'   => $response
            ];
        }

        $ehloHost = gethostname() ?: 'localhost';
        $cmdResult = poolbau_smtp_command($socket, "EHLO {$ehloHost}\r\n", [250]);
        if (!$cmdResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'EHLO command failed: ' . $cmdResult['message'],
                'debug'   => $cmdResult
            ];
        }

        if (in_array($encryption, ['tls', 'starttls'], true)) {
            $startTlsResult = poolbau_smtp_command($socket, "STARTTLS\r\n", [220]);
            if (!$startTlsResult['success']) {
                fclose($socket);
                return [
                    'success' => false,
                    'error'   => 'STARTTLS command failed: ' . $startTlsResult['message'],
                    'debug'   => $startTlsResult
                ];
            }

            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                fclose($socket);
                return [
                    'success' => false,
                    'error'   => 'Failed to enable TLS encryption.',
                    'debug'   => []
                ];
            }

            $cmdResult = poolbau_smtp_command($socket, "EHLO {$ehloHost}\r\n", [250]);
            if (!$cmdResult['success']) {
                fclose($socket);
                return [
                    'success' => false,
                    'error'   => 'EHLO after STARTTLS failed: ' . $cmdResult['message'],
                    'debug'   => $cmdResult
                ];
            }
        }

        $authResult = poolbau_smtp_command($socket, "AUTH LOGIN\r\n", [334]);
        if (!$authResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'AUTH LOGIN not accepted: ' . $authResult['message'],
                'debug'   => $authResult
            ];
        }

        $userResult = poolbau_smtp_command($socket, base64_encode($username) . "\r\n", [334]);
        if (!$userResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'SMTP username rejected: ' . $userResult['message'],
                'debug'   => $userResult
            ];
        }

        $passResult = poolbau_smtp_command($socket, base64_encode($password) . "\r\n", [235]);
        if (!$passResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'SMTP password rejected: ' . $passResult['message'],
                'debug'   => $passResult
            ];
        }

        $mailFromResult = poolbau_smtp_command($socket, "MAIL FROM:<{$fromEmail}>\r\n", [250, 251]);
        if (!$mailFromResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'MAIL FROM command failed: ' . $mailFromResult['message'],
                'debug'   => $mailFromResult
            ];
        }

        $rcptToResult = poolbau_smtp_command($socket, "RCPT TO:<{$toEmail}>\r\n", [250, 251]);
        if (!$rcptToResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'RCPT TO command failed: ' . $rcptToResult['message'],
                'debug'   => $rcptToResult
            ];
        }

        $dataResult = poolbau_smtp_command($socket, "DATA\r\n", [354]);
        if (!$dataResult['success']) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'DATA command failed: ' . $dataResult['message'],
                'debug'   => $dataResult
            ];
        }

        $headers = [];
        $headers[] = 'From: ' . poolbau_format_email_address($fromEmail, $fromName);
        $headers[] = 'To: ' . poolbau_format_email_address($toEmail, $toName);
        $headers[] = 'Reply-To: ' . poolbau_format_email_address($replyEmail, $replyName);
        $headers[] = 'Subject: ' . poolbau_encode_header($subject);
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'X-Mailer: Poolbauprofi.at SMTP';

        $bodyNormalized = preg_replace("/(?<!\r)\n/", "\r\n", $body);
        $bodyNormalized = str_replace("\r\n.", "\r\n..", $bodyNormalized); // Dot-stuffing

        $messageData = implode("\r\n", $headers) . "\r\n\r\n" . $bodyNormalized . "\r\n";

        fwrite($socket, $messageData . ".\r\n");

        $dataEndResponse = poolbau_smtp_read($socket);
        if (!in_array($dataEndResponse['code'], [250, 251], true)) {
            fclose($socket);
            return [
                'success' => false,
                'error'   => 'Failed to accept email data: ' . $dataEndResponse['message'],
                'debug'   => $dataEndResponse
            ];
        }

        poolbau_smtp_command($socket, "QUIT\r\n", [221]);
        fclose($socket);

        return [
            'success' => true,
            'error'   => '',
            'debug'   => []
        ];
    }
}

if (!function_exists('poolbau_smtp_command')) {
    /**
     * Write a command to the SMTP socket and validate response codes.
     */
    function poolbau_smtp_command($socket, string $command, array $expectedCodes): array
    {
        fwrite($socket, $command);
        $response = poolbau_smtp_read($socket);

        if (!in_array($response['code'], $expectedCodes, true)) {
            return [
                'success' => false,
                'code'    => $response['code'],
                'message' => $response['message']
            ];
        }

        return [
            'success' => true,
            'code'    => $response['code'],
            'message' => $response['message']
        ];
    }
}

if (!function_exists('poolbau_smtp_read')) {
    /**
     * Read SMTP response and return code/message.
     */
    function poolbau_smtp_read($socket): array
    {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (strlen($str) < 4 || $str[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($data, 0, 3);
        $message = trim($data);

        return [
            'code'    => $code,
            'message' => $message
        ];
    }
}

if (!function_exists('poolbau_format_email_address')) {
    /**
     * Format email address with optional display name.
     */
    function poolbau_format_email_address(string $email, string $name = ''): string
    {
        $email = trim($email);
        if ($name === '') {
            return $email;
        }

        return poolbau_encode_header($name) . ' <' . $email . '>';
    }
}

if (!function_exists('poolbau_encode_header')) {
    /**
     * Encode header values safely for UTF-8.
     */
    function poolbau_encode_header(string $value): string
    {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
?>

