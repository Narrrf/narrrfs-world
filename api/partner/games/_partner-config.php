<?php
/**
 * Partner Game Bridge V1 config.
 *
 * Plain language for DEVS FOR DECADES:
 * This config is for the Samuzi / Narrrfs iframe-session bridge.
 * Server-to-server partner APIs use the same shared internal secret style as
 * protected bridge/admin endpoints. API keys must never be placed in iframe
 * URLs, browser JavaScript, partner frontend code, Discord messages, or public docs.
 */

function partnerBridgeIsLocalhost(): bool
{
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));

    return (
        $host === 'localhost' ||
        str_starts_with($host, 'localhost:') ||
        $host === '127.0.0.1' ||
        str_starts_with($host, '127.0.0.1:')
    );
}

/**
 * Resolve the shared internal API secret used by protected Narrrfs bridge APIs.
 */
function partnerBridgeGetSharedInternalSecret(): string
{
    $candidates = [
        getenv('API_SECRET'),
        getenv('INTERNAL_API_SECRET'),
        getenv('DISCORD_SECRET'),
        getenv('SPOINC_BRIDGE_API_KEY'),
        getenv('PARTNER_SAMUZI_API_KEY')
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return '';
}

function partnerBridgeGetProjectConfig(string $partnerId): ?array
{
    $partnerId = strtolower(trim($partnerId));

    $configs = [
        'samuzi' => [
            'partner_id' => 'samuzi',
            'partner_name' => 'Samuzi',
            'allowed_games' => ['cheeseman'],
            'session_ttl_seconds' => 1800,
            'points_divisor' => 100,

            /**
             * Local fallback only.
             *
             * Plain language for DEVS FOR DECADES:
             * Production should use the shared internal environment secret.
             * This local fallback exists only so XAMPP tests can run before
             * the server env value is configured locally.
             */
            'local_api_key_fallback' => 'TEST_REPLACE_ME',
        ],
    ];

    return $configs[$partnerId] ?? null;
}

function partnerBridgeGetExpectedApiKey(array $partnerConfig): string
{
    $sharedSecret = partnerBridgeGetSharedInternalSecret();

    if ($sharedSecret !== '') {
        return $sharedSecret;
    }

    if (partnerBridgeIsLocalhost()) {
        return (string)($partnerConfig['local_api_key_fallback'] ?? '');
    }

    return '';
}

function partnerBridgeGetPublicBaseUrl(): string
{
    if (partnerBridgeIsLocalhost()) {
        return 'http://localhost';
    }

    return 'https://narrrfs.world';
}
?>