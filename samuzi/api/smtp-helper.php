<?php
/**
 * Minimal settings helper for email sender.
 */

if (!function_exists('poolbau_get_email_settings')) {
    function poolbau_get_email_settings(): array
    {
        $settingsPath = __DIR__ . '/../admin/settings.json';
        if (!file_exists($settingsPath)) {
            return [];
        }

        $raw = json_decode(file_get_contents($settingsPath), true);
        $settings = is_array($raw) ? $raw : [];

        $envResendKey = getenv('RESEND_API_KEY');
        if ($envResendKey !== false && $envResendKey !== '') {
            $settings['resend_api_key'] = $envResendKey;
        }

        return $settings;
    }
}
