<?php
/**
 * SPOINC Bridge Tester Helpers.
 *
 * Plain language for DEVS:
 * This helper controls the private SPOINC bridge tester allowlist.
 * It lets Narrrf and justme test bridge-intent preparation safely before
 * public launch.
 *
 * This helper must never:
 * - enable public swap execution
 * - call Gensuki transaction endpoints
 * - deduct DSPOINC
 * - credit DSPOINC
 * - settle bridge intents
 * - expose any API key
 */

const SPOINC_BRIDGE_TESTER_NARRRF_DISCORD_ID = '328601656659017732';
const SPOINC_BRIDGE_TESTER_JUSTME_DISCORD_ID = '1224428436928594015';

const SPOINC_BRIDGE_TESTER_IDS = [
    SPOINC_BRIDGE_TESTER_NARRRF_DISCORD_ID,
    SPOINC_BRIDGE_TESTER_JUSTME_DISCORD_ID
];

/**
 * Return true when running on local development.
 *
 * Plain language for DEVS:
 * Localhost can pass user_id for curl/browser testing.
 * Production must rely on the Discord session.
 */
function spoinc_bridge_tester_is_localhost(): bool
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');

    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON, POST, and GET.
 */
function spoinc_bridge_tester_get_request_data(): array
{
    static $cachedRequestData = null;

    if ($cachedRequestData !== null) {
        return $cachedRequestData;
    }

    $cachedRequestData = [];

    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);

    if (is_array($jsonInput)) {
        $cachedRequestData = $jsonInput;
    }

    if (!empty($_POST)) {
        $cachedRequestData = array_merge($cachedRequestData, $_POST);
    }

    if (!empty($_GET)) {
        $cachedRequestData = array_merge($cachedRequestData, $_GET);
    }

    return $cachedRequestData;
}

/**
 * Resolve the Discord user for private SPOINC bridge testing.
 *
 * Plain language for DEVS:
 * In production, the session is the authority.
 * On localhost, user_id may be passed for curl/browser tests.
 */
function spoinc_bridge_tester_resolve_user_id(array $requestData): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($requestData['user_id'] ?? ''));

    if (spoinc_bridge_tester_is_localhost() && $requestUserId !== '') {
        return $requestUserId;
    }

    if ($requestUserId !== '' && $requestUserId !== $sessionUserId) {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    return $sessionUserId;
}
/**
 * Return true when the resolved user is allowed to access private bridge tests.
 */
function spoinc_bridge_tester_is_allowed(string $userId): bool
{
    return in_array($userId, SPOINC_BRIDGE_TESTER_IDS, true);
}

/**
 * Require private tester access and return the resolved user id.
 *
 * Plain language for DEVS:
 * Use this at the top of every private test endpoint.
 */
function spoinc_bridge_tester_require_access(): string
{
    $requestData = spoinc_bridge_tester_get_request_data();
    $userId = spoinc_bridge_tester_resolve_user_id($requestData);

    if ($userId === '') {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    if (!spoinc_bridge_tester_is_allowed($userId)) {
        spoinc_bridge_tester_json_response([
            'success' => false,
            'error' => 'SPOINC bridge private testing is limited to allowlisted testers.'
        ], 403);
    }

    return $userId;
}

/**
 * Return a clean JSON response and stop execution.
 */
function spoinc_bridge_tester_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}