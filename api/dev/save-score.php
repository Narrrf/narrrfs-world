<?php
declare(strict_types=1);

// SEV-1 emergency containment: score submission is intentionally disabled.
//
// DEVS FOR DECADES:
// A valid session and matching identity are necessary request-shape checks, but
// they are not proof that a game result or reward was earned. Keep this route
// fail-closed until a separately reviewed server-owned result contract exists.

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/**
 * End the request with one non-sensitive JSON error response.
 */
function score_submission_error(int $status, string $code, string $message): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'code' => $code,
        'error' => $message,
    ]);
    exit;
}

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
if ($requestMethod !== 'POST') {
    header('Allow: POST');
    score_submission_error(405, 'method_not_allowed', 'POST is required.');
}

$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
$mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));
if ($mediaType !== 'application/json') {
    score_submission_error(415, 'unsupported_media_type', 'application/json is required.');
}

require_once __DIR__ . '/../config/session.php';

$sessionDiscordId = trim((string)($_SESSION['discord_id'] ?? ''));
if (!preg_match('/\A[0-9]{17,20}\z/', $sessionDiscordId)) {
    score_submission_error(401, 'authentication_required', 'Authentication is required.');
}

$rawBody = file_get_contents('php://input');
$requestData = json_decode(is_string($rawBody) ? $rawBody : '', true);
if (!is_array($requestData) || json_last_error() !== JSON_ERROR_NONE) {
    score_submission_error(400, 'invalid_json', 'A valid JSON object is required.');
}

$requestDiscordId = trim((string)($requestData['discord_id'] ?? ''));
if (!preg_match('/\A[0-9]{17,20}\z/', $requestDiscordId)) {
    score_submission_error(400, 'invalid_request_identity', 'A valid request identity is required.');
}

if (!hash_equals($sessionDiscordId, $requestDiscordId)) {
    score_submission_error(403, 'identity_mismatch', 'The request identity does not match the authenticated session.');
}

// Deliberately do not inspect game, score, wallet, reward, or role fields here.
// No database connection or protected write exists in this containment route.
score_submission_error(
    503,
    'score_submission_temporarily_disabled',
    'Score submission is temporarily disabled.'
);
