<?php
require_once __DIR__ . '/_partner-config.php';

/**
 * Send one JSON response and stop execution.
 */
function partnerBridgeRespond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Return the SQLite PDO connection for local or production.
 */
function partnerBridgeGetDb(): PDO
{
    $dbPath = partnerBridgeIsLocalhost()
        ? __DIR__ . '/../../../db/narrrf_world.sqlite'
        : '/var/www/html/db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found for partner bridge: ' . $dbPath);
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA busy_timeout = 5000');

    return $db;
}

/**
 * Read JSON request body safely.
 */
function partnerBridgeReadJsonBody(): array
{
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody ?: '{}', true);

    if (!is_array($data)) {
        partnerBridgeRespond(400, [
            'success' => false,
            'error' => 'Invalid JSON body'
        ]);
    }

    return $data;
}

/**
 * Read an HTTP header from Apache/FPM environments.
 */
function partnerBridgeGetHeader(string $name): string
{
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return trim((string)($_SERVER[$serverKey] ?? ''));
}

/**
 * Validate a safe external partner user ID.
 */
function partnerBridgeValidateExternalUserId(string $externalUserId): bool
{
    return preg_match('/^[A-Za-z0-9._:@-]{1,120}$/', $externalUserId) === 1;
}

/**
 * Validate a public game key supported by Partner Bridge V1.
 */
function partnerBridgeValidateGame(string $game, array $partnerConfig): bool
{
    $allowedGames = $partnerConfig['allowed_games'] ?? [];
    return in_array($game, $allowedGames, true);
}

/**
 * Create a URL-safe random token.
 */
function partnerBridgeCreateToken(string $prefix, int $bytes = 24): string
{
    return $prefix . bin2hex(random_bytes($bytes));
}

/**
 * Return a SHA-256 token hash for DB storage.
 */
function partnerBridgeHashToken(string $token): string
{
    return hash('sha256', $token);
}

/**
 * Calculate partner points for V1.
 *
 * Plain language for DEVS FOR DECADES:
 * This calculates Samuzi partner points only. It does not write Narrrfs
 * DSPOINC, SPOINC, score ledgers, tournament rewards, or partner-chain coins.
 */
function partnerBridgeCalculatePartnerPoints(int $rawScore, array $partnerConfig): int
{
    $divisor = max(1, (int)($partnerConfig['points_divisor'] ?? 100));
    return max(0, (int)floor($rawScore / $divisor));
}