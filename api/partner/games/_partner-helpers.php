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

/**
 * Decode partner session metadata safely.
 *
 * Plain language for DEVS FOR DECADES:
 * Partner metadata is display/session context only. It must never become
 * Narrrfs OAuth proof, DSPOINC authority, SPOINC authority, or ownership proof.
 */
function partnerBridgeDecodeMetadata(?string $metadataJson): array
{
    if (!$metadataJson) {
        return [];
    }

    $decoded = json_decode($metadataJson, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Build the safe partner identity payload for API responses and iframe display.
 *
 * Plain language for DEVS FOR DECADES:
 * This identity is partner-asserted through the server-side API key. It is not
 * a Narrrfs Discord OAuth login and must not auto-create users or grant rewards.
 */
function partnerBridgeBuildPartnerIdentity(array $session): array
{
    $metadata = partnerBridgeDecodeMetadata($session['metadata_json'] ?? null);
    $metadataIdentity = $metadata['partner_identity'] ?? [];

    if (!is_array($metadataIdentity)) {
        $metadataIdentity = [];
    }

    $discordId =
        $metadataIdentity['discord_id']
        ?? $metadata['discord_id']
        ?? null;

    return [
        'identity_source' => 'partner_asserted',
        'external_user_id' => $session['external_user_id'] ?? null,
        'wallet_address' => $session['wallet_address'] ?? null,
        'discord_id' => $discordId,
        'discord_name' => $session['discord_name'] ?? ($metadataIdentity['discord_name'] ?? null)
    ];
}

/**
 * Return partner-safe game stats for one partner user.
 *
 * Plain language for DEVS FOR DECADES:
 * This reads only isolated partner bridge result rows. It does not read or
 * write Narrrfs DSPOINC, public leaderboards, tbl_tetris_scores, SPOINC, or
 * partner token payouts.
 */
function partnerBridgeGetPartnerStats(
    PDO $db,
    string $partnerId,
    string $externalUserId,
    string $game
): array {
    $stmt = $db->prepare("
        SELECT
            COUNT(*) AS sessions_played,
            COALESCE(MAX(raw_score), 0) AS best_score,
            COALESCE(SUM(raw_score), 0) AS total_score,
            COALESCE(SUM(partner_points), 0) AS total_partner_points
        FROM tbl_partner_game_results
        WHERE partner_id = :partner_id
          AND external_user_id = :external_user_id
          AND game = :game
          AND validation_status = 'accepted'
    ");

    $stmt->execute([
        ':partner_id' => $partnerId,
        ':external_user_id' => $externalUserId,
        ':game' => $game
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'sessions_played' => (int)($row['sessions_played'] ?? 0),
        'best_score' => (int)($row['best_score'] ?? 0),
        'total_score' => (int)($row['total_score'] ?? 0),
        'total_partner_points' => (int)($row['total_partner_points'] ?? 0)
    ];
}