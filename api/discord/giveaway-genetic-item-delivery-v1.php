<?php
/** Dedicated, default-off bot service endpoint for persisted Genetic giveaway rewards. */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function giveaway_genetic_delivery_v1_reply(int $status, string $code): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $code], JSON_UNESCAPED_SLASHES);
    exit;
}

function giveaway_genetic_delivery_v1_authorization_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $name) {
        if (isset($_SERVER[$name]) && is_string($_SERVER[$name])) {
            return trim($_SERVER[$name]);
        }
    }
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp((string) $name, 'Authorization') === 0) {
                return trim((string) $value);
            }
        }
    }
    return '';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    giveaway_genetic_delivery_v1_reply(405, 'method_not_allowed');
}
if (getenv('NARRRFS_GIVEAWAY_GENETIC_DELIVERY_V1_ENABLED') !== '1') {
    giveaway_genetic_delivery_v1_reply(404, 'giveaway_genetic_delivery_unavailable');
}

require_once __DIR__ . '/../lib/giveaway-genetic-item-delivery-v1.php';

$expectedSecret = getenv('NARRRFS_GIVEAWAY_DELIVERY_V1_SECRET');
if (!is_string($expectedSecret) || strlen($expectedSecret) < 32) {
    error_log('[GIVEAWAY GENETIC DELIVERY V1] Dedicated service credential unavailable');
    giveaway_genetic_delivery_v1_reply(503, 'delivery_auth_unavailable');
}
if (!giveaway_genetic_delivery_v1_is_authorized($expectedSecret, giveaway_genetic_delivery_v1_authorization_header())) {
    giveaway_genetic_delivery_v1_reply(401, 'invalid_service_authorization');
}

$contentType = strtolower(trim((string) ($_SERVER['CONTENT_TYPE'] ?? '')));
if (!preg_match('/\Aapplication\/json(?:\s*;.*)?\z/D', $contentType)) {
    giveaway_genetic_delivery_v1_reply(415, 'json_required');
}
$raw = file_get_contents('php://input', false, null, 0, 2049);
if (!is_string($raw) || strlen($raw) > 2048 || $raw === '') {
    giveaway_genetic_delivery_v1_reply(413, 'invalid_request_size');
}
try {
    $request = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException $error) {
    giveaway_genetic_delivery_v1_reply(400, 'invalid_json');
}
if (!is_array($request)
    || count($request) !== 2
    || !isset($request['giveaway_id'], $request['winner_user_id'])
    || !is_string($request['giveaway_id'])
    || !is_string($request['winner_user_id'])) {
    giveaway_genetic_delivery_v1_reply(400, 'invalid_request_fields');
}

$databasePath = dirname(__DIR__, 2) . '/db/narrrf_world.sqlite';
if (!is_file($databasePath) || !is_readable($databasePath)) {
    giveaway_genetic_delivery_v1_reply(503, 'runtime_database_unavailable');
}

try {
    $database = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    $receipt = giveaway_genetic_delivery_v1($database, $request['giveaway_id'], $request['winner_user_id']);
    http_response_code(200);
    echo json_encode($receipt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (GiveawayGeneticDeliveryV1Error $error) {
    giveaway_genetic_delivery_v1_reply($error->getCode(), $error->getMessage());
} catch (Throwable $error) {
    error_log('[GIVEAWAY GENETIC DELIVERY V1] Transaction or runtime failure: ' . $error->getMessage());
    giveaway_genetic_delivery_v1_reply(500, 'giveaway_genetic_delivery_failed');
}
